@extends('layouts.adminDashboard')

@section('dash_body_class')
    sappc-dash--locked-viewport
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/document/sappcDocumentLayout.css') }}?v={{ filemtime(public_path('css/document/sappcDocumentLayout.css')) }}">
    <style>
        #sappcDocViewReportBtn.sappc-doc-picker_btn:disabled,
        .sappc-doc-picker_btn:disabled {
            opacity: 1;
        }
    </style>
@endpush

@section('content')
    <div class="sappc-doc-page" id="sappcDocPageRoot">
        <header class="sappc-doc-page_head">
            <h1 class="sappc-doc-page_title">
                <i class="fa-solid fa-file-lines" aria-hidden="true"></i>
                REPORT
            </h1>
            <p class="sappc-doc-page_breadcrumb mb-0">
                <a href="{{ route('admin.dashboard') }}">Dashboard</a>
                <span class="sappc-doc-page_sep" aria-hidden="true">|</span>
                <a href="{{ route('admin.document') }}">Document</a>
            </p>
        </header>

        <div class="sappc-doc-picker-wrap" id="sappcDocPickerWrap">
            <div class="sappc-doc-picker-card sappc-doc-picker-card--compact">
                <a href="{{ route('admin.document') }}" class="sappc-doc-picker_close" title="Close" aria-label="Close">&times;</a>

                <div class="sappc-doc-picker_header">
                    <img
                        class="sappc-doc-picker_logo"
                        src="{{ asset('assets/logos/SAPPC.png') }}"
                        width="88"
                        height="88"
                        alt="Saint Anthony of Padua Parish Church"
                    >

                    <h2 class="sappc-doc-picker_title">SAINT ANTHONY OF PADUA PARISH CHURCH</h2>
                </div>

                <hr class="sappc-doc-picker_divider">

                <div class="sappc-doc-picker_body">
                    <div class="sappc-doc-picker_field">
                        <label for="sappcDocType" class="sappc-doc-picker_label">Document Report Type:</label>
                        <div class="sappc-doc-picker_select-wrap">
                            <select id="sappcDocType" class="sappc-doc-picker_select" aria-label="Document report type">
                                <option value="" selected disabled>Please Select</option>
                                <option value="christening">CHRISTENING</option>
                                <option value="confirmation">CONFIRMATION</option>
                                <option value="wedding">WEDDING</option>
                                <option value="burial">BURIAL</option>
                            </select>
                        </div>
                    </div>

                    <div class="sappc-doc-picker_field">
                        <label for="sappcDocReportMonth" class="sappc-doc-picker_label">Select Month:</label>
                        <div class="sappc-doc-picker_month-wrap">
                            <input
                                type="month"
                                id="sappcDocReportMonth"
                                class="sappc-doc-picker_month"
                                name="report_month"
                                value="{{ $docReportMonth ?? request('month', now()->format('Y-m')) }}"
                                aria-label="Report month and year"
                            >
                        </div>
                    </div>

                    <button type="button" class="sappc-doc-picker_btn" id="sappcDocViewReportBtn" disabled>View Report</button>
                </div>
            </div>
        </div>

        <div class="sappc-doc-sheet" id="sappcDocumentSheet" style="display:none;">
            <div class="sappc-doc-sheet__actions no-print sappc-doc-report-toolbar" id="sappcDocToolbar" role="toolbar" aria-label="Report export and print">
                <div class="sappc-doc-toolbar_month-field sappc-doc-report-toolbar_month">
                    <label for="sappcDocReportMonthToolbar" class="sappc-doc-toolbar_month-label">Select Month and Year:</label>
                    <div class="sappc-doc-picker_month-wrap sappc-doc-toolbar_month-wrap">
                        <input
                            type="month"
                            id="sappcDocReportMonthToolbar"
                            class="sappc-doc-picker_month sappc-doc-toolbar_month-input"
                            value="{{ $docReportMonth ?? request('month', now()->format('Y-m')) }}"
                            aria-label="Change report month and year"
                        >
                    </div>
                </div>
                <span class="sappc-doc-report-toolbar_spacer" aria-hidden="true"></span>
                <button type="button" class="sappc-doc-picker_btn sappc-doc-toolbar_print" id="sappcDocPrintBtn">
                    <i class="fa-solid fa-print" aria-hidden="true"></i>
                    Print Report
                </button>
                <div class="sappc-doc-toolbar_exports" aria-label="Download report">
                    <button type="button" class="sappc-doc-toolbar_icon" data-doc-export="pdf" title="Download PDF" aria-label="Download PDF">
                        <i class="fa-solid fa-file-pdf" aria-hidden="true"></i>
                    </button>
                    <button type="button" class="sappc-doc-toolbar_icon" data-doc-export="docx" title="Download Word" aria-label="Download Word">
                        <i class="fa-solid fa-file-word" aria-hidden="true"></i>
                    </button>
                    <button type="button" class="sappc-doc-toolbar_icon" data-doc-export="xlsx" title="Download Excel" aria-label="Download Excel">
                        <i class="fa-solid fa-file-excel" aria-hidden="true"></i>
                    </button>
                </div>
            </div>

            <div class="sappc-doc-sheet__content">
                <header class="sappc-doc-letterhead" aria-label="Parish header">
                    <img
                        class="sappc-doc-letterhead_crest"
                        src="{{ asset('assets/logos/DSA.jpg') }}"
                        width="100"
                        height="100"
                        alt=""
                    >
                    <div class="sappc-doc-letterhead_center">
                        <p class="sappc-doc-letterhead_line sappc-doc-letterhead_line--primary">THE ROMAN CATHOLIC PARISH OF ST. ANTHONY OF PADUA</p>
                        <p class="sappc-doc-letterhead_line sappc-doc-letterhead_line--sub">DIOCESE OF SAN JOSE DE ANTIQUE</p>
                        <p class="sappc-doc-letterhead_line sappc-doc-letterhead_line--sub">BARBAZA, 5706, ANTIQUE, PHILIPPINES</p>
                    </div>
                    <img
                        class="sappc-doc-letterhead_seal"
                        src="{{ asset('assets/logos/SAPPC.png') }}"
                        width="100"
                        height="100"
                        alt=""
                    >
                </header>

                <h2 class="sappc-doc-report-title" id="sappcDocReportTitle">
                    <span id="sappcDocReportService">DOCUMENT</span> REPORT OF
                    <span id="sappcDocReportLabel">{{ strtoupper($reportLabel ?? '') }}</span>
                </h2>

                <div class="table-responsive sappc-doc-table-wrap sappc-doc-table-panel_scroll" id="sappcDocTableOuter">
                    <table id="sappcDocDataTable" class="sappc-doc-table table table-bordered align-middle w-100 mb-0">
                        <thead>
                            <tr>
                                <th scope="col">NO.</th>
                                <th scope="col">REFERENCE CODE</th>
                                <th scope="col">CLIENT</th>
                                <th scope="col">ADDRESS</th>
                                <th scope="col">CONTACT NUMBER</th>
                                <th scope="col">DATE</th>
                            </tr>
                        </thead>
                        <tbody id="sappcDocTableBody">
                            <tr>
                                <td colspan="6" class="text-center py-3">Select a report type and click View Report.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <footer class="sappc-doc-signature">
                    <p class="sappc-doc-signature_name">REV. FR. RAMON A. NAVALLASCA</p>
                    <p class="sappc-doc-signature_role">Parish Priest</p>
                </footer>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @include('document.js.documentScipt', [
        'applicationReportUrl' => $applicationReportUrl ?? route('admin.document.application-form-report'),
    ])
@endpush
