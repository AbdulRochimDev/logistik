<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Monitoring Logistik Gudang</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            color: #0f172a;
            background: radial-gradient(circle at top left, rgba(14, 116, 144, 0.25), transparent 45%),
                        radial-gradient(circle at bottom right, rgba(59, 130, 246, 0.25), transparent 55%),
                        #f8fafc;
        }

        header {
            padding: 1.5rem 2.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1.5rem;
        }

        header a {
            color: #0f172a;
            font-weight: 600;
            text-decoration: none;
            margin-left: 1.25rem;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .header-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 1.5rem;
            border-radius: 999px;
            text-decoration: none;
            font-weight: 600;
            border: 1px solid rgba(148, 163, 184, 0.35);
            background: rgba(255, 255, 255, 0.85);
            color: #0f172a;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .header-btn.primary {
            background: linear-gradient(135deg, #0ea5e9, #2563eb);
            color: white;
            border-color: transparent;
            box-shadow: 0 12px 28px rgba(14, 165, 233, 0.28);
        }

        .header-btn:hover {
            transform: translateY(-1px);
        }

        .hero {
            position: relative;
            display: grid;
            gap: 2rem;
            padding: 4rem 2.5rem 5rem;
            min-height: 70vh;
        }

        .hero::before {
            content: "";
            position: absolute;
            inset: 10% 5% auto;
            height: 60%;
            z-index: -1;
            filter: blur(90px);
            background: linear-gradient(135deg, rgba(56, 189, 248, 0.35), rgba(34, 197, 94, 0.25));
        }

        .hero-content {
            max-width: 680px;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(14, 165, 233, 0.12);
            color: #0284c7;
            padding: 0.4rem 1rem;
            border-radius: 999px;
            font-size: 0.95rem;
            font-weight: 600;
        }

        h1 {
            margin-top: 1.4rem;
            font-size: clamp(2.75rem, 5vw, 4rem);
            line-height: 1.1;
            color: #0f172a;
        }

        h1 span {
            background: linear-gradient(135deg, #38bdf8, #34d399, #facc15);
            -webkit-background-clip: text;
            color: transparent;
        }

        .lead {
            margin-top: 1.25rem;
            font-size: 1.1rem;
            color: #1f2937;
            max-width: 48ch;
        }

        .cta-group {
            margin-top: 2.25rem;
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .cta-group a {
            text-decoration: none;
            padding: 0.95rem 1.8rem;
            border-radius: 999px;
            font-weight: 600;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
        }

        .cta-primary {
            background: linear-gradient(135deg, #0ea5e9, #2563eb);
            color: white;
            box-shadow: 0 18px 32px rgba(14, 165, 233, 0.25);
        }

        .cta-secondary {
            background: white;
            color: #0f172a;
            border: 1px solid rgba(148, 163, 184, 0.4);
        }

        .cta-group a:hover {
            transform: translateY(-1px);
        }

        .overview {
            margin-top: 3rem;
            display: grid;
            gap: 1.5rem;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        }

        .overview-card {
            background: white;
            border-radius: 18px;
            padding: 1.8rem;
            border: 1px solid rgba(148, 163, 184, 0.25);
            box-shadow: 0 24px 48px rgba(15, 23, 42, 0.08);
        }

        .overview-card strong {
            display: block;
            font-size: 1.1rem;
            color: #0f172a;
            margin-bottom: 0.75rem;
        }

        .overview-card p {
            color: #4b5563;
            font-size: 0.98rem;
            line-height: 1.45;
        }

        .integrations {
            margin: 3rem 0;
            padding: 1.75rem;
            background: rgba(14, 165, 233, 0.1);
            border-radius: 18px;
            border: 1px solid rgba(14, 165, 233, 0.3);
        }

        .integrations h2 {
            font-size: 1rem;
            text-transform: uppercase;
            letter-spacing: 0.22em;
            color: #0ea5e9;
            margin-bottom: 1.25rem;
        }

        .integration-list {
            display: flex;
            flex-wrap: wrap;
            gap: 0.85rem 1.25rem;
        }

        .integration-list span {
            background: white;
            padding: 0.55rem 1.1rem;
            border-radius: 999px;
            color: #0369a1;
            font-weight: 600;
            border: 1px solid rgba(14, 165, 233, 0.3);
        }

        footer {
            padding: 2rem;
            text-align: center;
            color: #64748b;
            font-size: 0.95rem;
        }

        @media (max-width: 768px) {
            header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .cta-group {
                flex-direction: column;
                align-items: flex-start;
            }

            .hero {
                padding: 3.5rem 1.5rem 4.5rem;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="logo" style="display:flex;align-items:center;gap:0.75rem;">
            <div style="width:38px;height:38px;border-radius:12px;background:linear-gradient(135deg,#0ea5e9,#22c55e);"></div>
            <span style="font-weight:700;font-size:1.1rem;">Monitoring Logistik Gudang</span>
        </div>
        <nav>
            <a href="#features">Fitur</a>
            <a href="#integrations">Integrasi</a>
            <a href="mailto:sales@wms.co.id">Hubungi Kami</a>
        </nav>
        <div class="header-actions">
            @auth
                @php
                    $dashboardRoute = auth()->user()->hasRole('admin_gudang')
                        ? route('admin.dashboard')
                        : route('user.dashboard');
                @endphp
                <a class="header-btn primary" href="{{ $dashboardRoute }}">Buka Panel</a>
                <form action="{{ route('logout') }}" method="POST" style="margin:0;">
                    @csrf
                    <button type="submit" class="header-btn" style="border:none;background:white;">Keluar</button>
                </form>
            @else
                <a class="header-btn" href="{{ route('login') }}">Masuk</a>
            @endauth
        </div>
    </header>

    <section class="hero">
        <div class="hero-content">
            <span class="badge">Realtime Warehouse Intelligence</span>
            <h1>Orkestrasi <span>Inbound</span> sampai <span>PoD</span> dalam satu platform.</h1>
            <p class="lead">
                Dashboard Monitoring Logistik Gudang menghadirkan kontrol penuh atas stok, inbound/outbound, dan scan device Anda – terhubung dengan TiDB Cloud dan Ably untuk pengalaman realtime tanpa hambatan.
            </p>
            <div class="cta-group">
                <a class="cta-primary" href="mailto:sales@wms.co.id">
                    Mulai Demo
                    <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M5.25 12a.75.75 0 0 1 .75-.75h9.69l-2.72-2.72a.75.75 0 1 1 1.06-1.06l4 4a.75.75 0 0 1 0 1.06l-4 4a.75.75 0 1 1-1.06-1.06l2.72-2.72H6a.75.75 0 0 1-.75-.75Z" />
                    </svg>
                </a>
                <a class="cta-secondary" href="#integrations">Lihat Integrasi</a>
            </div>

            <div id="features" class="overview">
                <article class="overview-card">
                    <strong>Inbound & Putaway</strong>
                    <p>Validasi PO, buat lot otomatis, dan posting GRN dengan idempotensi sehingga retry aman.</p>
                </article>
                <article class="overview-card">
                    <strong>Outbound Workflow</strong>
                    <p>Alokasi, picking, packing hingga PoD dapat dipantau oleh admin gudang dan driver.</p>
                </article>
                <article class="overview-card">
                    <strong>StockService</strong>
                    <p>Satu pintu perubahan stok dengan logging movement lengkap untuk audit dan analitik.</p>
                </article>
            </div>
        </div>

        <div id="integrations" class="integrations">
            <h2>Integrasi utama</h2>
            <div class="integration-list">
                <span>TiDB Cloud (MySQL + TLS)</span>
                <span>Ably Broadcasting</span>
                <span>Scan Device API</span>
                <span>Sanctum Auth</span>
                <span>Zapier & Make</span>
                <span>Pest Testing</span>
            </div>
        </div>
    </section>

    <footer>
        © {{ now()->year }} Monitoring Logistik Gudang – PT Orkestra Digital Indonesia
    </footer>
</body>
</html>
