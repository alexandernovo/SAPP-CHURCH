@extends('layouts.adminDashboard')

@section('title', 'Confirmation — Application Form — ' . config('app.name', 'SAPP Church'))

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/christening/applicationOfChristening.css') }}">
    <link rel="stylesheet" href="{{ asset('css/confirmation/confirmationKompirmaModals.css') }}">
@endpush

@section('content')
    <div class="sappc-registry-page">
        <div class="sappc-registry-page_head">
            <div class="sappc-registry-page_head-main">
                <h1 class="sappc-page-title">
                    <i class="fa-solid fa-file-lines" aria-hidden="true"></i>
                    CONFIRMATION
                </h1>
                <p class="sappc-page-breadcrumb mb-0">
                    <a href="{{ route('admin.dashboard') }}">Dashboard</a>
                    <span class="sappc-page-breadcrumb_sep" aria-hidden="true">|</span>
                    <a href="{{ route('admin.confirmation.application') }}">Confirmation</a>
                    <span class="sappc-page-breadcrumb_sep" aria-hidden="true">|</span>
                    <span>Application Form</span>
                </p>
            </div>
            <div class="sappc-registry-page_actions">
                <button type="button"
                    class="sappc-registry-toolbar_btn sappc-registry-toolbar_btn--cta"
                    id="confirmationApplicationFormBtn"
                    aria-expanded="false"
                    aria-controls="confirmationApplicationModal"
                    title="Open confirmation application form"
                    aria-label="Open confirmation application form">
                    <i class="fa-solid fa-plus" aria-hidden="true"></i>
                    New Application Form
                </button>
            </div>
        </div>

        @include('partials.registry.navToolbar', [
            'registry' => 'confirmation',
            'activeSection' => 'application',
            'showCertification' => true,
        ])

        @include('confirmation.partials.applicationModal')

        @include('confirmation.partials.recordsTablePanel', [
            'activeSection' => 'application',
            'sectionLabel' => 'application records',
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
        'activeSection' => 'application',
    ])
@endpush
