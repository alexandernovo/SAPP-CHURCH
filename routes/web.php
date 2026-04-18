<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\ChristeningController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'landingPage')->name('landingPage');

Route::view('/developers', 'developers')->name('developers');

Route::view('/admin', 'auth.adminLogin')->name('admin.login');
Route::post('/admin/login', [AuthController::class, 'login'])->name('admin.login.submit');

Route::middleware('auth')->group(function () {
    Route::get('/sappcDashboard', [DashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/admin/dashboard/records', [DashboardController::class, 'records'])->name('admin.dashboard.records');
    Route::get('/admin/dashboard/search', [DashboardController::class, 'searchSAPPCData'])->name('admin.dashboard.search');
    Route::get('/admin/dashboard/stats/monthly', [DashboardController::class, 'monthlyStats'])->name('admin.dashboard.stats.monthly');
    Route::post('/admin/logout', [AuthController::class, 'logout'])->name('admin.logout');
    Route::get('/christening', [ChristeningController::class, 'index'])->name('admin.christening');
    Route::view('/confirmation', 'confirmation.view.confirmation')->name('admin.confirmation');
    Route::view('/wedding', 'wedding.view.wedding')->name('admin.wedding');
    Route::view('/burial', 'burial.views.burial')->name('admin.burial');
    Route::view('/document', 'document.view.document')->name('admin.document');
    Route::view('/certification', 'certification.view.certification')->name('admin.certification');
    Route::post('/christening/schedule-request', [ChristeningController::class, 'scheduleChristening'])->name('admin.christening.schedule-request');
    Route::post('/christening/application-form', [ChristeningController::class, 'christeningApplicationForm'])->name('admin.christening.application-form');
    Route::get('/christening/application-details', [ChristeningController::class, 'christeningApplicationDetails'])->name('admin.christening.application-details');
    Route::get('/christening/payment-details', [ChristeningController::class, 'christeningPaymentDetails'])->name('admin.christening.payment-details');
    Route::post('/christening/payment-save', [ChristeningController::class, 'christeningPaymentSave'])->name('admin.christening.payment-save');
    Route::post('/christening/certification-form', [ChristeningController::class, 'christeningCertificationForm'])->name('admin.christening.certification-form');
    Route::get('/christening/certification-details', [ChristeningController::class, 'christeningCertificationDetails'])->name('admin.christening.certification-details');
    Route::post('/christening/record/delete', [ChristeningController::class, 'deleteChristeningRecord'])->name('admin.christening.record-delete');
    Route::get('/christening/schedule-reserved-dates', [ChristeningController::class, 'christeningReservedDates'])->name('admin.christening.schedule-reserved-dates');
    Route::post('/admin/dashboard/records/delete', [DashboardController::class, 'deleteRecord' ])->name('admin.dashboard.records.delete');
});
