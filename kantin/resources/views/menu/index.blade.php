@extends('layouts.app')

@section('title', 'Menu Kantin')

@section('content')
    <section class="hero">
        <div class="hero-copy">
            <p class="eyebrow">Jajan lebih mudah</p>
            <h1>Menu kantin,<br><span>siap menemani harimu.</span></h1>
            <p class="hero-description">Temukan makanan dan minuman favorit dari kantin pilihanmu.</p>
        </div>
        <div class="hero-illustration" aria-hidden="true">
            <span class="illustration-sun"></span>
            <span class="illustration-plate">🍜</span>
            <span class="illustration-leaf">✦</span>
        </div>
    </section>

    <section class="catalog-section" aria-labelledby="catalog-title">
        <div class="section-heading">
            <div>
                <p class="eyebrow">Pilihan hari ini</p>
                <h2 id="catalog-title">Jelajahi menu</h2>
            </div>
            <span class="result-count">{{ $products->total() }} menu{{ $merchant ? ' · '.$merchant->stall_name : '' }}</span>
        </div>

        <form class="filter-bar" action="{{ route('menu.index') }}" method="GET">
            <label class="search-field">
                <span class="sr-only">Cari menu</span>
                <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Cari makanan atau minuman...">
            </label>
            <label class="category-field">
                <span class="sr-only">Kategori menu</span>
                <select name="category_id">
                    <option value="">Semua kategori</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) ($filters['category_id'] ?? '') === (string) $category->id)>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </label>
            @if ($merchant)
                <input type="hidden" name="merchant_id" value="{{ $merchant->id }}">
            @endif
            <button class="button button-primary" type="submit">Cari menu</button>
        </form>

        @if ($products->isEmpty())
            <div class="empty-state">
                <span class="empty-icon" aria-hidden="true">🍽️</span>
                <h3>Belum ada menu yang cocok</h3>
                <p>Coba ubah kata pencarian atau pilih kategori lain.</p>
            </div>
        @else
            <div class="product-grid">
                @foreach ($products as $product)
                    <article class="product-card">
                        <a class="product-art" href="{{ route('menu.show', $product) }}" aria-label="Lihat {{ $product->name }}">
                            @if ($product->image)
                                <img src="{{ Storage::disk('public')->url($product->image) }}" alt="{{ $product->name }}">
                            @else
                                <span>{{ $product->category->name === 'Minuman' ? '🥤' : '🍱' }}</span>
                            @endif
                            <span class="product-category">{{ $product->category->name }}</span>
                            <span class="product-availability {{ $product->is_available ? '' : 'sold-out' }}">
                                {{ $product->is_available ? 'Tersedia' : 'Habis' }}
                            </span>
                        </a>
                        <div class="product-details">
                            <div class="product-title-row">
                                <h3>{{ $product->name }}</h3>
                                <span class="availability-dot {{ $product->is_available ? '' : 'sold-out-dot' }}" aria-label="{{ $product->is_available ? 'Tersedia' : 'Habis' }}"></span>
                            </div>
                            <p class="product-description">{{ $product->description ?: 'Menu favorit dari kantin sekolah.' }}</p>
                            <p class="product-stall">{{ $product->merchantProfile->stall_name }}</p>
                            <div class="product-footer">
                                <strong>Rp{{ number_format((float) $product->price, 0, ',', '.') }}</strong>
                                @if ($product->is_available)
                                    @auth
                                        @if (auth()->user()->role === 'student')
                                            <form action="{{ route('cart.items.store') }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="product_id" value="{{ $product->id }}">
                                                <input type="hidden" name="quantity" value="1">
                                                <button class="button button-add" type="submit">+ Keranjang</button>
                                            </form>
                                        @endif
                                    @else
                                        <a class="button button-add" href="{{ route('login') }}">Pesan</a>
                                    @endauth
                                @else
                                    <span class="sold-out-text">Habis</span>
                                @endif
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="pagination-wrap">{{ $products->links() }}</div>
        @endif
    </section>
@endsection
