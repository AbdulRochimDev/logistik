@php($isEdit = isset($vehicle))
<form method="POST" action="{{ $isEdit ? route('admin.vehicles.update', $vehicle) : route('admin.vehicles.store') }}" class="card" style="display:grid;gap:1.25rem;">
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    <div style="display:grid;gap:1rem;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));">
        <div>
            <label for="plate_no" style="font-weight:600;display:block;margin-bottom:0.35rem;">Plat Nomor</label>
            <input id="plate_no" type="text" name="plate_no" value="{{ old('plate_no', $vehicle->plate_no ?? '') }}" required style="padding:0.8rem 1rem;border-radius:12px;border:1px solid rgba(148,163,184,0.35);width:100%;" />
            @error('plate_no')
                <p style="color:#b91c1c;font-size:0.9rem;margin-top:0.4rem;">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="type" style="font-weight:600;display:block;margin-bottom:0.35rem;">Tipe</label>
            <input id="type" type="text" name="type" value="{{ old('type', $vehicle->type ?? '') }}" style="padding:0.8rem 1rem;border-radius:12px;border:1px solid rgba(148,163,184,0.35);width:100%;" />
            @error('type')
                <p style="color:#b91c1c;font-size:0.9rem;margin-top:0.4rem;">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="capacity" style="font-weight:600;display:block;margin-bottom:0.35rem;">Kapasitas</label>
            <input id="capacity" type="number" step="0.1" min="0" name="capacity" value="{{ old('capacity', $vehicle->capacity ?? '') }}" style="padding:0.8rem 1rem;border-radius:12px;border:1px solid rgba(148,163,184,0.35);width:100%;" />
            @error('capacity')
                <p style="color:#b91c1c;font-size:0.9rem;margin-top:0.4rem;">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="status" style="font-weight:600;display:block;margin-bottom:0.35rem;">Status</label>
            <select id="status" name="status" style="padding:0.8rem 1rem;border-radius:12px;border:1px solid rgba(148,163,184,0.35);width:100%;">
                <option value="active" @selected(old('status', $vehicle->status ?? 'active') === 'active')>Aktif</option>
                <option value="inactive" @selected(old('status', $vehicle->status ?? 'active') === 'inactive')>Nonaktif</option>
            </select>
            @error('status')
                <p style="color:#b91c1c;font-size:0.9rem;margin-top:0.4rem;">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div style="display:flex;gap:1rem;">
        <button type="submit" class="btn btn-primary">Simpan</button>
        <a href="{{ route('admin.vehicles.index') }}" class="btn btn-neutral">Batal</a>
    </div>
</form>
