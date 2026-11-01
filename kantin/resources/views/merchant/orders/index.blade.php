@extends('layouts.app')

@section('title', 'Pesanan Kantin')

@section('content')
    <section class="management-page">
        <div class="section-heading"><div><p class="eyebrow">Kantin Anda</p><h1>Pesanan masuk</h1></div><a class="button button-muted" href="{{ route('merchant.dashboard') }}">Dashboard</a></div>
        <form class="filter-bar" method="GET">
            <label class="category-field"><span class="sr-only">Filter status</span><select name="status"><option value="">Semua status</option>@foreach (['awaiting_merchant' => 'Perlu verifikasi', 'processing' => 'Diproses', 'ready_for_pickup' => 'Siap diambil', 'completed' => 'Selesai', 'cancelled' => 'Dibatalkan', 'expired' => 'Kedaluwarsa'] as $value => $label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>@endforeach</select></label>
            <button class="button button-primary" type="submit">Terapkan</button>
        </form>
        <div class="table-card">
            <table><thead><tr><th>Nomor pesanan</th><th>Siswa</th><th>Waktu</th><th>Jumlah</th><th>Nominal QRIS</th><th>Status</th><th></th></tr></thead><tbody>
                @forelse ($orders as $order)
                    <tr><td><strong>{{ $order->order_number }}</strong></td><td>{{ $order->user->name }}</td><td>{{ $order->created_at->format('d M, H:i') }}</td><td>{{ $order->items_count ?? $order->items()->count() }} item</td><td><strong>Rp{{ number_format((float) $order->total, 0, ',', '.') }}</strong></td><td><span class="status-pill status-{{ $order->order_status }}">{{ str($order->order_status)->replace('_', ' ')->title() }}</span></td><td><a class="table-link" href="{{ route('merchant.orders.show', $order) }}">Buka</a></td></tr>
                @empty
                    <tr><td class="table-empty" colspan="7">Belum ada pesanan dengan status ini.</td></tr>
                @endforelse
            </tbody></table>
        </div>
        <div class="pagination-wrap">{{ $orders->links() }}</div>
    </section>
@endsection
