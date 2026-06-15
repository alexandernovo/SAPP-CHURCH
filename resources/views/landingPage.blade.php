@extends('layouts.app')

@push('styles')
    <style>
        body.landing-page {
            background-image: url("{{ asset('assets/landingPage/BACKGROUND.jpg') }}");
        }

        .landing-ribbon {
            background-image: url("{{ asset('assets/landingPage/RIBBON.png') }}");
        }
    </style>
@endpush

@section('title', config('app.name', 'SAPP Church'))

@section('body-class', 'landing-page')

@section('content')
    @include('layouts.landingPageHeader')

    <div class="landing-ribbon" aria-hidden="true"></div>

    <main class="landing-main">
        <section class="sappc-main" aria-label="Welcome">
            <div class="container">
                <div class="row align-items-center g-4 g-lg-5">
                    <div class="col-lg-7 sappc-main__copy">
                        <h1 class="sappc-main-title">
                            <span class="sappc-main-title__line">Saint Anthony of Padua</span>
                            <span class="sappc-main-title__line">Parish Church</span>
                        </h1>
                        <p class="sappc-main-subtitle">Automated Recording Management System</p>
                        <p class="sappc-main-tagline">
                            Digitizing Church Records, Strengthening Faith Communities
                        </p>
                    </div>
                    <div class="col-lg-5 text-center text-lg-end">
                        <img
                            class="sappc-main-logo img-fluid"
                            src="{{ asset('assets/landingPage/SAPPC.png') }}"
                            alt="Parish of St. Anthony of Padua, Barbaza seal"
                            width="600"
                            height="600"
                            decoding="async"
                        >
                    </div>
                </div>
            </div>
        </section>
    </main>

    @include('layouts.landingPageFooter')
@endsection

@push('scripts')
    <script>
        $(document).on('keydown', 'input[name="username"], #username, .username', function (e) {
            if (e.key !== 'Enter') {
                return;
            }

            e.preventDefault();

            const $form = $(this).closest('form');
            const $passwordField = $form.find('input[name="password"], #password, .password').first();

            if ($passwordField.length) {
                $passwordField.trigger('focus');
            }
        });
    </script>
@endpush
