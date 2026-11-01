@extends('layouts.app')

@section('title', 'Laporan KantinMuh')

@section('content')
    <section class="management-page">
        <div class="section-heading"><div><p class="eyebrow">Semua kantin</p><h1>Laporan transaksi</h1></div><a class="button button-muted" href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <form class="filter-bar" method="GET"><label class="field"><span>Dari</span><input type="date" name="start" value="{{ $start->toDateString() }}"></label><label class="field"><span>Sampai</span><input type="date" name="end" value="{{ $end->toDateString() }}"></label><button class="button button-primary" type="submit">Terapkan</button></form>
        <div class="stat-grid"><div class="stat-card"><span>Penjualan terverifikasi</span><strong>Rp{{ number_format((float) $revenue, 0, ',', '.') }}</strong></div></div>
        <div class="table-card"><table><thead><tr><th>Nomor</th><th>Kantin</th><th>Siswa</th><th>Status</th><th>Dibayar</th><th>Total</th></tr></thead><tbody>@forelse ($orders as $order)<tr><td>{{ $order->order_number }}</td><td>{{ $order->merchantProfile?->stall_name ?? 'Kantin' }}</td><td>{{ $order->user?->name ?? '—' }}</td><td>{{ str($order->order_status)->replace('_', ' ')->title() }}</td><td>{{ $order->paid_at?->format('d M Y, H:i') ?? '—' }}</td><td>Rp{{ number_format((float) $order->total, 0, ',', '.') }}</td></tr>@empty<tr><td colspan="6" class="table-empty">Tidak ada transaksi pada periode ini.</td></tr>@endforelse</tbody></table></div>
        <div class="pagination-wrap">{{ $orders->links() }}</div>
    </section>
@endsection
