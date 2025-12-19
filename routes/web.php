<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\CashierSessionController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IngredientController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ServerController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return Inertia::render('Welcome');
});

/*
|--------------------------------------------------------------------------
| Guest Routes
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Users
    Route::resource('users', UserController::class);
    Route::patch('/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');

    // Servers
    Route::resource('servers', ServerController::class);
    Route::patch('/servers/{server}/toggle-status', [ServerController::class, 'toggleStatus'])->name('servers.toggle-status');
    Route::get('/servers/{server}/stats', [ServerController::class, 'stats'])->name('servers.stats');

    // Categories
    Route::resource('categories', CategoryController::class)->except(['show']);

    // Products
    Route::resource('products', ProductController::class);
    Route::patch('/products/{product}/toggle-status', [ProductController::class, 'toggleStatus'])->name('products.toggle-status');

    // Stocks / Ingredients
    Route::resource('stocks', IngredientController::class)->parameters(['stocks' => 'ingredient']);
    Route::post('/stocks/{ingredient}/add', [IngredientController::class, 'addStock'])->name('stocks.add');
    Route::post('/stocks/{ingredient}/remove', [IngredientController::class, 'removeStock'])->name('stocks.remove');
    Route::get('/stocks/{ingredient}/movements', [IngredientController::class, 'movements'])->name('stocks.movements');

    // Cashier Sessions
    Route::resource('sessions', CashierSessionController::class)->only(['index', 'create', 'store', 'show']);
    Route::post('/sessions/{session}/close', [CashierSessionController::class, 'close'])->name('sessions.close');
    Route::get('/sessions/current', [CashierSessionController::class, 'current'])->name('sessions.current');

    // Orders
    Route::resource('orders', OrderController::class)->except(['destroy']);
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
    Route::post('/orders/{order}/pay', [OrderController::class, 'pay'])->name('orders.pay');
    Route::post('/orders/{order}/items', [OrderController::class, 'addItem'])->name('orders.add-item');
    Route::delete('/orders/{order}/items/{item}', [OrderController::class, 'removeItem'])->name('orders.remove-item');

    // Settings
    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');
    Route::post('/settings/logo', [SettingController::class, 'updateLogo'])->name('settings.logo');
    Route::put('/settings/order-options', [SettingController::class, 'updateOrderOptions'])->name('settings.order-options');
    Route::put('/settings/exchange-options', [SettingController::class, 'updateExchangeOptions'])->name('settings.exchange-options');
    Route::put('/settings/default-currency', [SettingController::class, 'setDefaultCurrency'])->name('settings.default-currency');
    Route::post('/settings/currencies', [SettingController::class, 'storeCurrency'])->name('settings.currencies.store');
    Route::put('/settings/currencies/{currency}', [SettingController::class, 'updateCurrency'])->name('settings.currencies.update');
    Route::delete('/settings/currencies/{currency}', [SettingController::class, 'deleteCurrency'])->name('settings.currencies.delete');
    Route::post('/settings/exchange-rates', [SettingController::class, 'storeExchangeRate'])->name('settings.exchange-rates.store');
    Route::put('/settings/exchange-rates/{exchangeRate}', [SettingController::class, 'updateExchangeRate'])->name('settings.exchange-rates.update');
    Route::delete('/settings/exchange-rates/{exchangeRate}', [SettingController::class, 'deleteExchangeRate'])->name('settings.exchange-rates.delete');
    Route::post('/settings/update-prices', [SettingController::class, 'updateProductPrices'])->name('settings.update-prices');
    Route::post('/settings/users', [SettingController::class, 'storeUser'])->name('settings.users.store');
    Route::put('/settings/users/{user}', [SettingController::class, 'updateUser'])->name('settings.users.update');
    Route::delete('/settings/users/{user}', [SettingController::class, 'deleteUser'])->name('settings.users.delete');

    // Reports
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
});
