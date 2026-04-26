@extends('layouts.adminDashboard')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/document/sappcDocumentLayout.css') }}">
@endpush

@section('content')
    <div class="sappc-doc-page">
        <div class="sappc-doc-page_head">
            <h1 class="sappc-doc-page_title">
                <i class="fa-solid fa-file-lines" aria-hidden="true"></i>
                REPORT
            </h1>
            <p class="sappc-doc-page_breadcrumb mb-0">
                <a href="{{ route('admin.dashboard') }}">Dashboard</a>
                <span class="sappc-doc-page_sep" aria-hidden="true">|</span>
                <a href="{{ route('admin.document') }}">Document</a>
            </p>
        </div>

        <div class="sappc-doc-toolbar" aria-label="Report actions">
            <a href="{{ route('admin.dashboard') }}" class="sappc-doc-toolbar_close" title="Close" aria-label="Close">&times;</a>

            <div class="sappc-doc-toolbar_left">
                <p class="sappc-doc-toolbar_label">Select Month and Year</p>
                <div class="sappc-doc-toolbar_month-wrap">
                    <input
                        type="month"
                        class="sappc-doc-toolbar_month"
                        id="sappcDocReportMonth"
                        name="report_month"
                        value="{{ $docReportMonth ?? request('month', now()->format('Y-m')) }}"
                        aria-label="Select month and year for report"
                    >
                    <span class="sappc-doc-toolbar_month-icon" aria-hidden="true">
                        <i class="fa-regular fa-calendar"></i>
                    </span>
                </div>
            </div>

            <div class="sappc-doc-toolbar_right">
                <button type="button" class="sappc-doc-toolbar_print" id="sappcDocPrintBtn">
                    <i class="fa-solid fa-print" aria-hidden="true"></i>
                    Print Report
                </button>
                <div class="sappc-doc-toolbar_downloads" role="group" aria-label="Download options">
                    <span class="sappc-doc-toolbar_downloads-label">Download</span>
                    <button type="button" class="sappc-doc-toolbar_icon-btn sappc-doc-toolbar_icon-btn--pdf" title="Download PDF" aria-label="Download PDF" disabled>
                        <i class="fa-solid fa-file-pdf" aria-hidden="true"></i>
                    </button>
                    <button type="button" class="sappc-doc-toolbar_icon-btn sappc-doc-toolbar_icon-btn--word" title="Download Word" aria-label="Download Word" disabled>
                        <i class="fa-solid fa-file-word" aria-hidden="true"></i>
                    </button>
                    <button type="button" class="sappc-doc-toolbar_icon-btn sappc-doc-toolbar_icon-btn--excel" title="Download Excel" aria-label="Download Excel" disabled>
                        <i class="fa-solid fa-file-excel" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="sappc-doc-sheet" id="sappcDocumentSheet">
            @yield('document')
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function ($) {
            $('#sappcDocPrintBtn').on('click', function () {
                window.print();
            });
        })(jQuery);
    </script>
    @stack('document_scripts')
@endpush
