<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RouteControllerConnectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_web_routes_reference_existing_controller_methods(): void
    {
        $controllerActions = [];

        foreach (Route::getRoutes() as $route) {
            $action = $route->getActionName();

            if (! str_starts_with($action, 'App\\Http\\Controllers\\')) {
                continue;
            }

            [$controller, $method] = explode('@', $action, 2);

            $this->assertTrue(class_exists($controller), "Controller [{$controller}] does not exist.");
            $this->assertTrue(
                method_exists($controller, $method),
                "Method [{$method}] does not exist on controller [{$controller}].",
            );

            $controllerActions[$controller] = true;
        }

        $this->assertEqualsCanonicalizing(
            [
                'App\\Http\\Controllers\\AuditLogController',
                'App\\Http\\Controllers\\CartController',
                'App\\Http\\Controllers\\KategoriController',
                'App\\Http\\Controllers\\MerchantProfileController',
                'App\\Http\\Controllers\\NotificationController',
                'App\\Http\\Controllers\\OrderController',
                'App\\Http\\Controllers\\PaymentController',
                'App\\Http\\Controllers\\ProductController',
                'App\\Http\\Controllers\\QrCodeController',
                'App\\Http\\Controllers\\ReviewController',
                'App\\Http\\Controllers\\UserController',
            ],
            array_keys($controllerActions),
        );
    }

    public function test_public_category_route_reaches_its_controller(): void
    {
        $this->getJson('/kategori')
            ->assertOk()
            ->assertContent('ini halaman Kategori');
    }

    public function test_controller_index_routes_display_their_page_text(): void
    {
        $this->actingAs(User::factory()->create());

        foreach ([
            '/users' => 'ini halaman Users',
            '/kategori' => 'ini halaman Kategori',
            '/merchant-profiles' => 'ini halaman Merchant Profiles',
            '/produk' => 'ini halaman Products',
            '/qr-codes' => 'ini halaman QR Codes',
            '/keranjang' => 'ini halaman Cart',
            '/pesanan' => 'ini halaman Orders',
            '/pembayaran' => 'ini halaman Payments',
            '/ulasan' => 'ini halaman Reviews',
            '/notifikasi' => 'ini halaman Notifications',
            '/audit-log' => 'ini halaman Audit Logs',
        ] as $uri => $pageText) {
            $this->get($uri)
                ->assertOk()
                ->assertContent($pageText);
        }
    }

    public function test_guest_is_redirected_to_public_page_for_protected_routes(): void
    {
        $this->get('/users')
            ->assertRedirect('/kategori');
    }

    public function test_resource_route_parameters_match_controller_model_arguments(): void
    {
        $routes = Route::getRoutes();

        $this->assertSame(
            ['merchantProfile'],
            $routes->getByName('merchant-profiles.show')->parameterNames(),
        );
        $this->assertSame(
            ['product'],
            $routes->getByName('produk.update')->parameterNames(),
        );
        $this->assertSame(
            ['qrCode'],
            $routes->getByName('qr-codes.show')->parameterNames(),
        );
    }
}
