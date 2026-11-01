@extends('layouts.app')

@section('title', 'Audit Log')

@section('content')
    <section class="management-page">
        <div class="section-heading"><div><p class="eyebrow">Keamanan dan akuntabilitas</p><h1>Audit log</h1></div><a class="button button-muted" href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="table-card"><table><thead><tr><th>Waktu</th><th>Pengguna</th><th>Aktivitas</th><th>Entitas</th><th>Detail</th><th>IP</th></tr></thead><tbody>@forelse ($logs as $log)<tr><td>{{ $log->created_at?->format('d M Y, H:i:s') }}</td><td>{{ $log->user?->name ?? 'Sistem' }}</td><td>{{ str($log->action)->replace('.', ' ')->title() }}</td><td>{{ $log->entity_type }} #{{ $log->entity_id }}</td><td>{{ $log->details ? json_encode($log->details, JSON_UNESCAPED_UNICODE) : '—' }}</td><td>{{ $log->ip_address }}</td></tr>@empty<tr><td class="table-empty" colspan="6">Belum ada aktivitas.</td></tr>@endforelse</tbody></table></div>
        <div class="pagination-wrap">{{ $logs->links() }}</div>
    </section>
@endsection
