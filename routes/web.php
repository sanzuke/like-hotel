<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\AdminAuthController;
use App\Http\Controllers\Auth\OwnerAuthController;
use App\Http\Controllers\Auth\OwnerGoogleController;
use App\Http\Controllers\Owner;
use App\Http\Controllers\Site;
use Illuminate\Support\Facades\Route;

/*
| Situs publik
*/
Route::get('/', [Site\ApartmentController::class, 'home'])->name('home');
Route::get('/apartments', [Site\ApartmentController::class, 'index'])->name('apartments.index');
Route::get('/apartments/{apartment}', [Site\ApartmentController::class, 'show'])->name('apartments.show');

Route::get('/apartments/{apartment}/booking', [Site\BookingController::class, 'create'])->name('booking.create');
Route::post('/apartments/{apartment}/booking', [Site\BookingController::class, 'store'])
    ->middleware('throttle:10,1')->name('booking.store');

Route::get('/cek-booking', [Site\CheckBookingController::class, 'form'])->name('booking.check');
Route::post('/cek-booking', [Site\CheckBookingController::class, 'lookup'])
    ->middleware('throttle:10,1')->name('booking.check.submit');

// Halaman booking dan invoice diakses lewat token acak, bukan kode booking.
Route::prefix('b/{token}')->group(function () {
    Route::get('/', [Site\BookingController::class, 'notify'])->name('booking.notify');
    Route::get('/invoice', [Site\PaymentController::class, 'invoice'])->name('invoice.show');
    Route::get('/bayar', [Site\PaymentController::class, 'pay'])->name('payment.show');
    Route::post('/snap', [Site\PaymentController::class, 'snap'])->middleware('throttle:10,1')->name('payment.snap');
    Route::get('/hasil', [Site\PaymentController::class, 'result'])->name('payment.result');
});

// Webhook Midtrans (dikecualikan dari CSRF di bootstrap/app.php, divalidasi via signature).
Route::post('/midtrans/notification', [Site\MidtransController::class, 'notification'])->name('midtrans.notification');

Route::get('/jual-beli', [Site\SaleController::class, 'index'])->name('sale-units.index');
Route::get('/jual-beli/{saleUnit}', [Site\SaleController::class, 'show'])->name('sale-units.show');
Route::get('/titip-jual', [Site\SaleController::class, 'requestForm'])->name('sale-request.create');
Route::post('/titip-jual', [Site\SaleController::class, 'requestStore'])
    ->middleware('throttle:5,1')->name('sale-request.store');

/*
| Owner
*/
Route::prefix('owner')->name('owner.')->group(function () {
    Route::middleware('guest:owner')->group(function () {
        Route::get('/login', [OwnerAuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [OwnerAuthController::class, 'login'])->name('login.submit');
        Route::get('/register', [OwnerAuthController::class, 'showRegister'])->name('register');
        Route::post('/register', [OwnerAuthController::class, 'register'])->middleware('throttle:5,1')->name('register.submit');
        Route::get('/auth/google', [OwnerGoogleController::class, 'redirect'])->middleware('throttle:10,1')->name('google.redirect');
        Route::get('/auth/google/callback', [OwnerGoogleController::class, 'callback'])->middleware('throttle:10,1')->name('google.callback');
    });

    Route::middleware('auth:owner')->group(function () {
        Route::post('/logout', [OwnerAuthController::class, 'logout'])->name('logout');
        Route::get('/', Owner\DashboardController::class)->name('dashboard');
        Route::get('/apartments', [Owner\ApartmentController::class, 'index'])->name('apartments.index');
        Route::post('/apartments', [Owner\ApartmentController::class, 'store'])->name('apartments.store');
        Route::get('/bookings', [Owner\BookingController::class, 'index'])->name('bookings.index');
        Route::get('/settlements', [Owner\SettlementController::class, 'index'])->name('settlements.index');
        Route::post('/settlements', [Owner\SettlementController::class, 'store'])->name('settlements.store');
    });
});

/*
| Admin
*/
Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest:admin')->group(function () {
        Route::get('/login', [AdminAuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [AdminAuthController::class, 'login'])->name('login.submit');
    });

    Route::middleware('auth:admin')->group(function () {
        Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');
        Route::get('/', Admin\DashboardController::class)->name('dashboard');
        Route::get('/password', [Admin\PasswordController::class, 'edit'])->name('password.edit');
        Route::put('/password', [Admin\PasswordController::class, 'update'])->middleware('throttle:5,1')->name('password.update');

        Route::get('/apartments', [Admin\ApartmentController::class, 'index'])->name('apartments.index');
        Route::post('/apartments', [Admin\ApartmentController::class, 'store'])->name('apartments.store');
        Route::get('/apartments/{apartment}/edit', [Admin\ApartmentController::class, 'edit'])->name('apartments.edit');
        Route::put('/apartments/{apartment}', [Admin\ApartmentController::class, 'update'])->name('apartments.update');
        Route::post('/apartments/{apartment}/images/{image}/cover', [Admin\ApartmentController::class, 'setCover'])->name('apartments.cover');
        Route::delete('/apartments/{apartment}/images/{image}', [Admin\ApartmentController::class, 'destroyImage'])->name('apartments.images.destroy');

        Route::get('/owners', [Admin\OwnerController::class, 'index'])->name('owners.index');
        Route::patch('/owners/{owner}/status', [Admin\OwnerController::class, 'updateStatus'])->name('owners.status');

        Route::get('/bookings', [Admin\BookingController::class, 'index'])->name('bookings.index');
        Route::post('/bookings/{booking}/paid', [Admin\BookingController::class, 'markPaid'])->name('bookings.paid');
        Route::post('/bookings/{booking}/cancel', [Admin\BookingController::class, 'cancel'])->name('bookings.cancel');

        Route::get('/promos', [Admin\PromoController::class, 'index'])->name('promos.index');
        Route::post('/promos', [Admin\PromoController::class, 'store'])->name('promos.store');
        Route::patch('/promos/{promo}/toggle', [Admin\PromoController::class, 'toggle'])->name('promos.toggle');
        Route::delete('/promos/{promo}', [Admin\PromoController::class, 'destroy'])->name('promos.destroy');

        Route::get('/komisi', [Admin\SettlementController::class, 'commission'])->name('commission');
        Route::get('/settlements', [Admin\SettlementController::class, 'index'])->name('settlements.index');
        Route::post('/settlements/owners/{owner}/pay', [Admin\SettlementController::class, 'payOwner'])->name('settlements.pay-owner');
        Route::post('/settlements/{settlement}/approve', [Admin\SettlementController::class, 'approve'])->name('settlements.approve');
        Route::post('/settlements/{settlement}/paid', [Admin\SettlementController::class, 'markPaid'])->name('settlements.paid');
        Route::post('/settlements/{settlement}/reject', [Admin\SettlementController::class, 'reject'])->name('settlements.reject');

        Route::get('/laporan', [Admin\ReportController::class, 'index'])->name('reports.index');
        Route::get('/laporan/export', [Admin\ReportController::class, 'export'])->name('reports.export');

        Route::get('/laporan-penjualan', [Admin\SalesReportController::class, 'index'])->name('sales-report.index');
        Route::get('/laporan-penjualan/export', [Admin\SalesReportController::class, 'export'])->name('sales-report.export');
        Route::get('/log-midtrans', [Admin\MidtransLogController::class, 'index'])->name('midtrans-logs.index');
        Route::get('/audit-log', [Admin\AuditLogController::class, 'index'])->name('audit-logs.index');

        Route::get('/sale-units', [Admin\SaleUnitController::class, 'index'])->name('sale-units.index');
        Route::post('/sale-units', [Admin\SaleUnitController::class, 'store'])->name('sale-units.store');
        Route::get('/sale-units/{saleUnit}/edit', [Admin\SaleUnitController::class, 'edit'])->name('sale-units.edit');
        Route::put('/sale-units/{saleUnit}', [Admin\SaleUnitController::class, 'update'])->name('sale-units.update');
        Route::patch('/sale-units/{saleUnit}/status', [Admin\SaleUnitController::class, 'status'])->name('sale-units.status');
        Route::delete('/sale-units/{saleUnit}', [Admin\SaleUnitController::class, 'destroy'])->name('sale-units.destroy');
        Route::post('/sale-units/{saleUnit}/images/{image}/cover', [Admin\SaleUnitController::class, 'setCover'])->name('sale-units.cover');
        Route::delete('/sale-units/{saleUnit}/images/{image}', [Admin\SaleUnitController::class, 'destroyImage'])->name('sale-units.images.destroy');

        Route::get('/sale-requests', [Admin\SaleRequestController::class, 'index'])->name('sale-requests.index');
        Route::get('/sale-requests/{saleRequest}', [Admin\SaleRequestController::class, 'show'])->name('sale-requests.show');
        Route::post('/sale-requests/{saleRequest}/notes', [Admin\SaleRequestController::class, 'addNote'])->name('sale-requests.notes');
        Route::post('/sale-requests/{saleRequest}/offer', [Admin\SaleRequestController::class, 'addOffer'])->name('sale-requests.offer');
        Route::post('/sale-requests/{saleRequest}/photos', [Admin\SaleRequestController::class, 'addPhotos'])->name('sale-requests.photos');
        Route::delete('/sale-requests/{saleRequest}/photos/{photo}', [Admin\SaleRequestController::class, 'destroyPhoto'])->name('sale-requests.photos.destroy');
        Route::post('/sale-requests/{saleRequest}/survey', [Admin\SaleRequestController::class, 'scheduleSurvey'])->name('sale-requests.survey');
        Route::post('/sale-requests/{saleRequest}/publish', [Admin\SaleRequestController::class, 'publish'])->name('sale-requests.publish');
        Route::post('/sale-requests/{saleRequest}/close', [Admin\SaleRequestController::class, 'close'])->name('sale-requests.close');
        Route::patch('/sale-requests/{saleRequest}/status', [Admin\SaleRequestController::class, 'updateStatus'])->name('sale-requests.status');
        Route::delete('/sale-requests/{saleRequest}', [Admin\SaleRequestController::class, 'destroy'])->name('sale-requests.destroy');

        Route::get('/sale-commissions', [Admin\SaleCommissionController::class, 'index'])->name('sale-commissions.index');
        Route::post('/sale-commissions', [Admin\SaleCommissionController::class, 'store'])->name('sale-commissions.store');
        Route::get('/sale-commissions/{saleCommission}/edit', [Admin\SaleCommissionController::class, 'edit'])->name('sale-commissions.edit');
        Route::put('/sale-commissions/{saleCommission}', [Admin\SaleCommissionController::class, 'update'])->name('sale-commissions.update');
        Route::delete('/sale-commissions/{saleCommission}', [Admin\SaleCommissionController::class, 'destroy'])->name('sale-commissions.destroy');
        Route::get('/sale-commissions/{saleCommission}/invoice', [Admin\SaleCommissionController::class, 'invoice'])->name('sale-commissions.invoice');
    });
});
