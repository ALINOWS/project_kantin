<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\KategoriController;
use App\Http\Controllers\MerchantProfileController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\QrCodeController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::get('/kategori', [KategoriController::class, 'index'])->name('kategori.index');
Route::get('/kategori/{kategori}', [KategoriController::class, 'show'])->name('kategori.show');
Route::get('/produk', [ProductController::class, 'index'])->name('produk.index');
Route::get('/produk/{product}', [ProductController::class, 'show'])->name('produk.show');

Route::middleware('auth')->group(function (): void {
    Route::apiResource('users', UserController::class);
    Route::apiResource('kategori', KategoriController::class)->except(['index', 'show']);
    Route::apiResource('merchant-profiles', MerchantProfileController::class)
        ->parameters(['merchant-profiles' => 'merchantProfile']);
    Route::apiResource('produk', ProductController::class)
        ->except(['index', 'show'])
        ->parameters(['produk' => 'product']);
    Route::patch('/produk/{product}/ketersediaan', [ProductController::class, 'availability'])
        ->name('produk.availability');
    Route::apiResource('qr-codes', QrCodeController::class)
        ->parameters(['qr-codes' => 'qrCode']);

    Route::get('/keranjang', [CartController::class, 'show'])->name('keranjang.show');
    Route::post('/keranjang/item', [CartController::class, 'addItem'])->name('keranjang.items.store');
    Route::patch('/keranjang/item/{cartItem}', [CartController::class, 'updateItem'])
        ->name('keranjang.items.update');
    Route::delete('/keranjang/item/{cartItem}', [CartController::class, 'removeItem'])
        ->name('keranjang.items.destroy');

    Route::get('/pesanan', [OrderController::class, 'index'])->name('pesanan.index');
    Route::post('/pesanan', [OrderController::class, 'store'])->name('pesanan.store');
    Route::get('/pesanan/{order}', [OrderController::class, 'show'])->name('pesanan.show');
    Route::patch('/pesanan/{order}/status', [OrderController::class, 'updateStatus'])
        ->name('pesanan.status.update');
    Route::post('/pesanan/{order}/ambil', [OrderController::class, 'claimPickup'])
        ->name('pesanan.pickup');

    Route::get('/pembayaran', [PaymentController::class, 'index'])->name('pembayaran.index');
    Route::get('/pembayaran/{payment}', [PaymentController::class, 'show'])->name('pembayaran.show');
    Route::post('/pembayaran/{payment}/saya-sudah-bayar', [PaymentController::class, 'claimPaid'])
        ->name('pembayaran.claim');
    Route::patch('/pembayaran/{payment}/verifikasi', [PaymentController::class, 'verify'])
        ->name('pembayaran.verify');

    Route::get('/ulasan', [ReviewController::class, 'index'])->name('ulasan.index');
    Route::post('/ulasan', [ReviewController::class, 'store'])->name('ulasan.store');
    Route::delete('/ulasan/{review}', [ReviewController::class, 'destroy'])->name('ulasan.destroy');

    Route::get('/notifikasi', [NotificationController::class, 'index'])->name('notifikasi.index');
    Route::patch('/notifikasi/{notification}/dibaca', [NotificationController::class, 'markRead'])
        ->name('notifikasi.read');
    Route::get('/audit-log', [AuditLogController::class, 'index'])->name('audit-log.index');
});
