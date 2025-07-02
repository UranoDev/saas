<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;
use App\Http\Controllers\ShopifyController;

Route::get('/shopify/orders', [ShopifyController::class, 'index'])->name('shopify.orders');
Route::post('/shopify/orders', [ShopifyController::class, 'fetchOrders'])->name('shopify.fetchOrders');


Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');
});


Route::get('/shopify/order/{id}', [ShopifyController::class, 'orderDetail'])->name('shopify.orderDetail');
Route::post('/shopify/qmf-refresh', [ShopifyController::class, 'qmfRefresh'])->name('shopify.qmfRefresh');


Route::get('/shopify/webhooks', [ShopifyController::class, 'webhooksForm'])->name('shopify.webhooksForm');
Route::post('/shopify/webhooks/list', [ShopifyController::class, 'listWebhooks'])->name('shopify.listWebhooks');
Route::post('/shopify/webhooks/delete', [ShopifyController::class, 'deleteWebhook'])->name('shopify.deleteWebhook');
Route::get('/shopify/webhooks/create', [ShopifyController::class, 'webhooksCreateForm'])->name('shopify.webhooks_create_form'); // Formulario para crear un webhook (con URL personalizada)
Route::post('/shopify/webhooks/create', [ShopifyController::class, 'createWebhook'])->name('shopify.createWebhook'); // Acción que envía la creación del webhook




require __DIR__.'/auth.php';
