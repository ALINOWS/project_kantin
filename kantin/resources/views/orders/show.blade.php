@extends('layouts.app')

@section('title', 'Pesanan '.$order->order_number)

@section('content')
    <section class="management-page">
        <a class="back-link" href="{{ route('orders.index') }}">← Kembali ke pesanan</a>
        <div class="order-heading">
            <div><p class="eyebrow">{{ $order->merchantProfile?->stall_name }}</p><h1>{{ $order->order_number }}</h1></div>
            <span class="status-pill status-{{ $order->order_status }}">{{ str($order->order_status)->replace('_', ' ')->title() }}</span>
        </div>

        <div class="order-layout">
            <div class="form-card order-items-card">
                <h2>Ringkasan pesanan</h2>
                @foreach ($order->items as $item)
                    <div class="order-line"><span>{{ $item->quantity }} × {{ $item->product_name }}</span><strong>Rp{{ number_format((float) $item->subtotal, 0, ',', '.') }}</strong></div>
                @endforeach
                @if ($order->notes)
                    <p class="order-note"><strong>Catatan:</strong> {{ $order->notes }}</p>
                @endif
                <div class="order-line order-subtotal"><span>Subtotal</span><strong>Rp{{ number_format((float) $order->subtotal, 0, ',', '.') }}</strong></div>
                @if ($order->unique_code)
                    <div class="order-line"><span>Kode unik QRIS</span><strong>+ Rp{{ number_format($order->unique_code, 0, ',', '.') }}</strong></div>
                @endif
                <div class="order-line order-total"><span>Total persis</span><strong>Rp{{ number_format((float) $order->total, 0, ',', '.') }}</strong></div>
            </div>

            @if ($order->order_status === 'pending_payment' && $order->payment_status === 'pending')
                <div class="form-card payment-card">
                    <p class="eyebrow">Bayar dengan QRIS</p>
                    <h2>Bayar tepat sesuai nominal</h2>
                    <p class="form-intro">Pindai QRIS milik kantin dan bayar hingga tiga digit kode unik. Penjual akan mencocokkan pembayaran secara manual.</p>
                    @if ($order->merchantProfile?->qris_image)
                        <img class="qris-image" src="{{ Storage::disk('public')->url($order->merchantProfile->qris_image) }}" alt="QRIS {{ $order->merchantProfile->stall_name }}">
                    @endif
                    <div class="amount-highlight">Rp{{ number_format((float) $order->total, 0, ',', '.') }}</div>
                    @if ($order->expires_at)
                        <p class="expiry-copy">Batas pembayaran: <strong>{{ $order->expires_at->format('d M Y, H:i') }}</strong></p>
                    @endif
                    <div class="alert alert-warning">Pastikan pembayaran berhasil sebelum menekan tombol berikut.</div>
                    <form action="{{ route('orders.claim-payment', $order) }}" method="POST">
                        @csrf
                        <button class="button button-primary button-wide" type="submit">Saya Sudah Bayar</button>
                    </form>
                </div>
            @elseif ($order->order_status === 'awaiting_merchant')
                <div class="form-card payment-card">
                    <span class="status-pill status-pending">Menunggu verifikasi</span>
                    <h2>Klaim pembayaran terkirim</h2>
                    <p class="form-intro">Penjual sedang mencocokkan nominal Rp{{ number_format((float) $order->total, 0, ',', '.') }} pada notifikasi QRIS.</p>
                    @if ($order->paid_claimed_at)<p class="expiry-copy">Dikirim {{ $order->paid_claimed_at->format('d M Y, H:i') }}</p>@endif
                </div>
            @elseif ($order->order_status === 'processing')
                <div class="form-card payment-card"><span class="status-pill status-processing">Pembayaran terverifikasi</span><h2>Pesanan sedang disiapkan</h2><p class="form-intro">Penjual akan memberikan kode pengambilan saat pesanan siap.</p></div>
            @elseif ($order->order_status === 'ready_for_pickup')
                <div class="form-card pickup-card"><p class="eyebrow">Tunjukkan ke penjual</p><h2>Kode pengambilan</h2><strong class="pickup-code">{{ $order->pickup_code }}</strong><p>Satu kali pakai. Kode hanya berlaku untuk pesanan ini.</p></div>
            @elseif ($order->order_status === 'completed')
                <div class="form-card payment-card"><span class="status-pill status-completed">Pesanan selesai</span><h2>Terima kasih!</h2><p class="form-intro">Pesanan diambil pada {{ $order->completed_at?->format('d M Y, H:i') }}.</p></div>
            @else
                <div class="form-card payment-card"><span class="status-pill status-inactive">{{ str($order->order_status)->replace('_', ' ')->title() }}</span><h2>Pesanan tidak aktif</h2><p class="form-intro">Pesanan kedaluwarsa atau dibatalkan. Hubungi kantin bila Anda telah membayar.</p></div>
            @endif
        </div>

        @if ($order->order_status === 'completed' && $order->payment_status === 'paid' && ! $order->review)
            <div class="form-card review-card">
                <p class="eyebrow">Bagikan pengalaman</p><h2>Beri ulasan</h2>
                <form action="{{ route('orders.review', $order) }}" method="POST" class="form-stack">
                    @csrf
                    <label class="field"><span>Rating</span><select name="rating" required><option value="">Pilih rating</option>@for ($rating = 5; $rating >= 1; $rating--)<option value="{{ $rating }}">{{ $rating }} bintang</option>@endfor</select></label>
                    <label class="field"><span>Komentar <small>(opsional)</small></span><textarea name="comment" rows="3" maxlength="2000"></textarea></label>
                    <button class="button button-primary" type="submit">Kirim ulasan</button>
                </form>
            </div>
        @elseif ($order->review)
            <div class="form-card review-card"><h2>Ulasan Anda</h2><p>{{ $order->review->rating }} / 5 bintang</p><p>{{ $order->review->comment }}</p></div>
        @endif
    </section>
@endsection
