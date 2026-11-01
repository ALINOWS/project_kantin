@extends('layouts.app')

@section('title', 'Ubah Menu')

@section('content')
    <section class="management-page">
        <a class="back-link" href="{{ route($routePrefix.'.index') }}">← Kembali ke menu saya</a>
        <div class="form-card">
            <p class="eyebrow">Pengelolaan kantin</p>
            <h1>Ubah menu</h1>
            @include('manage.products._form', ['action' => route($routePrefix.'.update', $product), 'method' => 'PUT'])
        </div>
    </section>
@endsection
