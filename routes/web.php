<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Owner;
use App\Http\Controllers\Site;
use Illuminate\Support\Facades\Route;

/* ----------------------------- Publik ----------------------------- */
Route::get('/', [Site\HomeController::class, 'index'])->name('home');

Route::get('/apartments', [Site\ApartmentController::class, 'index'])->name('apartments.index');
Route::get('/apartments/{apartment}', [Site\ApartmentController::class, 'show'])->name('apartments.show');
Route::get('/apartments/{apartment}/booking', [Site\BookingController::class, 'create'])->name('booking.create');
Route::post('/apartments/{apartment}/booking', [Site\BookingController::class, 'store'])
    ->middleware('throttle:10,1')->name('booking.store');

Route::get('/cek-booking', [Site\BookingController::class, 'lookupForm'])->name('booking.lookup');
Route::post('/cek-booking', [Site\BookingController::class, 'lookup'])->middleware('throttle:10,1');

// Invoice & pembayaran memakai token acak (bukan kode booking yang mudah ditebak).
Route::prefix('invoice/{booking}')->group(function () {
    Route::get('/', [Site\InvoiceController::class, 'show'])->name('invoice.show');
    Route::get('/notify', [Site\InvoiceController::class, 'notify'])->name('booking.notify');
    Route::get('/pay', [Site\PaymentController::class, 'show'])->name('payment.show');
    Route::post('/snap', [Site\PaymentController::class, 'snap'])->middleware('throttle:10,1')->name('payment.snap');
    // Hanya halaman tampilan: status dibayar HANYA ditentukan oleh notifikasi Midtrans / admin.
    Route::get('/success', [Site\PaymentController::class, 'success'])->name('payment.success');
    Route::get('/pending', [Site\PaymentController::class, 'pending'])->name('payment.pending');
});
Route::post('/midtrans/notification', [Site\PaymentController::class, 'notification'])->name('midtrans.notification');

Route::get('/jual-beli', [Site\SaleController::class, 'index'])->name('sale.index');
Route::get('/jual-beli/{saleUnit}', [Site\SaleController::class, 'show'])->name('sale.show');
Route::get('/titip-jual', [Site\SaleController::class, 'requestForm'])->name('sale.request');
Route::post('/titip-jual', [Site\SaleController::class, 'requestStore'])->middleware('throttle:5,1')->name('sale.request.store');

/* ------------------------------ Owner ------------------------------ */
Route::prefix('owner')->name('owner.')->group(function () {
    Route::middleware('guest:owner')->group(function () {
        Route::get('login', [Owner\AuthController::class, 'loginForm'])->name('login');
        Route::post('login', [Owner\AuthController::class, 'login'])->middleware('throttle:5,1');
        Route::get('register', [Owner\AuthController::class, 'registerForm'])->name('register');
        Route::post('register', [Owner\AuthController::class, 'register'])->middleware('throttle:5,1');
    });

    Route::middleware('auth:owner')->group(function () {
        Route::post('logout', [Owner\AuthController::class, 'logout'])->name('logout');
        Route::get('dashboard', [Owner\DashboardController::class, 'index'])->name('dashboard');
        Route::get('apartments', [Owner\ApartmentController::class, 'index'])->name('apartments');
        Route::post('apartments', [Owner\ApartmentController::class, 'store'])->name('apartments.store');
        Route::get('bookings', [Owner\BookingController::class, 'index'])->name('bookings');
        Route::get('settlement', [Owner\SettlementController::class, 'index'])->name('settlement');
        Route::post('settlement', [Owner\SettlementController::class, 'store'])->name('settlement.store');
    });
});

/* ------------------------------ Admin ------------------------------ */
Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest:admin')->group(function () {
        Route::get('login', [Admin\AuthController::class, 'loginForm'])->name('login');
        Route::post('login', [Admin\AuthController::class, 'login'])->middleware('throttle:5,1');
    });

    Route::middleware('auth:admin')->group(function () {
        Route::post('logout', [Admin\AuthController::class, 'logout'])->name('logout');
        Route::get('dashboard', [Admin\DashboardController::class, 'index'])->name('dashboard');

        Route::get('apartments', [Admin\ApartmentController::class, 'index'])->name('apartments');
        Route::post('apartments', [Admin\ApartmentController::class, 'store'])->name('apartments.store');
        Route::get('apartments/{apartment}/edit', [Admin\ApartmentController::class, 'edit'])->name('apartments.edit');
        Route::put('apartments/{apartment}', [Admin\ApartmentController::class, 'update'])->name('apartments.update');
        Route::delete('apartments/{apartment}/images/{image}', [Admin\ApartmentController::class, 'destroyImage'])->name('apartments.images.destroy');
        Route::post('apartments/{apartment}/images/{image}/cover', [Admin\ApartmentController::class, 'setCover'])->name('apartments.images.cover');

        Route::get('owners', [Admin\OwnerController::class, 'index'])->name('owners');
        Route::post('owners/{owner}/status', [Admin\OwnerController::class, 'status'])->name('owners.status');

        Route::get('bookings', [Admin\BookingController::class, 'index'])->name('bookings');
        Route::post('bookings/{booking:id}/paid', [Admin\BookingController::class, 'markPaid'])->name('bookings.paid');
        Route::post('bookings/{booking:id}/cancel', [Admin\BookingController::class, 'cancel'])->name('bookings.cancel');

        Route::get('commission', [Admin\ReportController::class, 'commission'])->name('commission');
        Route::get('reports', [Admin\ReportController::class, 'index'])->name('reports');
        Route::get('reports/export', [Admin\ReportController::class, 'export'])->name('reports.export');

        Route::get('settlements', [Admin\SettlementController::class, 'index'])->name('settlements');
        Route::post('settlements/pay-owner/{owner}', [Admin\SettlementController::class, 'payOwner'])->name('settlements.pay-owner');
        Route::post('settlements/{settlement}/{action}', [Admin\SettlementController::class, 'transition'])
            ->whereIn('action', ['approve', 'paid', 'reject'])->name('settlements.transition');

        Route::get('promos', [Admin\PromoController::class, 'index'])->name('promos');
        Route::post('promos', [Admin\PromoController::class, 'store'])->name('promos.store');
        Route::post('promos/{promo}/toggle', [Admin\PromoController::class, 'toggle'])->name('promos.toggle');
        Route::delete('promos/{promo}', [Admin\PromoController::class, 'destroy'])->name('promos.destroy');

        Route::get('sale-units', [Admin\SaleUnitController::class, 'index'])->name('sale-units');
        Route::post('sale-units', [Admin\SaleUnitController::class, 'store'])->name('sale-units.store');
        Route::get('sale-units/{saleUnit}/edit', [Admin\SaleUnitController::class, 'edit'])->name('sale-units.edit');
        Route::put('sale-units/{saleUnit}', [Admin\SaleUnitController::class, 'update'])->name('sale-units.update');
        Route::post('sale-units/{saleUnit}/status', [Admin\SaleUnitController::class, 'status'])->name('sale-units.status');
        Route::delete('sale-units/{saleUnit}', [Admin\SaleUnitController::class, 'destroy'])->name('sale-units.destroy');
        Route::delete('sale-units/{saleUnit}/images/{image}', [Admin\SaleUnitController::class, 'destroyImage'])->name('sale-units.images.destroy');
        Route::post('sale-units/{saleUnit}/images/{image}/cover', [Admin\SaleUnitController::class, 'setCover'])->name('sale-units.images.cover');

        Route::get('sale-requests', [Admin\SaleRequestController::class, 'index'])->name('sale-requests');
        Route::get('sale-requests/{saleRequest}', [Admin\SaleRequestController::class, 'show'])->name('sale-requests.show');
        Route::post('sale-requests/{saleRequest}/status', [Admin\SaleRequestController::class, 'status'])->name('sale-requests.status');
        Route::post('sale-requests/{saleRequest}/note', [Admin\SaleRequestController::class, 'note'])->name('sale-requests.note');
        Route::post('sale-requests/{saleRequest}/survey', [Admin\SaleRequestController::class, 'survey'])->name('sale-requests.survey');
        Route::post('sale-requests/{saleRequest}/offer', [Admin\SaleRequestController::class, 'offer'])->name('sale-requests.offer');
        Route::post('sale-requests/{saleRequest}/photos', [Admin\SaleRequestController::class, 'photos'])->name('sale-requests.photos');
        Route::delete('sale-requests/{saleRequest}/photos/{photo}', [Admin\SaleRequestController::class, 'destroyPhoto'])->name('sale-requests.photos.destroy');
        Route::post('sale-requests/{saleRequest}/publish', [Admin\SaleRequestController::class, 'publish'])->name('sale-requests.publish');
        Route::post('sale-requests/{saleRequest}/close', [Admin\SaleRequestController::class, 'close'])->name('sale-requests.close');
        Route::delete('sale-requests/{saleRequest}', [Admin\SaleRequestController::class, 'destroy'])->name('sale-requests.destroy');

        Route::get('sale-commissions', [Admin\SaleCommissionController::class, 'index'])->name('sale-commissions');
        Route::post('sale-commissions', [Admin\SaleCommissionController::class, 'store'])->name('sale-commissions.store');
        Route::get('sale-commissions/{saleCommission}/edit', [Admin\SaleCommissionController::class, 'edit'])->name('sale-commissions.edit');
        Route::put('sale-commissions/{saleCommission}', [Admin\SaleCommissionController::class, 'update'])->name('sale-commissions.update');
        Route::delete('sale-commissions/{saleCommission}', [Admin\SaleCommissionController::class, 'destroy'])->name('sale-commissions.destroy');
        Route::get('sale-commissions/{saleCommission}/invoice', [Admin\SaleCommissionController::class, 'invoice'])->name('sale-commissions.invoice');
    });
});
