@extends('layouts.adminDashboard')

@section('title', 'Burial — Schedule Request — ' . config('app.name', 'SAPP Church'))

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/christening/applicationOfChristening.css') }}">
    <link rel="stylesheet" href="{{ asset('css/burial/burialApplication.css') }}">
@endpush

@section('content')
    <div class="sappc-registry-page">
        <div class="sappc-registry-page_head">
            <div class="sappc-registry-page_head-main">
                <h1 class="sappc-page-title">
                    <i class="fa-solid fa-calendar-days" aria-hidden="true"></i>
                    BURIAL — SCHEDULE REQUEST
                </h1>
                <p class="sappc-page-breadcrumb mb-0">
                    <a href="{{ route('admin.dashboard') }}">Dashboard</a>
                    <span class="sappc-page-breadcrumb_sep" aria-hidden="true">|</span>
                    <a href="{{ route('admin.burial.application') }}">Burial</a>
                    <span class="sappc-page-breadcrumb_sep" aria-hidden="true">|</span>
                    <span>Schedule Request</span>
                </p>
            </div>
            <div class="sappc-registry-page_actions">
                <button type="button"
                    class="sappc-registry-toolbar_btn sappc-registry-toolbar_btn--cta"
                    id="burialNewRecordBtn"
                    data-bs-toggle="modal"
                    data-bs-target="#burialScheduleRequestModal"
                    title="New schedule request"
                    aria-label="Create new schedule request">
                    <i class="fa-solid fa-plus" aria-hidden="true"></i>
                    New Schedule Request
                </button>
            </div>
        </div>

        @include('partials.registry.navToolbar', [
            'registry' => 'burial',
            'activeSection' => 'schedule',
            'showCertification' => true,
        ])

        @include('burial.partials.scheduleRequestModal', ['generatedReferenceCode' => $generatedReferenceCode])

        @include('burial.partials.recordsTablePanel', [
            'activeSection' => 'schedule',
            'sectionLabel' => 'schedule records',
            'tableColumns' => [
                'NO.', 'REFERENCE CODE', 'CLIENT', 'ADDRESS', 'SEX', 'CONTACT NUMBER', 'PAYMENT STATUS', 'DATE CREATED', 'ACTION',
            ],
        ])
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @include('burial.js.burialScript', [
        'initialTablePayload' => $initialTablePayload,
        'activeSection' => 'schedule',
    ])
@endpush
