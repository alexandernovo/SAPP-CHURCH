@extends('layouts.adminDashboard')

@section('title', 'Burial — Certification — ' . config('app.name', 'SAPP Church'))

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/christening/applicationOfChristening.css') }}">
    <link rel="stylesheet" href="{{ asset('css/burial/burialApplication.css') }}">
    <style>
        .sappc-doc-picker_btn:disabled {
            opacity: 1;
        }
    </style>
@endpush

@section('content')
    <div class="sappc-registry-page">
        <div class="sappc-registry-page_head">
            <div class="sappc-registry-page_head-main">
                <h1 class="sappc-page-title">
                    <i class="fa-solid fa-certificate" aria-hidden="true"></i>
                    BURIAL
                </h1>
                <p class="sappc-page-breadcrumb mb-0">
                    <a href="{{ route('admin.dashboard') }}">Dashboard</a>
                    <span class="sappc-page-breadcrumb_sep" aria-hidden="true">|</span>
                    <a href="{{ route('admin.burial.application') }}">Burial</a>
                    <span class="sappc-page-breadcrumb_sep" aria-hidden="true">|</span>
                    <span>Certification</span>
                </p>
            </div>
            <div class="sappc-registry-page_actions">
                <button type="button"
                    class="sappc-registry-toolbar_btn sappc-registry-toolbar_btn--cta"
                    id="burialCertificationBtn"
                    title="Open burial certification form"
                    aria-label="Open burial certification form"
                    aria-expanded="false"
                    aria-controls="burialCertificationModal">
                    <i class="fa-solid fa-plus" aria-hidden="true"></i>
                    New Certification
                </button>
            </div>
        </div>

        @include('partials.registry.navToolbar', [
            'registry' => 'burial',
            'activeSection' => 'certification',
            'showCertification' => true,
        ])

        @include('burial.partials.certificationModal')
        @include('burial.partials.burialCertificationCertificate')
        @include('partials.sappcCertificatePreviewModal')

        @include('burial.partials.recordsTablePanel', [
            'activeSection' => 'certification',
            'sectionLabel' => 'certification records',
            'workflowHasCertification' => true,
            'sortOrder' => 'desc',
            'tableColumns' => [
                'NO.', 'REFERENCE CODE', 'CLIENT', 'ADDRESS', 'CONTACT NUMBER', 'DATE CREATED', 'ACTION',
            ],
        ])
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            var savedPrintTitle = '';

            window.addEventListener('beforeprint', function () {
                savedPrintTitle = document.title;
                document.title = ' ';
            });

            window.addEventListener('afterprint', function () {
                if (savedPrintTitle !== '') {
                    document.title = savedPrintTitle;
                    savedPrintTitle = '';
                }
            });
        })();
    </script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @include('burial.js.burialScript', [
        'initialTablePayload' => $initialTablePayload,
        'activeSection' => 'certification',
    ])
@endpush
