<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\ChristeningController;
use App\Http\Controllers\BurialController;
use App\Http\Controllers\ConfirmationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\WeddingController;
use App\Http\Controllers\DocumentationController;
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
    Route::get('/confirmation', [ConfirmationController::class, 'index'])->name('admin.confirmation');
    Route::post('/confirmation/schedule-request', [ConfirmationController::class, 'scheduleConfirmation'])->name('admin.confirmation.schedule-request');
    Route::get('/confirmation/schedule-reserved-dates', [ConfirmationController::class, 'confirmationReservedDates'])->name('admin.confirmation.schedule-reserved-dates');
    Route::get('/confirmation/payment-details', [ConfirmationController::class, 'confirmationPaymentDetails'])->name('admin.confirmation.payment-details');
    Route::post('/confirmation/payment-save', [ConfirmationController::class, 'confirmationPaymentSave'])->name('admin.confirmation.payment-save');
    Route::get('/confirmation/confirmation-application', [ConfirmationController::class, 'confirmationApplicationDetails'])->name('admin.confirmation.application-details');
    Route::post('/confirmation/confirmation-application', [ConfirmationController::class, 'confirmationApplicationSave'])->name('admin.confirmation.application-save');
    Route::get('/confirmation/confirmation-arancel', [ConfirmationController::class, 'confirmationArancelDetails'])->name('admin.confirmation.arancel-details');
    Route::post('/confirmation/confirmation-arancel', [ConfirmationController::class, 'confirmationArancelSave'])->name('admin.confirmation.arancel-save');
    Route::get('/wedding', [WeddingController::class, 'index'])->name('admin.wedding');
    Route::post('/wedding/schedule-request', [WeddingController::class, 'scheduleWedding'])->name('admin.wedding.schedule-request');
    Route::get('/wedding/schedule-reserved-dates', [WeddingController::class, 'weddingReservedDates'])->name('admin.wedding.schedule-reserved-dates');
    Route::get('/wedding/payment-details', [WeddingController::class, 'weddingPaymentDetails'])->name('admin.wedding.payment-details');
    Route::post('/wedding/payment-save', [WeddingController::class, 'weddingPaymentSave'])->name('admin.wedding.payment-save');
    Route::get('/wedding/marriage-application', [WeddingController::class, 'weddingMarriageApplicationDetails'])->name('admin.wedding.marriage-application-details');
    Route::post('/wedding/marriage-application', [WeddingController::class, 'weddingMarriageApplicationSave'])->name('admin.wedding.marriage-application-save');
    Route::get('/burial', [BurialController::class, 'index'])->name('admin.burial');
    Route::post('/burial/schedule-request', [BurialController::class, 'scheduleBurial'])->name('admin.burial.schedule-request');
    Route::get('/burial/schedule-reserved-dates', [BurialController::class, 'burialReservedDates'])->name('admin.burial.schedule-reserved-dates');
    Route::get('/burial/payment-details', [BurialController::class, 'burialPaymentDetails'])->name('admin.burial.payment-details');
    Route::post('/burial/payment-save', [BurialController::class, 'burialPaymentSave'])->name('admin.burial.payment-save');
    Route::get('/document', [DocumentationController::class, 'document'])->name('admin.document');
    Route::get('/document/burial-report', [DocumentationController::class, 'burialReport'])->name('admin.document.burial-report');
    Route::get('/certification', [ChristeningController::class, 'certificationPage'])->name('admin.certification');
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
