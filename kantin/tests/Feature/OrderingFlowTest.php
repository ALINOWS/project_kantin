<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\MerchantProfile;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderingFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_order_is_manually_verified_then_picked_up_once(): void
    {
        $student = User::factory()->create();
        $merchant = MerchantProfile::factory()->create(['qris_image' => 'qris/test.png']);
        $category = Category::factory()->create(['is_active' => true]);
        $product = Product::factory()
            ->for($category)
            ->for($merchant, 'merchantProfile')
            ->create(['name' => 'Nasi Goreng', 'price' => 10000]);
        $cart = $student->carts()->create(['status' => 'active']);
        $cart->items()->create(['product_id' => $product->id, 'quantity' => 2]);

        $this->actingAs($student)
            ->post(route('checkout.store'))
            ->assertRedirect();

        $order = Order::query()->where('user_id', $student->id)->firstOrFail();
        $this->assertSame('20000.00', $order->subtotal);
        $this->assertGreaterThan(20000, (float) $order->total);
        $this->assertLessThanOrEqual(20999, (float) $order->total);
        $this->assertSame('Nasi Goreng', $order->items()->firstOrFail()->product_name);
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'status' => 'pending',
        ]);

        $this->post(route('orders.claim-payment', $order))->assertRedirect();
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'order_status' => 'awaiting_merchant',
        ]);

        $merchantUser = $merchant->user;
        $this->actingAs($merchantUser)
            ->post(route('merchant.orders.verify-payment', $order), [
                'confirmed_amount' => $order->total,
            ])
            ->assertRedirect();

        $order->refresh();
        $this->assertSame('processing', $order->order_status);
        $this->assertSame('paid', $order->payment_status);
        $this->assertNotNull($order->pickup_code);
        $pickupCode = $order->pickup_code;

        $this->post(route('merchant.orders.ready', $order))->assertRedirect();
        $this->post(route('merchant.orders.complete', $order), ['pickup_code' => $pickupCode])
            ->assertRedirect();

        $order->refresh();
        $this->assertSame('completed', $order->order_status);
        $this->assertNull($order->pickup_code);

        $this->post(route('merchant.orders.complete', $order), ['pickup_code' => $pickupCode])
            ->assertStatus(409);

        $this->actingAs($student)
            ->post(route('orders.review', $order), ['rating' => 5, 'comment' => 'Enak'])
            ->assertRedirect();
        $this->assertDatabaseHas('reviews', [
            'order_id' => $order->id,
            'user_id' => $student->id,
            'rating' => 5,
        ]);
    }

    public function test_merchant_cannot_verify_a_payment_with_a_different_amount(): void
    {
        $student = User::factory()->create();
        $merchant = MerchantProfile::factory()->create(['qris_image' => 'qris/test.png']);
        $category = Category::factory()->create(['is_active' => true]);
        $product = Product::factory()
            ->for($category)
            ->for($merchant, 'merchantProfile')
            ->create(['price' => 10000]);
        $cart = $student->carts()->create(['status' => 'active']);
        $cart->items()->create(['product_id' => $product->id, 'quantity' => 1]);

        $this->actingAs($student)->post(route('checkout.store'))->assertRedirect();
        $order = Order::query()->where('user_id', $student->id)->firstOrFail();
        $this->post(route('orders.claim-payment', $order))->assertRedirect();

        $this->actingAs($merchant->user)
            ->from(route('merchant.orders.show', $order))
            ->post(route('merchant.orders.verify-payment', $order), [
                'confirmed_amount' => (float) $order->total + 1,
            ])
            ->assertRedirect(route('merchant.orders.show', $order))
            ->assertSessionHasErrors('confirmed_amount');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'order_status' => 'awaiting_merchant',
            'payment_status' => 'pending',
        ]);
    }
}
