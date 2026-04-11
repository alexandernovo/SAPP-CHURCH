@extends('layouts.adminDashboard')

@section('title', 'Christening — ' . config('app.name', 'SAPP Church'))

@section('content')
    <h1 class="sappc-page-title">
        <i class="fa-solid fa-file-lines" aria-hidden="true"></i>
        CHRISTENING
    </h1>
    <p class="sappc-page-breadcrumb mb-0">
        <a href="{{ route('admin.dashboard') }}">Dashboard</a>
        <span class="sappc-page-breadcrumb_sep" aria-hidden="true">|</span>
        <span>Christening</span>
    </p>

    <div class="sappc-registry-toolbar" role="toolbar" aria-label="Christening record actions">
        <span class="sappc-registry-toolbar_record">RECORD</span>
        <div class="sappc-registry-toolbar_actions">
            <button type="button" class="sappc-registry-toolbar_btn sappc-registry-toolbar_btn--reload"
                id="christeningReloadBtn" title="Reload" aria-label="Reload table">
                <i class="fa-solid fa-rotate-right" aria-hidden="true"></i>
                Reload
            </button>
            <button type="button" class="sappc-registry-toolbar_btn sappc-registry-toolbar_btn--cta"
                id="christeningScheduleRequestBtn" data-schedule-url="{{ route('admin.christening.schedule-request') }}"
                title="Schedule request" aria-label="Open schedule request">
                <i class="fa-solid fa-calendar-days" aria-hidden="true"></i>
                Schedule Request
            </button>
            <button type="button" class="sappc-registry-toolbar_btn sappc-registry-toolbar_btn--outline">
                <i class="fa-solid fa-certificate" aria-hidden="true"></i>
                Certification
            </button>
            <button type="button" class="sappc-registry-toolbar_btn sappc-registry-toolbar_btn--outline">
                <i class="fa-solid fa-money-bill-wave" aria-hidden="true"></i>
                Payment Fee
            </button>
            <button type="button" class="sappc-registry-toolbar_btn sappc-registry-toolbar_btn--outline">
                <i class="fa-solid fa-file-lines" aria-hidden="true"></i>
                Application Form
            </button>
        </div>
    </div>

    <section class="sappc-table-panel" id="christeningRecordsPanel"
        data-records-url="{{ route('admin.dashboard.records') }}" data-registry-type="christening"
        data-per-page-options="{{ json_encode($perPageOptions) }}" aria-label="Christening records">
        <div class="sappc-table-toolbar">
            <div class="sappc-table-toolbar_row sappc-table-toolbar_row--primary">
                <div class="sappc-table-toolbar_entries">
                    <label class="visually-hidden" for="christeningEntries">Entries per page</label>
                    <select id="christeningEntries" class="form-select form-select-sm sappc-table-toolbar_select"
                        aria-label="Entries per page">
                        @foreach ($perPageOptions as $n)
                            <option value="{{ $n }}" @selected($records->perPage() === $n)>{{ $n }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="sappc-toolbar-date-strip" role="group" aria-label="Filter by date range">
                    <span class="sappc-toolbar-date-strip_label">From:</span>
                    <input type="date" id="christeningDateFrom" class="sappc-toolbar-date-strip_input" name="date_from"
                        value="{{ request('date_from') }}" aria-label="From date">
                    <span class="sappc-toolbar-date-strip_label">To:</span>
                    <input type="date" id="christeningDateTo" class="sappc-toolbar-date-strip_input" name="date_to"
                        value="{{ request('date_to') }}" aria-label="To date">
                    <button type="button" class="sappc-toolbar-date-strip_btn">Filter</button>
                </div>
                <div class="sappc-table-toolbar_letters" role="group"
                    aria-label="Filter by first letter of client last name">
                    <span class="visually-hidden">Filter by first letter of last name A through Z; scroll horizontally to
                        see all letters.</span>
                    <div class="sappc-letter-filter_letters">
                        @foreach ($letterOptions as $letter)
                            <button type="button"
                                class="sappc-letter-filter_btn {{ request('letter') === $letter ? 'is-active' : '' }}"
                                data-letter="{{ $letter }}">{{ $letter }}</button>
                        @endforeach
                    </div>
                </div>
                <div class="sappc-table-toolbar_search" role="search">
                    <label class="sappc-table-toolbar_search-heading" for="christeningSearch">Search:</label>
                    <div class="sappc-table-toolbar_search-wrap">
                        <input type="search" id="christeningSearch"
                            class="form-control form-control-sm sappc-table-toolbar_search-input"
                            value="{{ request('search') }}" placeholder="" autocomplete="off"
                            aria-label="Search christening records" aria-controls="christeningTableBody">
                        <i class="fa-solid fa-magnifying-glass sappc-table-toolbar_search-icon" aria-hidden="true"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered mb-0 sappc-data-table">
                <thead>
                    <tr>
                        <th scope="col">NO.</th>
                        <th scope="col">REFERENCE CODE</th>
                        <th scope="col">CLIENT</th>
                        <th scope="col">ADDRESS</th>
                        <th scope="col">SEX</th>
                        <th scope="col">CONTACT NUMBER</th>
                        <th scope="col">PAYMENT STATUS</th>
                        <th scope="col">DATE CREATED</th>
                        <th scope="col" class="text-center">ACTION</th>
                    </tr>
                </thead>
                <tbody id="christeningTableBody" aria-live="polite" aria-relevant="additions text"></tbody>
            </table>
        </div>

        <div class="sappc-table-footer">
            <p class="sappc-table-footer_info mb-0" id="christeningTableFooterInfo"></p>
            <nav class="sappc-pagination" id="christeningPagination" aria-label="Table pagination"></nav>
        </div>
    </section>
@endsection

@push('scripts')
    @include('christening.js.christeningScript', ['initialTablePayload' => $initialTablePayload])
@endpush
