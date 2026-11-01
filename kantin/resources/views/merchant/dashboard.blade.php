@extends('layouts.app')

@section('title', 'Dashboard Penjual')

@section('content')
    <section class="management-page">
        <div class="section-heading">
            <div><p class="eyebrow">{{ auth()->user()->merchantProfile->stall_name }}</p><h1>Dashboard penjual</h1></div>
            <a class="button button-muted" href="{{ route('merchant.settings.edit') }}">Pengaturan kantin</a>
        </div>
        <div class="stat-grid">
            <a class="stat-card stat-attention" href="{{ route('merchant.orders.index', ['status' => 'awaiting_merchant']) }}"><span>Pembayaran perlu dicek</span><strong>{{ $newOrders }}</strong></a>
            <a class="stat-card" href="{{ route('merchant.orders.index', ['status' => 'processing']) }}"><span>Sedang diproses</span><strong>{{ $processingOrders }}</strong></a>
            <a class="stat-card" href="{{ route('merchant.orders.index', ['status' => 'ready_for_pickup']) }}"><span>Siap diambil</span><strong>{{ $readyOrders }}</strong></a>
            <div class="stat-card"><span>Selesai hari ini</span><strong>{{ $completedToday }}</strong></div>
        </div>
        <div class="section-heading subsection-heading"><h2>Pesanan terbaru</h2><a class="table-link" href="{{ route('merchant.orders.index') }}">Lihat semua</a></div>
        <div class="table-card">
            <table><thead><tr><th>Nomor</th><th>Siswa</th><th>Waktu</th><th>Total</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse ($recentOrders as $order)
                        <tr><td><strong>{{ $order->order_number }}</strong></td><td>{{ $order->user->name }}</td><td>{{ $order->created_at->format('d M, H:i') }}</td><td>Rp{{ number_format((float) $order->total, 0, ',', '.') }}</td><td><span class="status-pill status-{{ $order->order_status }}">{{ str($order->order_status)->replace('_', ' ')->title() }}</span></td><td><a class="table-link" href="{{ route('merchant.orders.show', $order) }}">Kelola</a></td></tr>
                    @empty
                        <tr><td colspan="6" class="table-empty">Belum ada pesanan untuk kantin Anda.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="quick-links">
            <a class="button button-primary" href="{{ route('merchant.products.create') }}">+ Tambah menu</a>
            <a class="button button-muted" href="{{ route('merchant.orders.index') }}">Kelola pesanan</a>
            <a class="button button-muted" href="{{ route('merchant.reports') }}">Laporan penjualan</a>
        </div>
    </section>
@endsection
