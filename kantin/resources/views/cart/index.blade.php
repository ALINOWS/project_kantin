@extends('layouts.app')

@section('title', 'Keranjang')

@section('content')
    <section class="management-page">
        <div class="section-heading">
            <div><p class="eyebrow">Pesanan Anda</p><h1>Keranjang</h1></div>
            <a class="button button-muted" href="{{ route('menu.index') }}">Tambah menu</a>
        </div>

        @if ($items->isEmpty())
            <div class="empty-state"><span class="empty-icon" aria-hidden="true">🛒</span><h3>Keranjang masih kosong</h3><p>Pilih menu yang tersedia untuk mulai memesan.</p></div>
        @else
            <div class="table-card">
                <table>
                    <thead><tr><th>Menu</th><th>Harga</th><th>Jumlah</th><th>Subtotal</th><th>Aksi</th></tr></thead>
                    <tbody>
                        @foreach ($items as $item)
                            <tr>
                                <td><strong>{{ $item->product->name }}</strong><small class="table-subtitle">{{ $item->product->merchantProfile->stall_name }} · {{ $item->product->is_available ? 'Tersedia' : 'Habis' }}</small></td>
                                <td>Rp{{ number_format((float) $item->product->price, 0, ',', '.') }}</td>
                                <td>
                                    <form action="{{ route('cart.items.update', $item) }}" method="POST" class="quantity-form">
                                        @csrf
                                        @method('PATCH')
                                        <input aria-label="Jumlah {{ $item->product->name }}" type="number" name="quantity" min="1" max="99" value="{{ $item->quantity }}" required>
                                        <button class="text-button" type="submit">Ubah</button>
                                    </form>
                                </td>
                                <td>Rp{{ number_format((float) $item->product->price * $item->quantity, 0, ',', '.') }}</td>
                                <td>
                                    <form action="{{ route('cart.items.destroy', $item) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button class="text-button text-danger" type="submit">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="checkout-summary">
                <p><span>Subtotal</span><strong>Rp{{ number_format($subtotalCents / 100, 0, ',', '.') }}</strong></p>
                <p class="checkout-hint">Kode unik pembayaran dibuat saat checkout. Satu pesanan hanya untuk satu kantin.</p>
                @if ($items->contains(fn ($item) => ! $item->product->is_available))
                    <div class="alert alert-error">Hapus menu yang sudah habis sebelum melanjutkan checkout.</div>
                @else
                    <form action="{{ route('checkout.store') }}" method="POST" class="checkout-form">
                        @csrf
                        <label class="field"><span>Catatan untuk penjual <small>(opsional)</small></span><textarea name="notes" maxlength="500" rows="2"></textarea></label>
                        <button class="button button-primary" type="submit">Lanjut ke pembayaran QRIS</button>
                    </form>
                @endif
            </div>
        @endif
    </section>
@endsection
