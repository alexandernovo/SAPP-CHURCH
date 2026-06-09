@extends('layouts.adminDashboard')

@section('title', 'Christening — Schedule Request — ' . config('app.name', 'SAPP Church'))

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/christening/applicationOfChristening.css') }}">
@endpush

@section('content')
    <div class="sappc-registry-page">
        <div class="sappc-registry-page_head">
            <div class="sappc-registry-page_head-main">
                <h1 class="sappc-page-title">
                    <i class="fa-solid fa-calendar-days" aria-hidden="true"></i>
                    CHRISTENING
                </h1>
                <p class="sappc-page-breadcrumb mb-0">
                    <a href="{{ route('admin.dashboard') }}">Dashboard</a>
                    <span class="sappc-page-breadcrumb_sep" aria-hidden="true">|</span>
                    <a href="{{ route('admin.christening.application') }}">Christening</a>
                    <span class="sappc-page-breadcrumb_sep" aria-hidden="true">|</span>
                    <span>Schedule Request</span>
                </p>
            </div>
            <div class="sappc-registry-page_actions">
                <button type="button"
                    class="sappc-registry-toolbar_btn sappc-registry-toolbar_btn--cta"
                    id="christeningNewRecordBtn"
                    data-bs-toggle="modal"
                    data-bs-target="#christeningScheduleRequestModal"
                    title="New schedule request"
                    aria-label="Create new schedule request">
                    <i class="fa-solid fa-plus" aria-hidden="true"></i>
                    New Schedule Request
                </button>
            </div>
        </div>

        @include('partials.registry.navToolbar', [
            'registry' => 'christening',
            'activeSection' => 'schedule',
            'showCertification' => true,
        ])

        @include('christening.partials.scheduleRequestModal', ['generatedReferenceCode' => $generatedReferenceCode])

        @include('christening.partials.recordsTablePanel', [
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
    @include('christening.js.christeningScript', [
        'initialTablePayload' => $initialTablePayload,
        'activeSection' => 'schedule',
    ])
@endpush
