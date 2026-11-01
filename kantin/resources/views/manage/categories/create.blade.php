@extends('layouts.app')

@section('title', 'Tambah Kategori')

@section('content')
    <section class="management-page">
        <a class="back-link" href="{{ route('manage.categories.index') }}">← Kembali ke kategori</a>
        <div class="form-card">
            <p class="eyebrow">Pengaturan katalog</p>
            <h1>Tambah kategori</h1>
            @include('manage.categories._form', ['action' => route('manage.categories.store'), 'method' => 'POST'])
        </div>
    </section>
@endsection
