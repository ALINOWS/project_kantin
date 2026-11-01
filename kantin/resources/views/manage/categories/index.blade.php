@extends('layouts.app')

@section('title', 'Kategori Menu')

@section('content')
    <section class="management-page">
        <div class="section-heading">
            <div>
                <p class="eyebrow">Pengaturan katalog</p>
                <h1>Kategori menu</h1>
            </div>
            <a class="button button-primary" href="{{ route('manage.categories.create') }}">Tambah kategori</a>
        </div>

        <div class="table-card">
            <table>
                <thead>
                    <tr><th>Nama</th><th>Slug</th><th>Menu</th><th>Status</th><th>Aksi</th></tr>
                </thead>
                <tbody>
                    @forelse ($categories as $category)
                        <tr>
                            <td><strong>{{ $category->name }}</strong></td>
                            <td>{{ $category->slug }}</td>
                            <td>{{ $category->products_count }}</td>
                            <td><span class="status-pill {{ $category->is_active ? 'status-active' : 'status-inactive' }}">{{ $category->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                            <td class="table-actions">
                                <a href="{{ route('manage.categories.edit', $category) }}">Ubah</a>
                                @if ($category->products_count === 0)
                                    <form action="{{ route('manage.categories.destroy', $category) }}" method="POST" onsubmit="return confirm('Hapus kategori ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="text-button text-danger" type="submit">Hapus</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td class="table-empty" colspan="5">Belum ada kategori.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination-wrap">{{ $categories->links() }}</div>
    </section>
@endsection
