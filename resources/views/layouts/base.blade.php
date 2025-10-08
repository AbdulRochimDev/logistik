<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    @php
        /** @var \App\Models\User|null $currentUser */
        $currentUser = auth()->user();
        $dashboardHref = null;
        $roleLabel = null;
        $initials = null;

        /** @var string $mainClass */
        $mainClass = $mainClass ?? '';

        if ($currentUser) {
            $dashboardHref = $currentUser->hasRole('admin_gudang')
                ? route('admin.dashboard')
                : route('user.dashboard');

            $roleLabel = $currentUser->roles
                ->pluck('name')
                ->map(fn (string $role) => str_replace('_', ' ', $role))
                ->implode(', ');

            $initials = strtoupper(mb_substr($currentUser->name ?? $currentUser->email, 0, 1));
        }
    @endphp
    <style>
        :root {
            font-family: 'Poppins', sans-serif;
            color: #0f172a;
        }

        body {
            margin: 0;
            min-height: 100vh;
            background: #f8fafc;
            display: flex;
            flex-direction: column;
        }

        header {
            padding: 1.25rem 2.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: rgba(248, 250, 252, 0.85);
            border-bottom: 1px solid rgba(148, 163, 184, 0.25);
            backdrop-filter: blur(12px);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 700;
            color: #0f172a;
            text-decoration: none;
        }

        .brand-badge {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            background: linear-gradient(135deg, #0ea5e9, #22c55e);
        }

        nav {
            display: flex;
            align-items: center;
            gap: 1.35rem;
        }

        nav a {
            text-decoration: none;
            font-weight: 600;
            color: #0f172a;
        }

        .nav-dashboard {
            color: #0ea5e9;
        }

        .auth-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .btn {
            padding: 0.65rem 1.4rem;
            border-radius: 999px;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }

        .btn-primary {
            background: linear-gradient(135deg, #0ea5e9, #2563eb);
            color: #fff;
            box-shadow: 0 12px 24px rgba(14, 165, 233, 0.25);
        }

        .btn-neutral {
            background: white;
            color: #0f172a;
            border: 1px solid rgba(148, 163, 184, 0.4);
        }

        .btn:hover {
            transform: translateY(-1px);
        }

        .user-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.65rem;
            padding: 0.45rem 0.85rem;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 999px;
            border: 1px solid rgba(148, 163, 184, 0.35);
        }

        .user-avatar {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: linear-gradient(135deg, #0ea5e9, #22c55e);
            color: white;
            display: grid;
            place-items: center;
            font-weight: 700;
        }

        .user-meta {
            display: flex;
            flex-direction: column;
            line-height: 1.15;
        }

        .user-name {
            font-weight: 600;
        }

        .user-role {
            font-size: 0.75rem;
            text-transform: uppercase;
            color: #64748b;
        }

        main {
            flex: 1;
            padding: 2.5rem 1.75rem 3.5rem;
            display: flex;
            justify-content: center;
        }

        .layout-wide {
            justify-content: flex-start;
        }

        footer {
            padding: 1.5rem;
            text-align: center;
            color: #64748b;
            font-size: 0.95rem;
        }

        .container-narrow {
            width: min(100%, 520px);
        }

        @media (max-width: 768px) {
            header {
                flex-direction: column;
                gap: 1rem;
            }

            nav {
                flex-wrap: wrap;
                justify-content: center;
            }
        }
    </style>
    @stack('styles')
</head>
<body>
<header>
    <a href="{{ route('home') }}" class="brand">
        <span class="brand-badge"></span>
        <span>Monitoring Logistik Gudang</span>
    </a>
    <nav>
        <a href="{{ route('home') }}">Beranda</a>
        <a href="{{ route('home') }}#features">Fitur</a>
        <a href="{{ route('home') }}#integrations">Integrasi</a>
        @auth
            <a href="{{ $dashboardHref }}" class="nav-dashboard">Panel</a>
        @endauth
    </nav>
    <div class="auth-actions">
        @auth
            <span class="user-chip">
                <span class="user-avatar">{{ $initials }}</span>
                <span class="user-meta">
                    <span class="user-name">{{ $currentUser->name ?? $currentUser->email }}</span>
                    @if(! empty($roleLabel))
                        <span class="user-role">{{ $roleLabel }}</span>
                    @endif
                </span>
            </span>
            <form action="{{ route('logout') }}" method="POST" style="display:inline;">
                @csrf
                <button class="btn btn-neutral" type="submit">Keluar</button>
            </form>
        @else
            <a class="btn btn-neutral" href="{{ route('login') }}">Masuk</a>
        @endauth
    </div>
</header>

<main class="{{ trim('layout-main ' . $mainClass) }}">
    @yield('content')
</main>

<footer>
    © {{ now()->year }} Monitoring Logistik Gudang · PT Orkestra Digital Indonesia
</footer>
@stack('scripts')
</body>
</html>
