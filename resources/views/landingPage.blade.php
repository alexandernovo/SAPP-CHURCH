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
                        <p class="sappc-welcome">Welcome to</p>
                        <h1 class="sappc-main-title">Saint Anthony of Padua Parish Church</h1>
                        <div class="sappc-main-title-accent" aria-hidden="true"></div>
                        <p class="sappc-main-subtitle">Automated Recording Management System</p>
                        <p class="sappc-main-quote">
                            &ldquo;Digitizing Church Records, Strengthening Faith Communities&rdquo;
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
