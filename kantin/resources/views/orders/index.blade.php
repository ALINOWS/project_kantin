@extends('layouts.app')

@section('title', 'Pesanan Saya')

@section('content')
    <section class="management-page">
        <div class="section-heading">
            <div>
                <p class="eyebrow">Riwayat transaksi</p>
                <h1>Pesanan saya</h1>
            </div>
            <a class="button button-muted" href="{{ route('menu.index') }}">Jelajahi menu</a>
        </div>

        @if ($orders->isEmpty())
            <div class="empty-state">
                <span class="empty-icon" aria-hidden="true">🧾</span>
                <h2>Belum ada pesanan</h2>
                <p>Pesanan yang Anda buat akan muncul di halaman ini.</p>
                <a class="button button-primary" href="{{ route('menu.index') }}">Pilih menu</a>
            </div>
        @else
            <div class="order-history-list">
                @foreach ($orders as $order)
                    @php
                        $statusLabels = [
                            'pending_payment' => 'Menunggu pembayaran',
                            'awaiting_merchant' => 'Menunggu verifikasi',
                            'processing' => 'Sedang disiapkan',
                            'ready_for_pickup' => 'Siap diambil',
                            'completed' => 'Selesai',
                            'cancelled' => 'Dibatalkan',
                            'expired' => 'Kedaluwarsa',
                        ];
                    @endphp
                    <a class="form-card order-history-card" href="{{ route('orders.show', $order) }}">
                        <div class="order-heading">
                            <div>
                                <p class="eyebrow">{{ $order->merchantProfile?->stall_name ?? 'Kantin' }}</p>
                                <h2>{{ $order->order_number }}</h2>
                            </div>
                            <span class="status-pill status-{{ $order->order_status }}">
                                {{ $statusLabels[$order->order_status] ?? str($order->order_status)->replace('_', ' ')->title() }}
                            </span>
                        </div>
                        <div class="order-history-meta">
                            <span>{{ $order->created_at->format('d M Y, H:i') }}</span>
                            <strong>Rp{{ number_format((float) $order->total, 0, ',', '.') }}</strong>
                        </div>
                    </a>
                @endforeach
            </div>
            <div class="pagination-wrap">{{ $orders->links() }}</div>
        @endif
    </section>
@endsection