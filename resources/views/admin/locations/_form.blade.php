@php($isEdit = isset($location))
<form method="POST" action="{{ $isEdit ? route('admin.locations.update', $location) : route('admin.locations.store') }}" class="card" style="display:grid;gap:1.25rem;">
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    <div style="display:grid;gap:1rem;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));">
        <div>
            <label for="warehouse_id" style="font-weight:600;display:block;margin-bottom:0.35rem;">Gudang</label>
            <select id="warehouse_id" name="warehouse_id" style="width:100%;padding:0.8rem 1rem;border-radius:12px;border:1px solid rgba(148,163,184,0.35);">
                @foreach($warehouses as $warehouse)
                    <option value="{{ $warehouse->id }}" @selected(old('warehouse_id', $location->warehouse_id ?? null) == $warehouse->id)>
                        {{ $warehouse->code }} — {{ $warehouse->name }}
                    </option>
                @endforeach
            </select>
            @error('warehouse_id')
                <p style="color:#b91c1c;font-size:0.9rem;margin-top:0.4rem;">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="code" style="font-weight:600;display:block;margin-bottom:0.35rem;">Kode</label>
            <input id="code" type="text" name="code" value="{{ old('code', $location->code ?? '') }}" required style="width:100%;padding:0.8rem 1rem;border-radius:12px;border:1px solid rgba(148,163,184,0.35);" />
            @error('code')
                <p style="color:#b91c1c;font-size:0.9rem;margin-top:0.4rem;">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="name" style="font-weight:600;display:block;margin-bottom:0.35rem;">Nama</label>
            <input id="name" type="text" name="name" value="{{ old('name', $location->name ?? '') }}" required style="width:100%;padding:0.8rem 1rem;border-radius:12px;border:1px solid rgba(148,163,184,0.35);" />
            @error('name')
                <p style="color:#b91c1c;font-size:0.9rem;margin-top:0.4rem;">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="type" style="font-weight:600;display:block;margin-bottom:0.35rem;">Tipe</label>
            <input id="type" type="text" name="type" value="{{ old('type', $location->type ?? '') }}" required style="width:100%;padding:0.8rem 1rem;border-radius:12px;border:1px solid rgba(148,163,184,0.35);" />
            @error('type')
                <p style="color:#b91c1c;font-size:0.9rem;margin-top:0.4rem;">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <label style="display:flex;align-items:center;gap:0.5rem;font-weight:600;">
        <input type="checkbox" name="is_default" value="1" @checked(old('is_default', $location->is_default ?? false)) />
        Jadikan default untuk gudang ini
    </label>

    <div style="display:flex;gap:1rem;">
        <button type="submit" class="btn btn-primary">Simpan</button>
        <a href="{{ route('admin.locations.index') }}" class="btn btn-neutral">Batal</a>
    </div>
</form>
