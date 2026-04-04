@extends('layouts.app')

@section('title', 'Admin Login — ' . config('app.name', 'SAPP Church'))

@section('body-class', 'admin-login-page bg-light')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/adminLogin.css') }}">
@endpush

@section('content')
    @include('layouts.landingPageHeader')

    <main class="admin-login-root">
        <div class="admin-login-bg" aria-hidden="true"></div>

        <div class="admin-login-shell">
            <div class="admin-login-card">
                <a href="{{ route('landingPage') }}" class="admin-login-card__close" aria-label="Close">&times;</a>

                <div class="admin-login-card__brand">
                    <img class="admin-login-card__logo" src="{{ asset('assets/landingPage/SAPPC.png') }}" alt=""
                        width="104" height="104" decoding="async">
                    <h1 class="admin-login-card__title">Saint Anthony of Padua Parish Church</h1>
                </div>

                <hr class="admin-login-card__divider">

                <div class="admin-login-card__intro">
                    <h2>Log in to your Account</h2>
                    <p>Enter your username and password to log in your account</p>
                </div>

                <form class="admin-login-form" method="POST" action="#" autocomplete="on">
                    @csrf
                    <div class="admin-login-field">
                        <label for="admin-username">Username:</label>
                        <div class="admin-login-input-wrap">
                            <span class="admin-login-input-wrap__icon" aria-hidden="true">
                                <i class="fa-solid fa-user"></i>
                            </span>
                            <input id="admin-username" class="admin-login-input" type="text" name="username"
                                value="{{ old('username') }}" autocomplete="username" required>
                        </div>
                    </div>

                    <div class="admin-login-field">
                        <label for="admin-password">Password:</label>
                        <div class="admin-login-input-wrap admin-login-input-wrap--password">
                            <span class="admin-login-input-wrap__icon" aria-hidden="true">
                                <i class="fa-solid fa-lock"></i>
                            </span>
                            <input id="admin-password" class="admin-login-input" type="password" name="password"
                                autocomplete="current-password" required>
                            <button type="button" class="admin-login-toggle-pw" aria-label="Show password">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="admin-login-submit">Login</button>
                </form>
            </div>
        </div>
    </main>

    @include('layouts.landingPageFooter')
@endsection

@push('scripts')
    @include('auth.adminLoginjs')
@endpush
