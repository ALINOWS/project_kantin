@extends('layouts.app')

@section('title', 'Kelola Menu')

@section('content')
    <section class="management-page">
        <div class="section-heading">
            <div>
                <p class="eyebrow">Pengelolaan kantin</p>
                <h1>Menu saya</h1>
            </div>
            <a class="button button-primary" href="{{ route($routePrefix.'.create') }}">Tambah menu</a>
        </div>

        <div class="table-card">
            <table>
                <thead>
                    <tr><th>Nama menu</th><th>Kategori</th><th>Harga</th><th>Status</th><th>Aksi</th></tr>
                </thead>
                <tbody>
                    @forelse ($products as $product)
                        <tr>
                            <td><strong>{{ $product->name }}</strong>@if (auth()->user()->role === 'admin')<small class="table-subtitle">{{ $product->merchantProfile->stall_name }}</small>@endif</td>
                            <td>{{ $product->category->name }}</td>
                            <td>Rp{{ number_format((float) $product->price, 0, ',', '.') }}</td>
                            <td><span class="status-pill {{ $product->is_available ? 'status-active' : 'status-inactive' }}">{{ $product->is_available ? 'Tersedia' : 'Nonaktif' }}</span></td>
                            <td class="table-actions">
                                <a href="{{ route($routePrefix.'.edit', $product) }}">Ubah</a>
                                <form action="{{ route($routePrefix.'.destroy', $product) }}" method="POST" onsubmit="return confirm('Hapus menu ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-button text-danger" type="submit">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td class="table-empty" colspan="5">Belum ada menu. Tambahkan menu pertama kantin Anda.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination-wrap">{{ $products->links() }}</div>
    </section>
@endsection
