@extends('layouts.app')

@section('title', 'Admin Login — ' . config('app.name', 'SAPP Church'))

@section('body-class', 'admin-login-page bg-light')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/auth/adminLogin.css') }}">
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
                    <p>Enter your username and password to log in to your account</p>
                </div>

                @error('login')
                    <p class="admin-login-card__error" role="alert">{{ $message }}</p>
                @enderror

                <form class="admin-login-form" method="POST" action="{{ route('admin.login.submit') }}" autocomplete="on">
                    @csrf
                    <div class="admin-login-field">
                        <label for="admin-username">Username</label>
                        <div class="admin-login-input-wrap">
                            <span class="admin-login-input-wrap__icon" aria-hidden="true">
                                <i class="fa-solid fa-user"></i>
                            </span>
                            <input id="admin-username" class="admin-login-input @error('userName') admin-login-input--error @enderror"
                                type="text" name="userName" value="{{ old('userName') }}" autocomplete="username" required>
                        </div>
                        @error('userName')
                            <span class="admin-login-field__error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="admin-login-field">
                        <label for="admin-password">Password</label>
                        <div class="admin-login-input-wrap admin-login-input-wrap--password">
                            <span class="admin-login-input-wrap__icon" aria-hidden="true">
                                <i class="fa-solid fa-lock"></i>
                            </span>
                            <input id="admin-password" class="admin-login-input @error('userPass') admin-login-input--error @enderror"
                                type="password" name="userPass" autocomplete="current-password" required>
                            <button type="button" class="admin-login-toggle-pw" aria-label="Show password">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                        @error('userPass')
                            <span class="admin-login-field__error">{{ $message }}</span>
                        @enderror
                    </div>

                    <button type="submit" class="admin-login-submit">Login</button>
                </form>
            </div>
        </div>
    </main>

    @include('layouts.landingPageFooter')
@endsection

@push('scripts')
    <script>
        document.querySelector('.admin-login-toggle-pw')?.addEventListener('click', function () {
            const input = document.getElementById('admin-password');
            const icon = this.querySelector('i');
            if (!input || !icon) return;
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            this.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            icon.classList.toggle('fa-eye', !show);
            icon.classList.toggle('fa-eye-slash', show);
        });
    </script>
@endpush
