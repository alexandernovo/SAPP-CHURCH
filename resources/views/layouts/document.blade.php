@extends('layouts.adminDashboard')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/document/sappcDocumentLayout.css') }}">
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
            <div class="sappc-doc-picker-card">
                <a href="{{ route('admin.document') }}" class="sappc-doc-picker_close" title="Close" aria-label="Close">&times;</a>

                <div class="sappc-doc-picker_header">
                    <img
                        class="sappc-doc-picker_logo"
                        src="{{ asset('assets/logos/SAPPC.png') }}"
                        width="120"
                        height="120"
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
            <div class="sappc-doc-sheet__actions no-print" id="sappcDocToolbar" role="toolbar" aria-label="Report export and print">
                <div class="sappc-doc-toolbar_month-field">
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

            @yield('document')
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function ($) {
            var $type = $('#sappcDocType');
            var $btn = $('#sappcDocViewReportBtn');
            var $monthPicker = $('#sappcDocReportMonth');
            var $monthToolbar = $('#sappcDocReportMonthToolbar');

            function sync() {
                var ok = $type.val() !== '' && $type.val() != null;
                $btn.prop('disabled', !ok);
            }

            $type.on('change', sync);
            sync();

            $monthToolbar.on('change', function () {
                var v = $(this).val() || '';
                if (v && $monthPicker.length) {
                    $monthPicker.val(v).trigger('change');
                }
            });

            $('#sappcDocViewReportBtn').on('click', function () {
                var type = $type.val();
                if (!type) {
                    return;
                }

                if ($monthToolbar.length && $monthPicker.length) {
                    $monthToolbar.val($monthPicker.val() || '');
                }

                $('#sappcDocPickerWrap').addClass('sappc-doc-picker-wrap--hidden');
                $('#sappcDocumentSheet').show();
                $('#sappcDocPageRoot').addClass('sappc-doc-page--report-active');
                $(document).trigger('sappc:doc-report', {
                    type: type,
                    month: $monthPicker.val() || '',
                });
            });

            $('#sappcDocPrintBtn').on('click', function () {
                window.print();
            });

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

            $(document).on('click', '[data-doc-export]', function () {
                var fmt = ($(this).attr('data-doc-export') || '').toLowerCase();
                if (fmt === 'pdf' || fmt === 'docx' || fmt === 'xlsx') {
                    window.alert('Download ' + fmt.toUpperCase() + ' is not wired yet. Use Print Report for a paper copy.');
                }
            });
        })(jQuery);
    </script>
    @stack('document_scripts')
@endpush
