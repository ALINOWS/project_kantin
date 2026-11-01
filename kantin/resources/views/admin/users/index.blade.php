@extends('layouts.app')

@section('title', 'Manajemen Pengguna')

@section('content')
    <section class="management-page">
        <div class="section-heading"><div><p class="eyebrow">Administrasi akses</p><h1>Pengguna & verifikasi kantin</h1></div><a class="button button-muted" href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <form class="filter-bar" method="GET"><label class="search-field"><span class="sr-only">Cari pengguna</span><input type="search" name="search" value="{{ request('search') }}" placeholder="Nama atau email"></label><label class="category-field"><span class="sr-only">Filter peran</span><select name="role"><option value="">Semua peran</option>@foreach (['student' => 'Siswa', 'merchant' => 'Penjual', 'admin' => 'Admin'] as $value => $label)<option value="{{ $value }}" @selected(request('role') === $value)>{{ $label }}</option>@endforeach</select></label><button class="button button-primary" type="submit">Cari</button></form>
        <div class="table-card"><table><thead><tr><th>Pengguna / Kantin</th><th>Peran</th><th>Status pengguna</th><th>Status kantin</th><th>Aksi</th></tr></thead><tbody>
            @forelse ($users as $user)
                <tr><td><form id="user-{{ $user->id }}" action="{{ route('admin.users.update', $user) }}" method="POST">@csrf @method('PATCH')</form><strong>{{ $user->name }}</strong><small class="table-subtitle">{{ $user->email }}{{ $user->merchantProfile ? ' · '.$user->merchantProfile->stall_name : '' }}</small></td>
                    <td><select form="user-{{ $user->id }}" name="role">@foreach (['student' => 'Siswa', 'merchant' => 'Penjual', 'admin' => 'Admin'] as $value => $label)<option value="{{ $value }}" @selected($user->role === $value)>{{ $label }}</option>@endforeach</select></td>
                    <td><select form="user-{{ $user->id }}" name="status">@foreach (['active' => 'Aktif', 'inactive' => 'Nonaktif', 'suspended' => 'Ditangguhkan'] as $value => $label)<option value="{{ $value }}" @selected($user->status === $value)>{{ $label }}</option>@endforeach</select></td>
                    <td>@if ($user->role === 'merchant' && $user->merchantProfile)<select form="user-{{ $user->id }}" name="merchant_status">@foreach (['pending' => 'Menunggu', 'active' => 'Aktif', 'inactive' => 'Nonaktif', 'rejected' => 'Ditolak'] as $value => $label)<option value="{{ $value }}" @selected($user->merchantProfile->status === $value)>{{ $label }}</option>@endforeach</select>@else—@endif</td>
                    <td><button class="button button-primary button-small" form="user-{{ $user->id }}" type="submit">Simpan</button></td>
                </tr>
            @empty<tr><td colspan="5" class="table-empty">Tidak ada pengguna.</td></tr>@endforelse
        </tbody></table></div>
        <div class="pagination-wrap">{{ $users->links() }}</div>
    </section>
@endsection
