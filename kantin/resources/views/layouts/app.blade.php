<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>@yield('title', 'Kantin') | Kantin</title>
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
    </head>
    <body>
        <header class="site-header">
            <div class="container header-content">
                <a class="brand" href="{{ route('menu.index') }}">
                    <span class="brand-mark">K</span>
                    <span>Kantin<span class="brand-dot">.</span></span>
                </a>
                <nav class="main-nav" aria-label="Navigasi utama">
                    <a href="{{ route('menu.index') }}">Menu</a>
                    @auth
                        @if (auth()->user()->role === 'student')
                            <a href="{{ route('cart.index') }}">Keranjang</a>
                            <a href="{{ route('orders.index') }}">Pesanan</a>
                        @elseif (auth()->user()->role === 'merchant')
                            <a href="{{ route('merchant.dashboard') }}">Dashboard</a>
                            <a href="{{ route('merchant.products.index') }}">Menu saya</a>
                            <a href="{{ route('merchant.orders.index') }}">Pesanan</a>
                            <a href="{{ route('merchant.settings.edit') }}">Pengaturan</a>
                        @else
                            <a href="{{ route('admin.dashboard') }}">Dashboard admin</a>
                            <a href="{{ route('admin.users.index') }}">Pengguna</a>
                            <a href="{{ route('admin.qrcodes.index') }}">QR menu</a>
                        @endif
                        <details class="notifications-menu">
                            <summary>Notifikasi <span>{{ auth()->user()->appNotifications()->whereNull('read_at')->count() }}</span></summary>
                            <div class="notifications-popover">
                                @forelse (auth()->user()->appNotifications()->latest()->limit(5)->get() as $notification)
                                    <form action="{{ route('notifications.read', $notification) }}" method="POST">
                                        @csrf
                                        <button class="notification-item {{ $notification->read_at ? 'notification-read' : '' }}" type="submit">
                                            <strong>{{ $notification->data['message'] ?? 'Pembaruan KantinMuh' }}</strong>
                                            <small>{{ $notification->created_at->diffForHumans() }}</small>
                                        </button>
                                    </form>
                                @empty
                                    <p class="notification-empty">Belum ada notifikasi.</p>
                                @endforelse
                            </div>
                        </details>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button class="nav-button" type="submit">Keluar</button>
                        </form>
                    @else
                        <a href="{{ route('register') }}">Daftar siswa</a>
                        <a class="nav-login" href="{{ route('login') }}">Masuk</a>
                    @endauth
                </nav>
            </div>
        </header>

        <main class="container page-content">
            @if (session('status'))
                <div class="alert alert-success" role="status">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert-error" role="alert">{{ session('error') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert alert-error" role="alert">
                    <p>Periksa kembali data yang dimasukkan.</p>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            @yield('content')
        </main>

        <footer class="site-footer">
            <div class="container">KantinMuh · Pesan, bayar QRIS, dan ambil tanpa antre.</div>
        </footer>
    </body>
</html>
