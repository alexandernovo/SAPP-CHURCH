@extends('layouts.app')

@section('title', 'Admin Login — ' . config('app.name', 'SAPP Church'))

@section('body-class', 'admin-login-page bg-light')

@push('styles')
    <style>
        body.admin-login-page .admin-login-bg {
            background-image: url("{{ asset('assets/landingPage/BACKGROUND.jpg') }}");
        }
    </style>
    <link rel="stylesheet" href="{{ asset('css/auth/adminLogin.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app-typography.css') }}?v={{ filemtime(public_path('css/app-typography.css')) }}">
@endpush

@section('content')
    @include('layouts.landingPageHeader')

    <main class="admin-login-root">
        <div class="admin-login-bg" aria-hidden="true"></div>

        <div class="admin-login-shell">
            <div class="admin-login-card">
                <a href="{{ route('landingPage') }}" class="admin-login-card__close" aria-label="Close">&times;</a>

                <div class="admin-login-card__brand">
                    <img class="admin-login-card__logo" src="{{ asset('assets/landingPage/SAPPC-transparent.png') }}" alt=""
                        width="888" height="900" decoding="async">
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
        (function () {
            var form = document.querySelector('.admin-login-form');
            var userInput = document.getElementById('admin-username');
            var passInput = document.getElementById('admin-password');
            if (!form || !userInput || !passInput) return;

            function trimmed(el) {
                return (el.value || '').trim();
            }

            function bothComplete() {
                return trimmed(userInput) !== '' && trimmed(passInput) !== '';
            }

            form.addEventListener('keydown', function (e) {
                if (e.key !== 'Enter') return;
                if (e.target === userInput) {
                    e.preventDefault();
                    if (!trimmed(userInput)) {
                        userInput.setCustomValidity('Please enter your username.');
                        userInput.reportValidity();
                        userInput.setCustomValidity('');
                        return;
                    }
                    passInput.focus();
                    return;
                }
                if (e.target === passInput) {
                    if (!trimmed(userInput)) {
                        e.preventDefault();
                        userInput.setCustomValidity('Please enter your username.');
                        userInput.reportValidity();
                        userInput.setCustomValidity('');
                        userInput.focus();
                        return;
                    }
                    if (!trimmed(passInput)) {
                        e.preventDefault();
                        passInput.setCustomValidity('Please enter your password.');
                        passInput.reportValidity();
                        passInput.setCustomValidity('');
                        return;
                    }
                    /* Both fields filled — allow default submit */
                }
            });

            form.addEventListener('submit', function (e) {
                if (!bothComplete()) {
                    e.preventDefault();
                    if (!trimmed(userInput)) {
                        userInput.setCustomValidity('Please enter your username.');
                        userInput.reportValidity();
                        userInput.setCustomValidity('');
                        userInput.focus();
                    } else {
                        passInput.setCustomValidity('Please enter your password.');
                        passInput.reportValidity();
                        passInput.setCustomValidity('');
                        passInput.focus();
                    }
                }
            });
        })();

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
