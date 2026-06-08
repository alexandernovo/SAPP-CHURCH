@extends('layouts.adminDashboard')

@section('title', 'Confirmation — Payment Fee — ' . config('app.name', 'SAPP Church'))

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/christening/applicationOfChristening.css') }}">
    <link rel="stylesheet" href="{{ asset('css/confirmation/confirmationKompirmaModals.css') }}">
@endpush

@section('content')
    <div class="sappc-registry-page">
        <div class="sappc-registry-page_head">
            <div class="sappc-registry-page_head-main">
                <h1 class="sappc-page-title">
                    <i class="fa-solid fa-money-bill-wave" aria-hidden="true"></i>
                    CONFIRMATION — PAYMENT FEE
                </h1>
                <p class="sappc-page-breadcrumb mb-0">
                    <a href="{{ route('admin.dashboard') }}">Dashboard</a>
                    <span class="sappc-page-breadcrumb_sep" aria-hidden="true">|</span>
                    <a href="{{ route('admin.confirmation.application') }}">Confirmation</a>
                    <span class="sappc-page-breadcrumb_sep" aria-hidden="true">|</span>
                    <span>Payment Fee</span>
                </p>
            </div>
            <div class="sappc-registry-page_actions">
                <button type="button"
                    class="sappc-registry-toolbar_btn sappc-registry-toolbar_btn--cta"
                    id="confirmationPaymentFeeBtn"
                    title="Payment fee"
                    aria-label="Open payment fee"
                    aria-expanded="false"
                    aria-controls="confirmationPaymentFeeModal">
                    <i class="fa-solid fa-plus" aria-hidden="true"></i>
                    New Payment Record
                </button>
            </div>
        </div>

        @include('partials.registry.navToolbar', [
            'registry' => 'confirmation',
            'activeSection' => 'payment',
            'showCertification' => true,
        ])

        @include('confirmation.partials.paymentFeeModal')

        @include('confirmation.partials.recordsTablePanel', [
            'activeSection' => 'payment',
            'sectionLabel' => 'payment records',
            'tableColumns' => [
                'NO.', 'REFERENCE CODE', 'CLIENT', 'ADDRESS', 'SEX', 'CONTACT NUMBER', 'DATE CREATED', 'ACTION',
            ],
        ])
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @include('confirmation.js.confirmationScript', [
        'initialTablePayload' => $initialTablePayload,
        'activeSection' => 'payment',
    ])
@endpush
