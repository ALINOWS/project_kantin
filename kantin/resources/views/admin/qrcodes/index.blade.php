@extends('layouts.app')

@section('title', 'QR Menu Kantin')

@section('content')
    <section class="management-page">
        <div class="section-heading"><div><p class="eyebrow">QR akses katalog</p><h1>QR menu kantin</h1></div><a class="button button-muted" href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="form-card qr-create-card"><h2>Buat QR menu</h2><p class="form-intro">QR ini membuka katalog satu kantin. QR menu berbeda dari QRIS yang digunakan untuk membayar.</p>
            <form action="{{ route('admin.qrcodes.store') }}" method="POST" class="form-stack">@csrf<label class="field"><span>Kantin aktif</span><select name="merchant_id" required><option value="">Pilih kantin</option>@foreach ($merchants as $merchant)<option value="{{ $merchant->id }}">{{ $merchant->stall_name }}</option>@endforeach</select></label><button class="button button-primary" type="submit">Buat QR menu</button></form>
        </div>
        <div class="qr-grid">
            @forelse ($qrCodes as $qrCode)
                <article class="form-card qr-card"><div class="qr-display" data-qr="{{ $qrCode->target_url }}" role="img" aria-label="QR menu {{ $qrCode->merchantProfile?->stall_name }}"></div><h2>{{ $qrCode->merchantProfile?->stall_name ?? 'Kantin nonaktif' }}</h2><span class="status-pill {{ $qrCode->is_active ? 'status-active' : 'status-inactive' }}">{{ $qrCode->is_active ? 'Aktif' : 'Nonaktif' }}</span><label class="field qr-url"><span>URL tujuan</span><input readonly value="{{ $qrCode->target_url }}"></label><button class="button button-muted copy-qr-url" type="button" data-url="{{ $qrCode->target_url }}">Salin URL</button><form action="{{ route('admin.qrcodes.toggle', $qrCode) }}" method="POST">@csrf @method('PATCH')<button class="text-button" type="submit">{{ $qrCode->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button></form></article>
            @empty
                <div class="empty-state"><h3>Belum ada QR menu</h3><p>Buat QR untuk membagikan katalog kantin.</p></div>
            @endforelse
        </div>
        <div class="pagination-wrap">{{ $qrCodes->links() }}</div>
    </section>
@endsection
