<?php

namespace Database\Factories;

use App\Models\MerchantProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MerchantProfile>
 */
class MerchantProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->merchant(),
            'stall_name' => fake()->company(),
            'description' => fake()->optional()->sentence(),
            'opening_hours' => null,
            'status' => 'active',
        ];
    }
}
