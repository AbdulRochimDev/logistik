@php($isEdit = isset($driver))
<form method="POST" action="{{ $isEdit ? route('admin.drivers.update', $driver) : route('admin.drivers.store') }}" class="card" style="display:grid;gap:1.25rem;">
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    <div style="display:grid;gap:1rem;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));">
        <div>
            <label for="name" style="font-weight:600;display:block;margin-bottom:0.35rem;">Nama</label>
            <input id="name" type="text" name="name" value="{{ old('name', $driver->name ?? '') }}" required style="padding:0.8rem 1rem;border-radius:12px;border:1px solid rgba(148,163,184,0.35);width:100%;" />
            @error('name')
                <p style="color:#b91c1c;font-size:0.9rem;margin-top:0.4rem;">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="phone" style="font-weight:600;display:block;margin-bottom:0.35rem;">Telepon</label>
            <input id="phone" type="text" name="phone" value="{{ old('phone', $driver->phone ?? '') }}" style="padding:0.8rem 1rem;border-radius:12px;border:1px solid rgba(148,163,184,0.35);width:100%;" />
            @error('phone')
                <p style="color:#b91c1c;font-size:0.9rem;margin-top:0.4rem;">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="license_no" style="font-weight:600;display:block;margin-bottom:0.35rem;">No. Lisensi</label>
            <input id="license_no" type="text" name="license_no" value="{{ old('license_no', $driver->license_no ?? '') }}" style="padding:0.8rem 1rem;border-radius:12px;border:1px solid rgba(148,163,184,0.35);width:100%;" />
            @error('license_no')
                <p style="color:#b91c1c;font-size:0.9rem;margin-top:0.4rem;">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="status" style="font-weight:600;display:block;margin-bottom:0.35rem;">Status</label>
            <select id="status" name="status" style="padding:0.8rem 1rem;border-radius:12px;border:1px solid rgba(148,163,184,0.35);width:100%;">
                <option value="active" @selected(old('status', $driver->status ?? 'active') === 'active')>Aktif</option>
                <option value="inactive" @selected(old('status', $driver->status ?? 'active') === 'inactive')>Nonaktif</option>
            </select>
            @error('status')
                <p style="color:#b91c1c;font-size:0.9rem;margin-top:0.4rem;">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div style="display:flex;gap:1rem;">
        <button type="submit" class="btn btn-primary">Simpan</button>
        <a href="{{ route('admin.drivers.index') }}" class="btn btn-neutral">Batal</a>
    </div>
</form>
