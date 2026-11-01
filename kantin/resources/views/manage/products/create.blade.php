@extends('layouts.app')

@section('title', 'Tambah Menu')

@section('content')
    <section class="management-page">
        <a class="back-link" href="{{ route($routePrefix.'.index') }}">← Kembali ke menu saya</a>
        <div class="form-card">
            <p class="eyebrow">Pengelolaan kantin</p>
            <h1>Tambah menu</h1>
            @include('manage.products._form', ['action' => route($routePrefix.'.store'), 'method' => 'POST'])
        </div>
    </section>
@endsection
