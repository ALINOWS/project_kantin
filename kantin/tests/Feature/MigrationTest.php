<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_ordering_fields_migration_can_be_replayed_without_duplicate_indexes(): void
    {
        $migration = require base_path('database/migrations/2026_10_31_064651_add_kantinmuh_ordering_fields.php');

        $migration->up();
        $migration->up();

        $this->assertTrue(Schema::hasIndex(
            'orders',
            ['merchant_id', 'unique_code', 'payment_status']
        ));
    }
}
