@extends('layouts.app')

@section('title', $product->name)

@section('content')
    <section class="product-detail">
        <a class="back-link" href="{{ route('menu.index', request()->only('merchant_id')) }}">← Kembali ke menu</a>
        <div class="detail-card">
            <div class="detail-art">
                @if ($product->image)
                    <img src="{{ Storage::disk('public')->url($product->image) }}" alt="{{ $product->name }}">
                @else
                    <span aria-hidden="true">{{ $product->category->name === 'Minuman' ? '🥤' : '🍱' }}</span>
                @endif
            </div>
            <div class="detail-copy">
                <p class="eyebrow">{{ $product->category->name }} · {{ $product->merchantProfile->stall_name }}</p>
                <h1>{{ $product->name }}</h1>
                <strong class="detail-price">Rp{{ number_format((float) $product->price, 0, ',', '.') }}</strong>
                <p class="detail-description">{{ $product->description ?: 'Menu favorit dari kantin sekolah.' }}</p>
                <span class="status-pill {{ $product->is_available ? 'status-active' : 'status-inactive' }}">
                    {{ $product->is_available ? 'Tersedia' : 'Habis' }}
                </span>
                @if ($product->is_available)
                    @auth
                        @if (auth()->user()->role === 'student')
                            <form action="{{ route('cart.items.store') }}" method="POST" class="detail-order-form">
                                @csrf
                                <input type="hidden" name="product_id" value="{{ $product->id }}">
                                <label class="field"><span>Jumlah</span><input type="number" name="quantity" min="1" max="99" value="1" required></label>
                                <button class="button button-primary" type="submit">Tambah ke keranjang</button>
                            </form>
                        @endif
                    @else
                        <a class="button button-primary detail-login" href="{{ route('login') }}">Masuk untuk memesan</a>
                    @endauth
                @endif
            </div>
        </div>
    </section>
@endsection
