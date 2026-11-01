@extends('layouts.app')

@section('title', 'Laporan Penjualan')

@section('content')
    <section class="management-page">
        <div class="section-heading"><div><p class="eyebrow">Analisis kantin</p><h1>Laporan penjualan</h1></div><a class="button button-muted" href="{{ route('merchant.dashboard') }}">Dashboard</a></div>
        <form class="filter-bar" method="GET"><label class="field"><span>Dari</span><input type="date" name="start" value="{{ $start->toDateString() }}"></label><label class="field"><span>Sampai</span><input type="date" name="end" value="{{ $end->toDateString() }}"></label><button class="button button-primary" type="submit">Terapkan</button></form>
        <div class="stat-grid"><div class="stat-card"><span>Transaksi dibayar</span><strong>{{ $salesCount }}</strong></div><div class="stat-card"><span>Total penjualan</span><strong>Rp{{ number_format((float) $revenue, 0, ',', '.') }}</strong></div></div>
        <div class="form-card report-card"><h2>Menu terlaris</h2>@forelse ($topProducts as $product)<div class="order-line"><span>{{ $product->name }}</span><strong>{{ $product->quantity_sold ?? 0 }} terjual</strong></div>@empty<p class="form-intro">Belum ada penjualan pada periode ini.</p>@endforelse</div>
        <h2 class="subsection-title">Transaksi</h2>
        <div class="table-card"><table><thead><tr><th>Nomor</th><th>Siswa</th><th>Dibayar</th><th>Total</th></tr></thead><tbody>@forelse ($orders as $order)<tr><td>{{ $order->order_number }}</td><td>{{ $order->user->name }}</td><td>{{ $order->paid_at?->format('d M Y, H:i') }}</td><td>Rp{{ number_format((float) $order->total, 0, ',', '.') }}</td></tr>@empty<tr><td colspan="4" class="table-empty">Belum ada transaksi.</td></tr>@endforelse</tbody></table></div>
        <div class="pagination-wrap">{{ $orders->links() }}</div>
    </section>
@endsection
