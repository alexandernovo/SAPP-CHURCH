<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'landingPage')->name('landingPage');

Route::view('/developers', 'developers')->name('developers');

Route::view('/admin', 'auth.adminLogin')->name('admin.login');
