@extends('layouts.app')

@section('title', 'Masuk')

@section('content')
    <section class="auth-panel">
        <p class="eyebrow">Selamat datang kembali</p>
        <h1>Masuk ke akun KantinMuh</h1>
        <p class="form-intro">Masuk sebagai siswa, penjual, atau admin menggunakan email dan kata sandi Anda.</p>

        <form action="{{ route('login.store') }}" method="POST" class="form-stack">
            @csrf
            <label class="field">
                <span>Email</span>
                <input type="email" name="email" value="{{ old('email') }}" autocomplete="username" required autofocus>
            </label>
            <label class="field">
                <span>Kata sandi</span>
                <input type="password" name="password" autocomplete="current-password" required>
            </label>
            <label class="checkbox-field">
                <input type="checkbox" name="remember" value="1">
                <span>Ingat saya</span>
            </label>
            <button class="button button-primary button-wide" type="submit">Masuk</button>
        </form>
        <p class="auth-switch">Belum punya akun siswa? <a href="{{ route('register') }}">Daftar di sini</a>.</p>
        <p class="auth-switch">Ingin membuka kantin? <a href="{{ route('register.merchant') }}">Daftar sebagai penjual</a>.</p>
    </section>
@endsection
