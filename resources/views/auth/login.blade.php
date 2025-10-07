@extends('layouts.base', ['title' => 'Masuk · Monitoring Logistik Gudang'])

@section('content')
<div class="container-narrow">
    <div style="background:white;border-radius:18px;padding:2.5rem;border:1px solid rgba(148,163,184,0.25);box-shadow:0 24px 48px rgba(15,23,42,0.08);">
        <h2 style="font-size:1.9rem;margin-bottom:0.75rem;">Selamat datang kembali</h2>
        <p style="color:#64748b;margin-bottom:2rem;">Masuk untuk mengelola operasi gudang dan memantau pergerakan stok.</p>

        @if ($errors->any())
            <div style="background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.35);color:#b91c1c;padding:0.85rem 1.1rem;border-radius:12px;margin-bottom:1.5rem;">
                <strong>Terjadi kesalahan:</strong>
                <ul style="margin:0.5rem 0 0 1rem;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" style="display:grid;gap:1.25rem;">
            @csrf
            <div>
                <label for="email" style="font-weight:600;display:block;margin-bottom:0.4rem;">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                       style="width:100%;padding:0.85rem 1rem;border-radius:12px;border:1px solid rgba(148,163,184,0.35);font-size:1rem;" />
            </div>

            <div>
                <label for="password" style="font-weight:600;display:block;margin-bottom:0.4rem;">Kata Sandi</label>
                <input id="password" name="password" type="password" required
                       style="width:100%;padding:0.85rem 1rem;border-radius:12px;border:1px solid rgba(148,163,184,0.35);font-size:1rem;" />
            </div>

            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:0.75rem;">
                <label style="display:flex;align-items:center;gap:0.5rem;color:#475569;">
                    <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }} /> Ingat saya
                </label>
                <a href="#" style="color:#0ea5e9;text-decoration:none;font-weight:600;">Lupa kata sandi?</a>
            </div>

            <button type="submit" class="btn btn-primary" style="justify-content:center;">Masuk Sekarang</button>
        </form>
    </div>
</div>
@endsection
