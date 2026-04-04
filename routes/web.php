<?php

use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'landingPage')->name('landingPage');

Route::view('/developers', 'developers')->name('developers');

Route::view('/admin', 'auth.adminLogin')->name('admin.login');
Route::post('/admin/login', [AuthController::class, 'login'])->name('admin.login.submit');

Route::middleware('auth')->group(function () {
    Route::view('/sappcDashboard', 'sappcDashboard')->name('admin.dashboard');
    Route::post('/admin/logout', [AuthController::class, 'logout'])->name('admin.logout');
    Route::view('/christening', 'christening')->name('admin.christening');
    Route::view('/confirmation', 'confirmation')->name('admin.confirmation');
    Route::view('/wedding', 'wedding')->name('admin.wedding');
    Route::view('/burial', 'burial')->name('admin.burial');
    Route::view('/document', 'document')->name('admin.document');
    Route::view('/certification', 'certification')->name('admin.certification');
});
