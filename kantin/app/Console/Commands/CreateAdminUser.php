<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

#[Signature('kantin:create-admin-user {email}')]
#[Description('Create the first active KantinMuh administrator')]
class CreateAdminUser extends Command
{
    public function handle(): int
    {
        $email = (string) $this->argument('email');
        if (! filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) {
            $this->components->error('Alamat email tidak valid.');

            return self::FAILURE;
        }

        if (User::query()->where('email', $email)->exists()) {
            $this->components->error('Email sudah terdaftar. Kelola akun melalui dashboard admin.');

            return self::FAILURE;
        }

        $name = trim($this->ask('Nama admin') ?? '');
        $password = $this->secret('Kata sandi admin (minimal 12 karakter)');
        if ($name === '' || mb_strlen($name) > 120 || $password === null || mb_strlen($password) < 12) {
            $this->components->error('Nama wajib diisi dan kata sandi minimal 12 karakter.');

            return self::FAILURE;
        }

        $confirmedPassword = $this->secret('Ulangi kata sandi');
        if (! hash_equals($password, (string) $confirmedPassword)) {
            $this->components->error('Konfirmasi kata sandi tidak cocok.');

            return self::FAILURE;
        }

        User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->components->info("Admin {$email} berhasil dibuat.");

        return self::SUCCESS;
    }
}
