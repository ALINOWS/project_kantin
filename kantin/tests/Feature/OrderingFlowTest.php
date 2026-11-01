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
        $this->from(route('orders.show', $order))
            ->post(route('orders.review', $order), ['rating' => 4])
            ->assertSessionHasErrors('review');
        $this->assertDatabaseCount('reviews', 1);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'order.payment_claimed',
            'entity_id' => $order->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'order.payment_verified',
            'entity_id' => $order->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'order.completed',
            'entity_id' => $order->id,
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

    public function test_student_order_history_displays_orders_and_hides_other_students_orders(): void
    {
        $student = User::factory()->create();
        $otherStudent = User::factory()->create();
        $merchant = MerchantProfile::factory()->create();
        $order = $this->createOrder($student, $merchant, 'KM-OWNED-ORDER');
        $otherOrder = $this->createOrder($otherStudent, $merchant, 'KM-OTHER-ORDER');

        $this->actingAs($student)
            ->get(route('orders.index'))
            ->assertOk()
            ->assertSee('Pesanan saya')
            ->assertSee($order->order_number)
            ->assertDontSee($otherOrder->order_number);

        $this->get(route('orders.show', $otherOrder))->assertNotFound();
    }

    public function test_expiration_command_updates_only_unpaid_orders_past_the_deadline(): void
    {
        $student = User::factory()->create();
        $merchant = MerchantProfile::factory()->create();
        $expired = $this->createOrder($student, $merchant, 'KM-EXPIRED-ORDER', [
            'expires_at' => now()->subMinute(),
        ]);
        $stillPending = $this->createOrder($student, $merchant, 'KM-PENDING-ORDER', [
            'expires_at' => now()->addMinute(),
        ]);

        $this->artisan('kantin:expire-unpaid-orders')->assertExitCode(0);

        $this->assertDatabaseHas('orders', [
            'id' => $expired->id,
            'order_status' => 'expired',
            'payment_status' => 'expired',
        ]);
        $this->assertDatabaseHas('payments', [
            'order_id' => $expired->id,
            'status' => 'expired',
        ]);
        $this->assertDatabaseHas('orders', [
            'id' => $stillPending->id,
            'order_status' => 'pending_payment',
            'payment_status' => 'pending',
        ]);
    }

    public function test_student_cannot_checkout_without_qris_or_add_another_stalls_menu(): void
    {
        $student = User::factory()->create();
        $firstStall = MerchantProfile::factory()->create();
        $secondStall = MerchantProfile::factory()->create(['qris_image' => 'qris/second.png']);
        $category = Category::factory()->create(['is_active' => true]);
        $firstProduct = Product::factory()
            ->for($category)
            ->for($firstStall, 'merchantProfile')
            ->create();
        $secondProduct = Product::factory()
            ->for($category)
            ->for($secondStall, 'merchantProfile')
            ->create();

        $this->actingAs($student)
            ->post(route('cart.items.store'), ['product_id' => $firstProduct->id, 'quantity' => 1])
            ->assertRedirect(route('cart.index'));
        $this->post(route('cart.items.store'), ['product_id' => $secondProduct->id, 'quantity' => 1])
            ->assertSessionHasErrors('product_id');

        $this->from(route('cart.index'))
            ->post(route('checkout.store'))
            ->assertRedirect(route('cart.index'))
            ->assertSessionHasErrors('cart');
        $this->assertDatabaseCount('orders', 0);

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $firstProduct->id,
            'quantity' => 1,
        ]);
        $this->assertDatabaseMissing('cart_items', ['product_id' => $secondProduct->id]);
    }

    public function test_checkout_rejects_totals_that_exceed_the_database_money_column(): void
    {
        $student = User::factory()->create();
        $merchant = MerchantProfile::factory()->create(['qris_image' => 'qris/test.png']);
        $category = Category::factory()->create(['is_active' => true]);
        $product = Product::factory()
            ->for($category)
            ->for($merchant, 'merchantProfile')
            ->create(['price' => '9999999999.99']);
        $cart = $student->carts()->create(['status' => 'active']);
        $cart->items()->create(['product_id' => $product->id, 'quantity' => 2]);

        $this->actingAs($student)
            ->from(route('cart.index'))
            ->post(route('checkout.store'))
            ->assertRedirect(route('cart.index'))
            ->assertSessionHasErrors('cart');

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseHas('cart_items', [
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);
    }

    public function test_merchant_can_reject_a_claim_and_student_is_notified(): void
    {
        $student = User::factory()->create();
        $merchant = MerchantProfile::factory()->create(['qris_image' => 'qris/test.png']);
        $category = Category::factory()->create(['is_active' => true]);
        $product = Product::factory()
            ->for($category)
            ->for($merchant, 'merchantProfile')
            ->create();
        $cart = $student->carts()->create(['status' => 'active']);
        $cart->items()->create(['product_id' => $product->id, 'quantity' => 1]);

        $this->actingAs($student)->post(route('checkout.store'))->assertRedirect();
        $order = Order::query()->where('user_id', $student->id)->firstOrFail();
        $this->post(route('orders.claim-payment', $order))->assertRedirect();

        $this->actingAs($merchant->user)
            ->post(route('merchant.orders.reject-payment', $order), [
                'reason' => 'Transaksi tidak ditemukan pada mutasi.',
            ])
            ->assertRedirect(route('merchant.orders.index'));

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'order_status' => 'cancelled',
            'payment_status' => 'failed',
        ]);
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'status' => 'failed',
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $student->id,
            'type' => 'payment_rejected',
        ]);
    }

    public function test_merchant_report_counts_only_its_paid_sales_and_top_items(): void
    {
        $student = User::factory()->create();
        $merchant = MerchantProfile::factory()->create();
        $otherMerchant = MerchantProfile::factory()->create();
        $category = Category::factory()->create();
        $product = Product::factory()
            ->for($category)
            ->for($merchant, 'merchantProfile')
            ->create(['name' => 'Nasi Uduk']);
        $otherProduct = Product::factory()
            ->for($category)
            ->for($otherMerchant, 'merchantProfile')
            ->create(['name' => 'Menu Kantin Lain']);

        $order = $this->createOrder($student, $merchant, 'KM-REPORT-ORDER', [
            'order_status' => 'completed',
            'payment_status' => 'paid',
            'paid_at' => now(),
            'completed_at' => now(),
        ]);
        $order->payment()->update(['status' => 'paid', 'paid_at' => now()]);
        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'unit_price' => $product->price,
            'quantity' => 3,
            'subtotal' => (float) $product->price * 3,
        ]);

        $otherOrder = $this->createOrder(User::factory()->create(), $otherMerchant, 'KM-OTHER-REPORT', [
            'order_status' => 'completed',
            'payment_status' => 'paid',
            'paid_at' => now(),
            'completed_at' => now(),
        ]);
        $otherOrder->payment()->update(['status' => 'paid', 'paid_at' => now()]);
        $otherOrder->items()->create([
            'product_id' => $otherProduct->id,
            'product_name' => $otherProduct->name,
            'unit_price' => $otherProduct->price,
            'quantity' => 7,
            'subtotal' => (float) $otherProduct->price * 7,
        ]);

        $this->actingAs($merchant->user)
            ->get(route('merchant.reports', [
                'start' => today()->toDateString(),
                'end' => today()->toDateString(),
            ]))
            ->assertOk()
            ->assertSee('Nasi Uduk')
            ->assertSee('3 terjual')
            ->assertDontSee('Menu Kantin Lain')
            ->assertDontSee('KM-OTHER-REPORT');
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createOrder(User $student, MerchantProfile $merchant, string $number, array $attributes = []): Order
    {
        $uniqueCode = 123 + Order::query()
            ->where('merchant_id', $merchant->id)
            ->where('payment_status', 'pending')
            ->count();
        $order = Order::query()->create([
            'order_number' => $number,
            'user_id' => $student->id,
            'merchant_id' => $merchant->id,
            'subtotal' => 10000,
            'unique_code' => $uniqueCode,
            'total' => 10000 + $uniqueCode,
            'order_status' => 'pending_payment',
            'payment_status' => 'pending',
            'expires_at' => now()->addMinutes(15),
            ...$attributes,
        ]);

        $order->payment()->create([
            'provider' => 'static_qris',
            'payment_method' => 'QRIS',
            'amount' => $order->total,
            'status' => 'pending',
            'expires_at' => $order->expires_at,
        ]);

        return $order;
    }
}
