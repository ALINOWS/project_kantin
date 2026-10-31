<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class MerchantProfileSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::query()
            ->where('role', 'merchant')
            ->whereDoesntHave('merchantProfile')
            ->each(function (User $user): void {
                $user->merchantProfile()->create([
                    'stall_name' => $user->name,
                    'status' => 'pending',
                ]);
            });
    }
}
