<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\QrCodeController as AdminQrCodeController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Auth\RegistrationController;
use App\Http\Controllers\Auth\SessionController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\Merchant\DashboardController as MerchantDashboardController;
use App\Http\Controllers\Merchant\OrderController as MerchantOrderController;
use App\Http\Controllers\Merchant\SettingsController as MerchantSettingsController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::get('/', [MenuController::class, 'index'])->name('menu.index');

Route::middleware('guest')->group(function () {
    Route::get('/login', [SessionController::class, 'create'])->name('login');
    Route::post('/login', [SessionController::class, 'store'])->name('login.store');
    Route::get('/register', [RegistrationController::class, 'createStudent'])->name('register');
    Route::post('/register', [RegistrationController::class, 'storeStudent'])->name('register.store');
    Route::get('/register-merchant', [RegistrationController::class, 'createMerchant'])->name('register.merchant');
    Route::post('/register-merchant', [RegistrationController::class, 'storeMerchant'])->name('register.merchant.store');
});

Route::post('/logout', [SessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::post('/notifications/{notification}/read', [NotificationController::class, 'read'])
    ->middleware('auth')
    ->name('notifications.read');
Route::get('/q/{token}', [AdminQrCodeController::class, 'scan'])->name('qrcode.scan');
Route::get('/menu/{product:slug}', [MenuController::class, 'show'])->name('menu.show');

Route::middleware(['auth', 'role:student'])->group(function () {
    Route::get('/keranjang', [CartController::class, 'index'])->name('cart.index');
    Route::post('/keranjang/items', [CartController::class, 'store'])->name('cart.items.store');
    Route::patch('/keranjang/items/{cartItem}', [CartController::class, 'update'])->name('cart.items.update');
    Route::delete('/keranjang/items/{cartItem}', [CartController::class, 'destroy'])->name('cart.items.destroy');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
    Route::get('/pesanan', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/pesanan/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('/pesanan/{order}/sudah-bayar', [OrderController::class, 'claimPayment'])->name('orders.claim-payment');
    Route::post('/pesanan/{order}/ulasan', [OrderController::class, 'review'])->name('orders.review');
});

Route::middleware(['auth', 'role:merchant'])->prefix('merchant')->name('merchant.')->group(function () {
    Route::get('/', MerchantDashboardController::class)->name('dashboard');
    Route::get('/pesanan', [MerchantOrderController::class, 'index'])->name('orders.index');
    Route::get('/pesanan/{order}', [MerchantOrderController::class, 'show'])->name('orders.show');
    Route::post('/pesanan/{order}/verifikasi', [MerchantOrderController::class, 'verifyPayment'])->name('orders.verify-payment');
    Route::post('/pesanan/{order}/tolak', [MerchantOrderController::class, 'rejectPayment'])->name('orders.reject-payment');
    Route::post('/pesanan/{order}/siap-diambil', [MerchantOrderController::class, 'markReady'])->name('orders.ready');
    Route::post('/pesanan/{order}/ambil', [MerchantOrderController::class, 'completePickup'])->name('orders.complete');
    Route::get('/produk', [ProductController::class, 'index'])->name('products.index');
    Route::get('/produk/tambah', [ProductController::class, 'create'])->name('products.create');
    Route::post('/produk', [ProductController::class, 'store'])->name('products.store');
    Route::get('/produk/{product}/ubah', [ProductController::class, 'edit'])->name('products.edit');
    Route::put('/produk/{product}', [ProductController::class, 'update'])->name('products.update');
    Route::delete('/produk/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
    Route::get('/pengaturan', [MerchantSettingsController::class, 'edit'])->name('settings.edit');
    Route::put('/pengaturan', [MerchantSettingsController::class, 'update'])->name('settings.update');
    Route::get('/laporan', [MerchantDashboardController::class, 'reports'])->name('reports');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', AdminDashboardController::class)->name('dashboard');
    Route::get('/pengguna', [AdminUserController::class, 'index'])->name('users.index');
    Route::patch('/pengguna/{user}', [AdminUserController::class, 'update'])->name('users.update');
    Route::get('/qr-menu', [AdminQrCodeController::class, 'index'])->name('qrcodes.index');
    Route::post('/qr-menu', [AdminQrCodeController::class, 'store'])->name('qrcodes.store');
    Route::patch('/qr-menu/{qrCode}', [AdminQrCodeController::class, 'toggle'])->name('qrcodes.toggle');
    Route::get('/laporan', [AdminDashboardController::class, 'reports'])->name('reports');
    Route::get('/audit', [AdminDashboardController::class, 'audit'])->name('audit');
});

Route::middleware(['auth', 'role:admin'])->prefix('manage')->name('manage.')->group(function () {
    Route::resource('categories', CategoryController::class)->except(['show']);
    Route::resource('products', ProductController::class)->except(['show']);
});
