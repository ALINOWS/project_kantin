@extends('layouts.app')

@section('title', 'Dashboard Admin')

@section('content')
    <section class="management-page">
        <div class="section-heading"><div><p class="eyebrow">KantinMuh</p><h1>Dashboard admin</h1></div><a class="button button-primary" href="{{ route('admin.users.index') }}">Tinjau pengguna</a></div>
        <div class="stat-grid">
            <a class="stat-card" href="{{ route('admin.users.index') }}"><span>Total pengguna</span><strong>{{ $userCount }}</strong></a>
            <a class="stat-card stat-attention" href="{{ route('admin.users.index', ['role' => 'merchant']) }}"><span>Pendaftaran kantin menunggu</span><strong>{{ $pendingMerchants }}</strong></a>
            <a class="stat-card" href="{{ route('admin.reports') }}"><span>Pesanan hari ini</span><strong>{{ $todayOrders }}</strong></a>
            <a class="stat-card" href="{{ route('admin.reports') }}"><span>Klaim pembayaran menunggu</span><strong>{{ $pendingPayments }}</strong></a>
        </div>
        <div class="quick-links">
            <a class="button button-muted" href="{{ route('admin.qrcodes.index') }}">Kelola QR menu</a>
            <a class="button button-muted" href="{{ route('admin.reports') }}">Laporan semua kantin</a>
            <a class="button button-muted" href="{{ route('admin.audit') }}">Audit log</a>
            <a class="button button-muted" href="{{ route('manage.categories.index') }}">Kategori menu</a>
        </div>
        <div class="section-heading subsection-heading"><h2>Aktivitas terbaru</h2><a class="table-link" href="{{ route('admin.audit') }}">Lihat audit log</a></div>
        <div class="table-card"><table><thead><tr><th>Waktu</th><th>Pengguna</th><th>Aktivitas</th><th>Data</th></tr></thead><tbody>@forelse ($recentActivity as $log)<tr><td>{{ $log->created_at?->format('d M, H:i') }}</td><td>{{ $log->user?->name ?? 'Sistem' }}</td><td>{{ str($log->action)->replace('.', ' ')->title() }}</td><td>{{ $log->entity_type }} #{{ $log->entity_id }}</td></tr>@empty<tr><td colspan="4" class="table-empty">Aktivitas akan dicatat di sini.</td></tr>@endforelse</tbody></table></div>
    </section>
@endsection
