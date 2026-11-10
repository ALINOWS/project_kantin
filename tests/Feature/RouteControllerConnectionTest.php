<?php

namespace Tests\Feature;

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
            ->assertOk();
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
