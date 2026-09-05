<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\BarController;
use App\Http\Controllers\CashSessionController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FloorPlanController;
use App\Http\Controllers\KitchenController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderItemController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\RestaurantTableController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ZoneController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::resource('users', UserController::class)->except(['show', 'destroy']);
    Route::patch('users/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('users.toggle-active');

    Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
    Route::patch('roles/{role}', [RoleController::class, 'update'])->name('roles.update');

    Route::get('audit', [AuditLogController::class, 'index'])->name('audit.index');

    Route::resource('zones', ZoneController::class)->except(['show']);
    Route::resource('tables', RestaurantTableController::class)->except(['show'])->parameters(['tables' => 'table']);
    Route::resource('categories', CategoryController::class)->except(['show']);
    Route::resource('products', ProductController::class);
    Route::resource('customers', CustomerController::class)->except(['show']);

    Route::get('cash-sessions', [CashSessionController::class, 'index'])->name('cash-sessions.index');
    Route::get('cash-sessions/create', [CashSessionController::class, 'create'])->name('cash-sessions.create');
    Route::post('cash-sessions', [CashSessionController::class, 'store'])->name('cash-sessions.store');
    Route::get('cash-sessions/{cashSession}', [CashSessionController::class, 'show'])->name('cash-sessions.show');
    Route::post('cash-sessions/{cashSession}/movements', [CashSessionController::class, 'storeMovement'])->name('cash-sessions.movements.store');
    Route::get('cash-sessions/{cashSession}/close', [CashSessionController::class, 'closeForm'])->name('cash-sessions.close-form');
    Route::post('cash-sessions/{cashSession}/close', [CashSessionController::class, 'close'])->name('cash-sessions.close');

    Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('orders/create', [OrderController::class, 'create'])->name('orders.create');
    Route::post('orders', [OrderController::class, 'store'])->name('orders.store');
    Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('orders/{order}/discount', [OrderController::class, 'discount'])->name('orders.discount');
    Route::post('orders/{order}/send', [OrderController::class, 'send'])->name('orders.send');
    Route::post('orders/{order}/serve', [OrderController::class, 'serve'])->name('orders.serve');
    Route::post('orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');

    Route::post('orders/{order}/items', [OrderItemController::class, 'store'])->name('orders.items.store');
    Route::patch('orders/{order}/items/{item}', [OrderItemController::class, 'update'])->name('orders.items.update');
    Route::delete('orders/{order}/items/{item}', [OrderItemController::class, 'destroy'])->name('orders.items.destroy');
    Route::post('orders/{order}/items/{item}/cancel', [OrderItemController::class, 'cancel'])->name('orders.items.cancel');

    Route::post('orders/{order}/payments', [PaymentController::class, 'store'])->name('orders.payments.store');
    Route::post('payments/{payment}/refund', [PaymentController::class, 'refund'])->name('payments.refund');

    Route::get('orders/{order}/receipt', [ReceiptController::class, 'order'])->name('orders.receipt');
    Route::get('cash-sessions/{cashSession}/report', [ReceiptController::class, 'cashSession'])->name('cash-sessions.report');

    Route::get('floor-plan', [FloorPlanController::class, 'index'])->name('floor-plan.index');
    Route::post('floor-plan/tables/{table}/mark-available', [FloorPlanController::class, 'markAvailable'])->name('floor-plan.mark-available');
    Route::post('floor-plan/tables/{table}/transfer', [FloorPlanController::class, 'transfer'])->name('floor-plan.transfer');

    Route::get('kitchen', KitchenController::class)->name('kitchen.index');
    Route::get('bar', BarController::class)->name('bar.index');

    Route::get('reservations', [ReservationController::class, 'index'])->name('reservations.index');
    Route::get('reservations/create', [ReservationController::class, 'create'])->name('reservations.create');
    Route::post('reservations', [ReservationController::class, 'store'])->name('reservations.store');
    Route::get('reservations/{reservation}', [ReservationController::class, 'show'])->name('reservations.show');
    Route::get('reservations/{reservation}/edit', [ReservationController::class, 'edit'])->name('reservations.edit');
    Route::put('reservations/{reservation}', [ReservationController::class, 'update'])->name('reservations.update');
    Route::post('reservations/{reservation}/confirm', [ReservationController::class, 'confirm'])->name('reservations.confirm');
    Route::post('reservations/{reservation}/seat', [ReservationController::class, 'seat'])->name('reservations.seat');
    Route::post('reservations/{reservation}/complete', [ReservationController::class, 'complete'])->name('reservations.complete');
    Route::post('reservations/{reservation}/cancel', [ReservationController::class, 'cancel'])->name('reservations.cancel');
    Route::post('reservations/{reservation}/no-show', [ReservationController::class, 'noShow'])->name('reservations.no-show');
    Route::post('reservations/{reservation}/create-order', [ReservationController::class, 'createOrder'])->name('reservations.create-order');
});
