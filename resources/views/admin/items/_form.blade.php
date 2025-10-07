@php($isEdit = isset($item))
<form method="POST" action="{{ $isEdit ? route('admin.items.update', $item) : route('admin.items.store') }}" class="card" style="display:grid;gap:1.25rem;">
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    <div style="display:grid;gap:1rem;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));">
        <div>
            <label for="sku" style="font-weight:600;display:block;margin-bottom:0.35rem;">SKU</label>
            <input id="sku" name="sku" type="text" value="{{ old('sku', $item->sku ?? '') }}" required style="width:100%;padding:0.8rem 1rem;border-radius:12px;border:1px solid rgba(148,163,184,0.35);" />
            @error('sku')
                <p style="color:#b91c1c;font-size:0.9rem;margin-top:0.4rem;">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="name" style="font-weight:600;display:block;margin-bottom:0.35rem;">Nama</label>
            <input id="name" name="name" type="text" value="{{ old('name', $item->name ?? '') }}" required style="width:100%;padding:0.8rem 1rem;border-radius:12px;border:1px solid rgba(148,163,184,0.35);" />
            @error('name')
                <p style="color:#b91c1c;font-size:0.9rem;margin-top:0.4rem;">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="default_uom" style="font-weight:600;display:block;margin-bottom:0.35rem;">Default UOM</label>
            <input id="default_uom" name="default_uom" type="text" value="{{ old('default_uom', $item->default_uom ?? '') }}" required style="width:100%;padding:0.8rem 1rem;border-radius:12px;border:1px solid rgba(148,163,184,0.35);" />
            @error('default_uom')
                <p style="color:#b91c1c;font-size:0.9rem;margin-top:0.4rem;">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label style="font-weight:600;display:flex;gap:0.5rem;align-items:center;margin-top:1.9rem;">
                <input type="checkbox" name="is_lot_tracked" value="1" @checked(old('is_lot_tracked', $item->is_lot_tracked ?? false)) />
                Lot Tracked
            </label>
        </div>
    </div>

    <div>
        <label for="description" style="font-weight:600;display:block;margin-bottom:0.35rem;">Deskripsi</label>
        <textarea id="description" name="description" rows="3" style="width:100%;padding:0.8rem 1rem;border-radius:12px;border:1px solid rgba(148,163,184,0.35);">{{ old('description', $item->description ?? '') }}</textarea>
        @error('description')
            <p style="color:#b91c1c;font-size:0.9rem;margin-top:0.4rem;">{{ $message }}</p>
        @enderror
    </div>

    <div style="display:flex;gap:1rem;">
        <button type="submit" class="btn btn-primary">Simpan</button>
        <a href="{{ route('admin.items.index') }}" class="btn btn-neutral">Batal</a>
    </div>
</form>
