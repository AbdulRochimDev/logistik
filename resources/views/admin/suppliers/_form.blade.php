@php($isEdit = isset($supplier))
<form method="POST" action="{{ $isEdit ? route('admin.suppliers.update', $supplier) : route('admin.suppliers.store') }}" class="card" style="display:grid;gap:1.25rem;">
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    <div style="display:grid;gap:1rem;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));">
        <div>
            <label for="code" style="font-weight:600;display:block;margin-bottom:0.35rem;">Kode</label>
            <input id="code" name="code" type="text" value="{{ old('code', $supplier->code ?? '') }}" required style="width:100%;padding:0.8rem 1rem;border-radius:12px;border:1px solid rgba(148,163,184,0.35);" />
            @error('code')
                <p style="color:#b91c1c;font-size:0.9rem;margin-top:0.4rem;">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="name" style="font-weight:600;display:block;margin-bottom:0.35rem;">Nama</label>
            <input id="name" name="name" type="text" value="{{ old('name', $supplier->name ?? '') }}" required style="width:100%;padding:0.8rem 1rem;border-radius:12px;border:1px solid rgba(148,163,184,0.35);" />
            @error('name')
                <p style="color:#b91c1c;font-size:0.9rem;margin-top:0.4rem;">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="contact_name" style="font-weight:600;display:block;margin-bottom:0.35rem;">Contact</label>
            <input id="contact_name" name="contact_name" type="text" value="{{ old('contact_name', $supplier->contact_name ?? '') }}" style="width:100%;padding:0.8rem 1rem;border-radius:12px;border:1px solid rgba(148,163,184,0.35);" />
            @error('contact_name')
                <p style="color:#b91c1c;font-size:0.9rem;margin-top:0.4rem;">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="phone" style="font-weight:600;display:block;margin-bottom:0.35rem;">Telepon</label>
            <input id="phone" name="phone" type="text" value="{{ old('phone', $supplier->phone ?? '') }}" style="width:100%;padding:0.8rem 1rem;border-radius:12px;border:1px solid rgba(148,163,184,0.35);" />
            @error('phone')
                <p style="color:#b91c1c;font-size:0.9rem;margin-top:0.4rem;">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="email" style="font-weight:600;display:block;margin-bottom:0.35rem;">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email', $supplier->email ?? '') }}" style="width:100%;padding:0.8rem 1rem;border-radius:12px;border:1px solid rgba(148,163,184,0.35);" />
            @error('email')
                <p style="color:#b91c1c;font-size:0.9rem;margin-top:0.4rem;">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div>
        <label for="address" style="font-weight:600;display:block;margin-bottom:0.35rem;">Alamat</label>
        <textarea id="address" name="address" rows="3" style="width:100%;padding:0.8rem 1rem;border-radius:12px;border:1px solid rgba(148,163,184,0.35);">{{ old('address', $supplier->address ?? '') }}</textarea>
        @error('address')
            <p style="color:#b91c1c;font-size:0.9rem;margin-top:0.4rem;">{{ $message }}</p>
        @enderror
    </div>

    <div style="display:flex;gap:1rem;">
        <button type="submit" class="btn btn-primary">Simpan</button>
        <a href="{{ route('admin.suppliers.index') }}" class="btn btn-neutral">Batal</a>
    </div>
</form>
