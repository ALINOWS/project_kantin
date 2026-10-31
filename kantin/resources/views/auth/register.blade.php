@extends('layouts.app')

@section('title', 'Daftar Siswa')

@section('content')
    <section class="auth-panel">
        <p class="eyebrow">Akun KantinMuh</p>
        <h1>Daftar sebagai siswa</h1>
        <p class="form-intro">Buat akun untuk memesan dan melacak pesanan kantin.</p>
        <form action="{{ route('register.store') }}" method="POST" class="form-stack">
            @csrf
            <label class="field"><span>Nama lengkap</span><input name="name" value="{{ old('name') }}" maxlength="120" autocomplete="name" required></label>
            <label class="field"><span>Email</span><input type="email" name="email" value="{{ old('email') }}" maxlength="190" autocomplete="email" required></label>
            <label class="field"><span>Kata sandi</span><input type="password" name="password" minlength="8" autocomplete="new-password" required></label>
            <label class="field"><span>Ulangi kata sandi</span><input type="password" name="password_confirmation" minlength="8" autocomplete="new-password" required></label>
            <button class="button button-primary button-wide" type="submit">Buat akun siswa</button>
        </form>
        <p class="auth-switch">Sudah terdaftar? <a href="{{ route('login') }}">Masuk</a>.</p>
    </section>
@endsection
