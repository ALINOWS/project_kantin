@extends('layouts.app')

@section('title', 'Pesanan '.$order->order_number)

@section('content')
    <section class="management-page">
        <a class="back-link" href="{{ route('merchant.orders.index') }}">← Kembali ke pesanan</a>
        <div class="order-heading"><div><p class="eyebrow">{{ $order->user->name }} · {{ $order->created_at->format('d M Y, H:i') }}</p><h1>{{ $order->order_number }}</h1></div><span class="status-pill status-{{ $order->order_status }}">{{ str($order->order_status)->replace('_', ' ')->title() }}</span></div>
        <div class="order-layout">
            <div class="form-card order-items-card">
                <h2>Detail pesanan</h2>
                @foreach ($order->items as $item)<div class="order-line"><span>{{ $item->quantity }} × {{ $item->product_name }}</span><strong>Rp{{ number_format((float) $item->subtotal, 0, ',', '.') }}</strong></div>@endforeach
                @if ($order->notes)<p class="order-note"><strong>Catatan siswa:</strong> {{ $order->notes }}</p>@endif
                <div class="order-line order-subtotal"><span>Subtotal</span><strong>Rp{{ number_format((float) $order->subtotal, 0, ',', '.') }}</strong></div>
                <div class="order-line"><span>Kode unik QRIS</span><strong>+ Rp{{ number_format((int) $order->unique_code, 0, ',', '.') }}</strong></div>
                <div class="order-line order-total"><span>Total harus cocok</span><strong>Rp{{ number_format((float) $order->total, 0, ',', '.') }}</strong></div>
                @if ($order->paid_claimed_at)<p class="expiry-copy">Klaim pembayaran dikirim {{ $order->paid_claimed_at->format('d M Y, H:i') }}</p>@endif
            </div>

            @if ($order->order_status === 'awaiting_merchant')
                <div class="form-card payment-card">
                    <p class="eyebrow">Verifikasi manual QRIS</p><h2>Cocokkan mutasi pembayaran</h2>
                    <p class="form-intro">Jangan menyetujui klaim hanya dari tombol siswa. Cocokkan mutasi QRIS dan nominal persis yang diterima.</p>
                    <form action="{{ route('merchant.orders.verify-payment', $order) }}" method="POST" class="form-stack">
                        @csrf
                        <label class="field"><span>Nominal yang masuk (Rp)</span><input type="number" name="confirmed_amount" min="0" step="0.01" value="{{ old('confirmed_amount', $order->total) }}" required></label>
                        <button class="button button-primary" type="submit">Nominal cocok — verifikasi</button>
                    </form>
                    <hr class="form-divider">
                    <form action="{{ route('merchant.orders.reject-payment', $order) }}" method="POST" class="form-stack" onsubmit="return confirm('Batalkan pesanan karena pembayaran tidak cocok?')">
                        @csrf
                        <label class="field"><span>Alasan penolakan</span><input name="reason" maxlength="500" required></label>
                        <button class="button button-danger" type="submit">Tolak dan batalkan pesanan</button>
                    </form>
                </div>
            @elseif ($order->order_status === 'processing')
                <div class="form-card payment-card"><p class="eyebrow">Pembayaran terverifikasi</p><h2>Pesanan sedang disiapkan</h2><p class="form-intro">Setelah selesai dimasak/disiapkan, ubah status agar siswa menerima kode pengambilan.</p><form action="{{ route('merchant.orders.ready', $order) }}" method="POST">@csrf<button class="button button-primary" type="submit">Tandai siap diambil</button></form></div>
            @elseif ($order->order_status === 'ready_for_pickup')
                <div class="form-card payment-card"><p class="eyebrow">Validasi sekali pakai</p><h2>Konfirmasi pengambilan</h2><p class="form-intro">Minta siswa menunjukkan kode di halaman pesanan, lalu cocokkan sebelum menyelesaikan pesanan.</p><form action="{{ route('merchant.orders.complete', $order) }}" method="POST" class="form-stack">@csrf<label class="field"><span>Kode pengambilan</span><input name="pickup_code" autocomplete="off" maxlength="32" required></label><button class="button button-primary" type="submit">Validasi & selesaikan</button></form></div>
            @elseif ($order->order_status === 'completed')
                <div class="form-card payment-card"><h2>Pesanan selesai</h2><p class="form-intro">Pesanan diselesaikan {{ $order->completed_at?->format('d M Y, H:i') }}. Kode pengambilan telah digunakan.</p></div>
            @else
                <div class="form-card payment-card"><h2>Status pesanan</h2><p class="form-intro">{{ str($order->order_status)->replace('_', ' ')->title() }} · pembayaran {{ str($order->payment_status)->replace('_', ' ') }}</p></div>
            @endif
        </div>
    </section>
@endsection
