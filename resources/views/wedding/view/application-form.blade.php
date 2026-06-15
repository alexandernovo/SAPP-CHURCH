@extends('layouts.adminDashboard')

@section('title', 'Wedding — Application Form — ' . config('app.name', 'SAPP Church'))

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/christening/applicationOfChristening.css') }}">
    <link rel="stylesheet" href="{{ asset('css/wedding/marriageApplicationKasal.css') }}">
@endpush

@section('content')
    <div class="sappc-registry-page">
        <div class="sappc-registry-page_head">
            <div class="sappc-registry-page_head-main">
                <h1 class="sappc-page-title">
                    <i class="fa-solid fa-file-lines" aria-hidden="true"></i>
                    WEDDING
                </h1>
                <p class="sappc-page-breadcrumb mb-0">
                    <a href="{{ route('admin.dashboard') }}">Dashboard</a>
                    <span class="sappc-page-breadcrumb_sep" aria-hidden="true">|</span>
                    <a href="{{ route('admin.wedding.application') }}">Wedding</a>
                    <span class="sappc-page-breadcrumb_sep" aria-hidden="true">|</span>
                    <span>Application Form</span>
                </p>
            </div>
            <div class="sappc-registry-page_actions">
                <button type="button"
                    class="sappc-registry-toolbar_btn sappc-registry-toolbar_btn--cta"
                    id="weddingApplicationFormBtn"
                    aria-expanded="false"
                    aria-controls="weddingMarriageApplicationModal"
                    title="Open marriage application form"
                    aria-label="Open marriage application form">
                    <i class="fa-solid fa-plus" aria-hidden="true"></i>
                    New Application Form
                </button>
            </div>
        </div>

        @include('partials.registry.navToolbar', [
            'registry' => 'wedding',
            'activeSection' => 'application',
            'showCertification' => true,
        ])

        @include('wedding.partials.marriageApplicationModal')

        @include('wedding.partials.recordsTablePanel', [
            'activeSection' => 'application',
            'sectionLabel' => 'application records',
            'tableColumns' => [
                'NO.', 'REFERENCE CODE', 'CLIENT', 'ADDRESS', 'CONTACT NUMBER', 'DATE CREATED', 'ACTION',
            ],
        ])
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @include('wedding.js.weddingScript', [
        'initialTablePayload' => $initialTablePayload,
        'activeSection' => 'application',
    ])
@endpush
