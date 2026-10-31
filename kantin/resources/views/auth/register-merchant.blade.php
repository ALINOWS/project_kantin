@extends('layouts.app')

@section('title', 'Daftar Penjual')

@section('content')
    <section class="auth-panel">
        <p class="eyebrow">Pendaftaran kantin</p>
        <h1>Daftarkan kantin Anda</h1>
        <p class="form-intro">Admin akan meninjau pendaftaran sebelum kantin dapat menerima pesanan.</p>
        <form action="{{ route('register.merchant.store') }}" method="POST" class="form-stack">
            @csrf
            <label class="field"><span>Nama penjual</span><input name="name" value="{{ old('name') }}" maxlength="120" autocomplete="name" required></label>
            <label class="field"><span>Email</span><input type="email" name="email" value="{{ old('email') }}" maxlength="190" autocomplete="email" required></label>
            <label class="field"><span>Nama kantin</span><input name="stall_name" value="{{ old('stall_name') }}" maxlength="150" required></label>
            <label class="field"><span>Deskripsi kantin</span><textarea name="description" rows="3" maxlength="5000">{{ old('description') }}</textarea></label>
            <label class="field"><span>Kata sandi</span><input type="password" name="password" minlength="8" autocomplete="new-password" required></label>
            <label class="field"><span>Ulangi kata sandi</span><input type="password" name="password_confirmation" minlength="8" autocomplete="new-password" required></label>
            <button class="button button-primary button-wide" type="submit">Kirim pendaftaran</button>
        </form>
    </section>
@endsection
