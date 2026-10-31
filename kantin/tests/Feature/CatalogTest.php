<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\MerchantProfile;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_catalog_shows_only_available_products_from_active_categories_and_stalls(): void
    {
        $category = Category::factory()->create(['name' => 'Makanan', 'slug' => 'makanan']);
        $merchantProfile = MerchantProfile::factory()->create();
        $visibleProduct = Product::factory()
            ->for($category)
            ->for($merchantProfile, 'merchantProfile')
            ->create(['name' => 'Nasi Goreng', 'slug' => 'nasi-goreng']);

        Product::factory()
            ->for($category)
            ->for($merchantProfile, 'merchantProfile')
            ->create([
                'name' => 'Menu Tidak Tersedia',
                'slug' => 'menu-tidak-tersedia',
                'is_available' => false,
            ]);

        $this->get('/?search=Nasi')
            ->assertOk()
            ->assertSee($visibleProduct->name)
            ->assertDontSee('Menu Tidak Tersedia');
    }

    public function test_guest_is_sent_to_login_before_accessing_menu_management(): void
    {
        $this->get(route('manage.products.index'))
            ->assertRedirect(route('login'));
    }

    public function test_active_merchant_can_log_in_and_inactive_merchants_are_rejected(): void
    {
        $merchant = User::factory()->merchant()->create([
            'email' => 'merchant@example.com',
        ]);
        MerchantProfile::factory()->for($merchant)->create(['status' => 'active']);

        $this->post(route('login.store'), [
            'email' => 'merchant@example.com',
            'password' => 'password',
        ])
            ->assertRedirect(route('merchant.dashboard'));
        $this->assertAuthenticatedAs($merchant);

        auth()->logout();

        $inactiveMerchant = User::factory()->merchant()->create([
            'email' => 'inactive@example.com',
        ]);
        MerchantProfile::factory()->for($inactiveMerchant)->create(['status' => 'pending']);

        $response = $this->from(route('login'))
            ->post(route('login.store'), [
                'email' => 'inactive@example.com',
                'password' => 'password',
            ]);

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_active_merchant_can_create_a_product_for_its_own_stall(): void
    {
        $user = User::factory()->merchant()->create();
        $merchantProfile = MerchantProfile::factory()->for($user)->create(['status' => 'active']);
        $category = Category::factory()->create();

        $this->actingAs($user)
            ->post(route('merchant.products.store'), [
                'category_id' => $category->id,
                'name' => 'Roti Bakar',
                'slug' => 'roti-bakar',
                'description' => 'Roti hangat',
                'price' => 10000,
                'is_available' => '1',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('products', [
            'merchant_id' => $merchantProfile->id,
            'category_id' => $category->id,
            'slug' => 'roti-bakar',
        ]);
    }

    public function test_merchant_cannot_edit_another_stalls_product_or_manage_categories(): void
    {
        $merchant = User::factory()->merchant()->create();
        MerchantProfile::factory()->for($merchant)->create(['status' => 'active']);
        $otherMerchant = MerchantProfile::factory()->create();
        $product = Product::factory()->for($otherMerchant, 'merchantProfile')->create();

        $this->actingAs($merchant)
            ->get(route('merchant.products.edit', $product))
            ->assertNotFound();

        $this->get(route('manage.categories.index'))
            ->assertForbidden();
    }

    public function test_admin_can_create_a_category(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('manage.categories.store'), [
                'name' => 'Minuman',
                'slug' => 'minuman',
                'description' => 'Minuman segar',
                'is_active' => '1',
            ])
            ->assertRedirect(route('manage.categories.index'));

        $this->assertDatabaseHas('categories', [
            'name' => 'Minuman',
            'slug' => 'minuman',
            'is_active' => true,
        ]);
    }

    public function test_admin_and_merchant_dashboard_pages_render_for_their_roles(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        foreach ([
            'admin.dashboard',
            'admin.users.index',
            'admin.qrcodes.index',
            'admin.reports',
            'admin.audit',
            'manage.categories.index',
            'manage.products.index',
        ] as $routeName) {
            $this->actingAs($admin)->get(route($routeName))->assertOk();
        }

        $merchant = User::factory()->merchant()->create();
        MerchantProfile::factory()->for($merchant)->create(['status' => 'active']);

        foreach ([
            'merchant.dashboard',
            'merchant.orders.index',
            'merchant.products.index',
            'merchant.settings.edit',
            'merchant.reports',
        ] as $routeName) {
            $this->actingAs($merchant)->get(route($routeName))->assertOk();
        }
    }
}
