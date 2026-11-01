<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\MerchantProfile;
use App\Models\Product;
use App\Models\QrCode;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_catalog_shows_active_stalls_and_marks_unavailable_products(): void
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
                'name' => 'Es Batu Habis',
                'slug' => 'es-batu-habis',
                'is_available' => false,
            ]);
        $inactiveCategory = Category::factory()->create(['is_active' => false]);
        Product::factory()
            ->for($inactiveCategory)
            ->for($merchantProfile, 'merchantProfile')
            ->create(['name' => 'Kategori Nonaktif', 'slug' => 'kategori-nonaktif']);
        $inactiveMerchant = MerchantProfile::factory()->create(['status' => 'inactive']);
        Product::factory()
            ->for($category)
            ->for($inactiveMerchant, 'merchantProfile')
            ->create(['name' => 'Kantin Nonaktif', 'slug' => 'kantin-nonaktif']);

        $this->get('/')
            ->assertOk()
            ->assertSee($visibleProduct->name)
            ->assertSee('Es Batu Habis')
            ->assertSee('Habis')
            ->assertDontSee('Kategori Nonaktif')
            ->assertDontSee('Kantin Nonaktif');
    }

    public function test_guest_is_sent_to_login_before_accessing_menu_management(): void
    {
        $this->get(route('manage.products.index'))
            ->assertRedirect(route('login'));
    }

    public function test_guest_authentication_pages_render_for_students_and_merchants(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Masuk ke akun KantinMuh');
        $this->get(route('register'))
            ->assertOk()
            ->assertSee('Daftar sebagai siswa');
        $this->get(route('register.merchant'))
            ->assertOk()
            ->assertSee('Daftarkan kantin Anda');
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
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'category.created',
            'entity_type' => 'category',
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

    public function test_admin_can_create_and_disable_a_merchant_menu_qr(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $merchant = MerchantProfile::factory()->create(['status' => 'active']);

        $this->actingAs($admin)
            ->post(route('admin.qrcodes.store'), ['merchant_id' => $merchant->id])
            ->assertRedirect(route('admin.qrcodes.index'));

        $qrCode = QrCode::query()->firstOrFail();
        $this->assertSame(route('qrcode.scan', $qrCode->token), $qrCode->target_url);
        $this->get(route('qrcode.scan', $qrCode->token))
            ->assertRedirect(route('menu.index', ['merchant_id' => $merchant->id]));

        $this->patch(route('admin.qrcodes.toggle', $qrCode))->assertRedirect();
        $this->assertDatabaseHas('qr_codes', ['id' => $qrCode->id, 'is_active' => false]);
        $this->get(route('qrcode.scan', $qrCode->token))->assertNotFound();
    }

    public function test_report_date_filters_reject_invalid_and_reversed_ranges(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)
            ->from(route('admin.reports'))
            ->get(route('admin.reports', ['start' => 'not-a-date']))
            ->assertRedirect(route('admin.reports'))
            ->assertSessionHasErrors('start');

        $this->get(route('admin.reports', [
            'start' => '2026-11-02',
            'end' => '2026-11-01',
        ]))->assertStatus(422);

        $merchant = User::factory()->merchant()->create();
        MerchantProfile::factory()->for($merchant)->create(['status' => 'active']);
        $this->actingAs($merchant)
            ->from(route('merchant.reports'))
            ->get(route('merchant.reports', ['end' => 'invalid']))
            ->assertRedirect(route('merchant.reports'))
            ->assertSessionHasErrors('end');
    }

    public function test_merchant_registration_requires_admin_approval_before_login(): void
    {
        $this->post(route('register.merchant.store'), [
            'name' => 'Penjual Baru',
            'email' => 'penjual.baru@example.test',
            'stall_name' => 'Kantin Baru',
            'description' => 'Menu rumahan',
            'password' => 'strong-password',
            'password_confirmation' => 'strong-password',
        ])->assertRedirect(route('login'));

        $merchant = User::query()->where('email', 'penjual.baru@example.test')->firstOrFail();
        $this->assertGuest();
        $this->assertDatabaseHas('merchant_profiles', [
            'user_id' => $merchant->id,
            'status' => 'pending',
        ]);

        $this->post(route('login.store'), [
            'email' => 'penjual.baru@example.test',
            'password' => 'strong-password',
        ])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_student_registration_creates_an_authenticated_student_account(): void
    {
        $this->post(route('register.store'), [
            'name' => 'Siswa Baru',
            'email' => 'siswa.baru@example.test',
            'password' => 'strong-password',
            'password_confirmation' => 'strong-password',
        ])->assertRedirect(route('menu.index'));

        $student = User::query()->where('email', 'siswa.baru@example.test')->firstOrFail();
        $this->assertAuthenticatedAs($student);
        $this->assertSame('student', $student->role);
        $this->assertSame('active', $student->status);
    }

    public function test_notifications_can_only_be_marked_read_by_their_recipient(): void
    {
        $recipient = User::factory()->create();
        $otherUser = User::factory()->create();
        $notification = UserNotification::query()->create([
            'user_id' => $recipient->id,
            'type' => 'general',
            'data' => ['message' => 'Pesanan diperbarui.'],
        ]);

        $this->actingAs($otherUser)
            ->post(route('notifications.read', $notification))
            ->assertNotFound();
        $this->assertNull($notification->fresh()->read_at);

        $this->actingAs($recipient)
            ->post(route('notifications.read', $notification))
            ->assertRedirect(route('orders.index'));
        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_all_role_specific_create_and_edit_pages_render(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::factory()->create();
        $merchant = MerchantProfile::factory()->create();
        $product = Product::factory()
            ->for($category)
            ->for($merchant, 'merchantProfile')
            ->create();

        foreach ([
            route('manage.categories.create'),
            route('manage.categories.edit', $category),
            route('manage.products.create'),
            route('manage.products.edit', $product),
        ] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }

        $this->get(route('admin.users.index'))
            ->assertSee('form="user-'.$admin->id.'"', false);

        $merchantUser = $merchant->user;
        foreach ([
            route('merchant.products.create'),
            route('merchant.products.edit', $product),
        ] as $url) {
            $this->actingAs($merchantUser)->get($url)->assertOk();
        }
    }

    public function test_login_attempts_are_rate_limited(): void
    {
        for ($attempt = 0; $attempt < 6; $attempt++) {
            $this->post(route('login.store'), [
                'email' => 'unknown@example.test',
                'password' => 'invalid-password',
            ])->assertRedirect();
        }

        $this->post(route('login.store'), [
            'email' => 'unknown@example.test',
            'password' => 'invalid-password',
        ])->assertTooManyRequests();
    }

    public function test_admin_user_filters_reject_invalid_roles_and_array_search_terms(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->from(route('admin.users.index'))
            ->get(route('admin.users.index', ['role' => 'superuser']))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHasErrors('role');

        $this->get(route('admin.users.index', ['search' => ['unexpected']]))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHasErrors('search');
    }

    public function test_catalog_filters_by_category_and_qr_merchant(): void
    {
        $category = Category::factory()->create(['name' => 'Minuman', 'slug' => 'minuman']);
        $otherCategory = Category::factory()->create(['name' => 'Makanan', 'slug' => 'makanan']);
        $merchant = MerchantProfile::factory()->create(['stall_name' => 'Kantin A']);
        $otherMerchant = MerchantProfile::factory()->create(['stall_name' => 'Kantin B']);
        $matchingProduct = Product::factory()
            ->for($category)
            ->for($merchant, 'merchantProfile')
            ->create(['name' => 'Es Jeruk', 'slug' => 'es-jeruk']);
        Product::factory()
            ->for($otherCategory)
            ->for($merchant, 'merchantProfile')
            ->create(['name' => 'Nasi Kuning', 'slug' => 'nasi-kuning']);
        Product::factory()
            ->for($category)
            ->for($otherMerchant, 'merchantProfile')
            ->create(['name' => 'Jus Mangga', 'slug' => 'jus-mangga']);

        $this->get(route('menu.index', [
            'category_id' => $category->id,
            'merchant_id' => $merchant->id,
        ]))
            ->assertOk()
            ->assertSee($matchingProduct->name)
            ->assertDontSee('Nasi Kuning')
            ->assertDontSee('Jus Mangga');
    }

    public function test_categories_in_use_cannot_be_deleted(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::factory()->create();
        Product::factory()->for($category)->create();

        $this->actingAs($admin)
            ->from(route('manage.categories.index'))
            ->delete(route('manage.categories.destroy', $category))
            ->assertRedirect(route('manage.categories.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }
}
