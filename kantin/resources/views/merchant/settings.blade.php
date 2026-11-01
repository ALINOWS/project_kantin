@extends('layouts.app')

@section('title', 'Pengaturan Kantin')

@section('content')
    <section class="management-page">
        <a class="back-link" href="{{ route('merchant.dashboard') }}">← Kembali ke dashboard</a>
        <div class="form-card"><p class="eyebrow">Profil penjual</p><h1>Pengaturan kantin</h1>
            <form action="{{ route('merchant.settings.update') }}" method="POST" enctype="multipart/form-data" class="form-stack">
                @csrf
                @method('PUT')
                <label class="field"><span>Nama kantin</span><input name="stall_name" value="{{ old('stall_name', $merchant->stall_name) }}" maxlength="150" required></label>
                <label class="field"><span>Deskripsi</span><textarea name="description" rows="3" maxlength="5000">{{ old('description', $merchant->description) }}</textarea></label>
                <label class="field"><span>Jam operasional</span><input name="opening_hours" value="{{ old('opening_hours', $merchant->opening_hours['text'] ?? '') }}" maxlength="500" placeholder="Senin–Jumat, 07.00–14.00"></label>
                <div class="field"><span>QRIS statis kantin</span>
                    @if ($merchant->qris_image)<img class="qris-preview" src="{{ Storage::disk('public')->url($merchant->qris_image) }}" alt="QRIS kantin"><label class="checkbox-field"><input type="checkbox" name="remove_qris" value="1"><span>Hapus foto QRIS</span></label>@endif
                    <input type="file" name="qris_image" accept="image/jpeg,image/png,image/webp">
                    <small>JPG, PNG, atau WebP, maksimal 4 MB. QRIS ditampilkan pada halaman pembayaran.</small>
                </div>
                <div class="alert alert-warning">Gunakan foto QRIS statis milik kantin. Sistem tidak menghubungi payment gateway dan pembayaran tetap harus dicocokkan manual.</div>
                <button class="button button-primary" type="submit">Simpan pengaturan</button>
            </form>
        </div>
    </section>
@endsection
