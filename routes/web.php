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
});
