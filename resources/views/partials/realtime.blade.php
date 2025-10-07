@php
    /** @var array<int, int|string> $shipments */
    $shipments = array_map(static fn ($value) => (int) $value, array_values(array_unique($shipments ?? [])));
    /** @var array<int, array{label:string, reference?:string|null}> $shipmentMeta */
    $shipmentMeta = $shipmentMeta ?? [];
    $ablyKey = config('services.ably.key');
@endphp

@once
    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/ably@1.2.38/build/ably.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/laravel-echo@1/dist/laravel-echo.iife.js"></script>
        <script>
            (function () {
                if (window.LogistikRealtime) {
                    return;
                }

                const config = {
                    ablyKey: @json($ablyKey),
                    authEndpoint: @json(url('/broadcasting/auth')),
                };

                const numberFormatter = new Intl.NumberFormat('id-ID', {
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 2,
                });

                class LogistikRealtimeHub {
                    constructor(options) {
                        this.config = options;
                        this.channels = new Map();
                        this.shipmentMeta = {};
                        this.shipmentRows = new Map();
                        this.progressBars = new Map();
                        this.statusPills = new Map();
                        this.activityFeeds = new Set();
                        this.activityLimit = 20;
                        this.echo = null;
                        this.ably = null;
                    }

                    boot(payload) {
                        if (payload?.meta) {
                            this.registerShipmentMeta(payload.meta);
                        }

                        this.scanDom();

                        (payload?.shipments ?? []).forEach((id) => {
                            this.subscribeShipment(id);
                        });
                    }

                    registerShipmentMeta(meta) {
                        this.shipmentMeta = Object.assign({}, this.shipmentMeta, meta ?? {});
                    }

                    scanDom() {
                        document.querySelectorAll('[data-activity-feed]').forEach((element) => {
                            if (element.dataset.activityLimit) {
                                const limit = parseInt(element.dataset.activityLimit, 10);
                                if (! Number.isNaN(limit)) {
                                    this.activityLimit = limit;
                                }
                            }

                            this.activityFeeds.add(element);
                        });

                        document.querySelectorAll('[data-shipment-item-row]').forEach((row) => {
                            const id = parseInt(row.dataset.shipmentItemId ?? '', 10);

                            if (Number.isNaN(id)) {
                                return;
                            }

                            const pickedCell = row.querySelector('[data-field="qty_picked"]');
                            if (! pickedCell) {
                                return;
                            }

                            const deliveredCell = row.querySelector('[data-field="qty_delivered"]');

                            this.shipmentRows.set(id, {
                                row,
                                pickedCell,
                                deliveredCell,
                                picked: parseFloat(row.dataset.qtyPicked ?? '0') || 0,
                                delivered: parseFloat(row.dataset.qtyDelivered ?? '0') || 0,
                                planned: parseFloat(row.dataset.qtyPlanned ?? '0') || 0,
                            });
                        });

                        document.querySelectorAll('[data-shipment-progress]').forEach((wrapper) => {
                            const shipmentId = parseInt(wrapper.dataset.shipmentId ?? '', 10);

                            if (Number.isNaN(shipmentId)) {
                                return;
                            }

                            const fill = wrapper.querySelector('[data-progress-fill]');
                            const label = wrapper.querySelector('[data-progress-label]');

                            if (! fill || ! label) {
                                return;
                            }

                            this.progressBars.set(shipmentId, {
                                wrapper,
                                fill,
                                label,
                                planned: parseFloat(wrapper.dataset.totalPlanned ?? '0') || 0,
                                picked: parseFloat(wrapper.dataset.totalPicked ?? '0') || 0,
                            });

                            this.renderProgress(shipmentId);
                        });

                        document.querySelectorAll('[data-shipment-status]').forEach((element) => {
                            const shipmentId = parseInt(element.dataset.shipmentStatus ?? '', 10);

                            if (! Number.isNaN(shipmentId)) {
                                this.statusPills.set(shipmentId, element);
                            }
                        });
                    }

                    ensureEcho() {
                        if (this.echo) {
                            return this.echo;
                        }

                        if (! this.config.ablyKey) {
                            console.warn('Ably key is not configured; realtime disabled.');
                            return null;
                        }

                        if (typeof Ably === 'undefined' || typeof Ably.Realtime === 'undefined') {
                            console.warn('Ably JS client not loaded.');
                            return null;
                        }

                        const EchoConstructor = window.Echo;
                        if (typeof EchoConstructor !== 'function') {
                            console.warn('Laravel Echo library not available.');
                            return null;
                        }

                        this.ably = this.ably ?? new Ably.Realtime.Promise({
                            key: this.config.ablyKey,
                        });

                        this.echo = new EchoConstructor({
                            broadcaster: 'ably',
                            key: this.config.ablyKey,
                            client: this.ably,
                            authEndpoint: this.config.authEndpoint,
                            auth: {
                                headers: {
                                    'X-CSRF-TOKEN': this.csrfToken(),
                                },
                            },
                        });

                        return this.echo;
                    }

                    subscribeShipment(shipmentId) {
                        if (! shipmentId || this.channels.has(shipmentId)) {
                            return;
                        }

                        const echo = this.ensureEcho();
                        if (! echo) {
                            return;
                        }

                        const channel = echo.private(`wms.outbound.shipment.${shipmentId}`)
                            .listen('.pick.completed', (payload) => this.handlePick(payload))
                            .listen('.shipment.dispatched', (payload) => this.handleDispatched(payload))
                            .listen('.shipment.delivered', (payload) => this.handleDelivered(payload));

                        this.channels.set(shipmentId, channel);
                    }

                    handlePick(payload) {
                        const shipmentId = Number(payload?.shipment_id ?? 0);
                        const shipmentItemId = Number(payload?.shipment_item_id ?? 0);
                        const qty = parseFloat(payload?.qty_picked ?? 0) || 0;

                        if (shipmentItemId && this.shipmentRows.has(shipmentItemId)) {
                            const details = this.shipmentRows.get(shipmentItemId);
                            details.picked += qty;
                            details.pickedCell.dataset.value = details.picked.toString();
                            details.pickedCell.textContent = numberFormatter.format(details.picked);
                        }

                        if (shipmentId && this.progressBars.has(shipmentId)) {
                            const progress = this.progressBars.get(shipmentId);
                            progress.picked += qty;
                            progress.wrapper.dataset.totalPicked = progress.picked.toString();
                            this.renderProgress(shipmentId);
                        }

                        this.appendActivity('pick', payload);
                    }

                    handleDispatched(payload) {
                        const shipmentId = Number(payload?.shipment_id ?? 0);
                        this.updateStatus(shipmentId, 'dispatched');
                        this.appendActivity('dispatched', payload);
                    }

                    handleDelivered(payload) {
                        const shipmentId = Number(payload?.shipment_id ?? 0);
                        this.updateStatus(shipmentId, 'delivered');

                        if (shipmentId && this.progressBars.has(shipmentId)) {
                            const progress = this.progressBars.get(shipmentId);
                            progress.picked = Math.max(progress.picked, progress.planned);
                            this.renderProgress(shipmentId);
                        }

                        this.appendActivity('delivered', payload);
                    }

                    renderProgress(shipmentId) {
                        const progress = this.progressBars.get(shipmentId);

                        if (! progress) {
                            return;
                        }

                        const planned = progress.planned > 0 ? progress.planned : 0;
                        const picked = Math.min(progress.picked, planned);
                        const ratio = planned > 0 ? Math.min(picked / planned, 1) : 0;

                        progress.fill.style.width = `${(ratio * 100).toFixed(1)}%`;
                        progress.label.textContent = `${numberFormatter.format(picked)} / ${numberFormatter.format(planned)} Picked`;
                    }

                    updateStatus(shipmentId, status) {
                        if (! shipmentId || ! this.statusPills.has(shipmentId)) {
                            return;
                        }

                        const pill = this.statusPills.get(shipmentId);
                        const statuses = ['draft', 'allocated', 'dispatched', 'delivered'];
                        statuses.forEach((name) => pill.classList.remove(`status-${name}`));
                        pill.classList.add(`status-${status}`);
                        pill.textContent = status.charAt(0).toUpperCase() + status.slice(1);
                    }

                    appendActivity(type, payload) {
                        if (! this.activityFeeds.size) {
                            return;
                        }

                        const entry = this.composeActivityEntry(type, payload);

                        if (! entry) {
                            return;
                        }

                        this.activityFeeds.forEach((feed) => {
                            const item = document.createElement('div');
                            item.className = 'movement-item';

                            const header = document.createElement('strong');
                            const labelSpan = document.createElement('span');
                            labelSpan.textContent = entry.title;
                            header.appendChild(labelSpan);

                            const highlightSpan = document.createElement('span');
                            highlightSpan.textContent = entry.highlight ?? '';
                            header.appendChild(highlightSpan);

                            item.appendChild(header);

                            if (entry.meta) {
                                const metaSpan = document.createElement('span');
                                metaSpan.textContent = entry.meta;
                                item.appendChild(metaSpan);
                            }

                            if (entry.timestamp?.human) {
                                const timestampSpan = document.createElement('span');
                                timestampSpan.style.color = '#64748b';
                                timestampSpan.style.fontSize = '0.85rem';
                                timestampSpan.textContent = entry.timestamp.human;
                                item.appendChild(timestampSpan);
                            }

                            if (entry.remarks) {
                                const remarksSpan = document.createElement('span');
                                remarksSpan.style.color = '#475569';
                                remarksSpan.style.fontSize = '0.9rem';
                                remarksSpan.textContent = `"${entry.remarks}"`;
                                item.appendChild(remarksSpan);
                            }

                            feed.prepend(item);

                            while (feed.children.length > this.activityLimit) {
                                feed.removeChild(feed.lastElementChild);
                            }
                        });
                    }

                    composeActivityEntry(type, payload) {
                        const shipmentId = Number(payload?.shipment_id ?? 0);
                        const label = this.shipmentLabel(shipmentId);

                        if (type === 'pick') {
                            const itemId = payload?.item_id ? `Item #${payload.item_id}` : null;
                            const iso = payload?.picked_at ?? null;

                            return {
                                title: `Pick Completed · ${label}`,
                                highlight: numberFormatter.format(parseFloat(payload?.qty_picked ?? 0) || 0),
                                meta: itemId,
                                timestamp: this.formatTimestamp(iso),
                                remarks: null,
                            };
                        }

                        if (type === 'dispatched') {
                            const iso = payload?.dispatched_at ?? null;
                            let meta = null;

                            if (payload?.driver_id) {
                                meta = `Driver #${payload.driver_id}`;
                            }

                            return {
                                title: `Shipment Dispatched · ${label}`,
                                highlight: 'Dispatched',
                                meta,
                                timestamp: this.formatTimestamp(iso),
                                remarks: null,
                            };
                        }

                        if (type === 'delivered') {
                            const iso = payload?.delivered_at ?? null;
                            const signer = payload?.signer ? `Signer: ${payload.signer}` : null;

                            return {
                                title: `Shipment Delivered · ${label}`,
                                highlight: 'Delivered',
                                meta: signer,
                                timestamp: this.formatTimestamp(iso),
                                remarks: null,
                            };
                        }

                        return null;
                    }

                    shipmentLabel(shipmentId) {
                        if (shipmentId && this.shipmentMeta[shipmentId]) {
                            return this.shipmentMeta[shipmentId].label;
                        }

                        if (! shipmentId) {
                            return 'Shipment';
                        }

                        return `Shipment #${shipmentId}`;
                    }

                    formatTimestamp(iso) {
                        if (! iso) {
                            return { iso: null, human: null };
                        }

                        const date = new Date(iso);

                        if (Number.isNaN(date.getTime())) {
                            return { iso: null, human: null };
                        }

                        return {
                            iso,
                            human: date.toLocaleString('id-ID', {
                                day: '2-digit',
                                month: 'short',
                                year: 'numeric',
                                hour: '2-digit',
                                minute: '2-digit',
                            }),
                        };
                    }

                    csrfToken() {
                        const tokenMeta = document.querySelector('meta[name="csrf-token"]');
                        return tokenMeta ? tokenMeta.getAttribute('content') : '';
                    }
                }

                window.LogistikRealtime = new LogistikRealtimeHub(config);
            })();
        </script>
    @endpush
@endonce

@push('scripts')
    <script>
        window.LogistikRealtime?.boot({
            shipments: @json($shipments),
            meta: @json($shipmentMeta),
        });
    </script>
@endpush
