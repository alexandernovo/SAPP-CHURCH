@extends('layouts.adminDashboard')

@section('title', 'Report — ' . config('app.name', 'SAPP Church'))

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/document/sappcDocumentLayout.css') }}">
    <link rel="stylesheet" href="{{ asset('css/certification/certificationReport.css') }}">
@endpush

@section('content')
    <div class="sappc-doc-page" id="sappcCertPageRoot">
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

        <div class="sappc-doc-picker-wrap" id="sappcCertPickerWrap">
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
                        <label for="sappcCertReportType" class="sappc-doc-picker_label">Certification Report Type:</label>
                        <div class="sappc-doc-picker_select-wrap">
                            <select id="sappcCertReportType" class="sappc-doc-picker_select" aria-label="Certification report type">
                                <option value="" selected disabled>Please Select</option>
                                <option value="christening">CHRISTENING</option>
                                <option value="wedding">WEDDING</option>
                            </select>
                        </div>
                    </div>

                    <div class="sappc-doc-picker_field">
                        <label for="sappcCertReportMonth" class="sappc-doc-picker_label">Select Month:</label>
                        <div class="sappc-doc-picker_month-wrap">
                            <input
                                type="month"
                                id="sappcCertReportMonth"
                                class="sappc-doc-picker_month"
                                name="report_month"
                                value="{{ $certReportMonth ?? request('month', now()->format('Y-m')) }}"
                                aria-label="Report month and year"
                            >
                        </div>
                    </div>

                    <button type="button" class="sappc-doc-picker_btn" id="sappcCertViewReportBtn" disabled>View Report</button>
                </div>
            </div>
        </div>

        <div class="sappc-doc-sheet" id="sappcCertDocumentSheet" style="display:none;">
            <div class="sappc-doc-sheet__actions no-print sappc-cert-report-toolbar" role="toolbar" aria-label="Report export and print">
                <div class="sappc-doc-toolbar_month-field sappc-cert-report-toolbar_month">
                    <label for="sappcCertReportMonthToolbar" class="sappc-doc-toolbar_month-label">Select Month and Year:</label>
                    <div class="sappc-doc-picker_month-wrap sappc-doc-toolbar_month-wrap">
                        <input
                            type="month"
                            id="sappcCertReportMonthToolbar"
                            class="sappc-doc-picker_month sappc-doc-toolbar_month-input"
                            value="{{ $certReportMonth ?? request('month', now()->format('Y-m')) }}"
                            aria-label="Change report month and year"
                        >
                    </div>
                </div>
                <span class="sappc-cert-report-toolbar_spacer" aria-hidden="true"></span>
                <button type="button" class="sappc-doc-picker_btn sappc-doc-toolbar_print" id="sappcCertPrintBtn">
                    <i class="fa-solid fa-print" aria-hidden="true"></i>
                    Print Report
                </button>
                <div class="sappc-doc-toolbar_exports" aria-label="Download report">
                    <button type="button" class="sappc-doc-toolbar_icon" data-cert-export="pdf" title="Download PDF" aria-label="Download PDF">
                        <i class="fa-solid fa-file-pdf" aria-hidden="true"></i>
                    </button>
                    <button type="button" class="sappc-doc-toolbar_icon" data-cert-export="docx" title="Download Word" aria-label="Download Word">
                        <i class="fa-solid fa-file-word" aria-hidden="true"></i>
                    </button>
                    <button type="button" class="sappc-doc-toolbar_icon" data-cert-export="xlsx" title="Download Excel" aria-label="Download Excel">
                        <i class="fa-solid fa-file-excel" aria-hidden="true"></i>
                    </button>
                </div>
            </div>

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

            <h2 class="sappc-doc-report-title sappc-doc-report-title--stack" id="sappcCertReportTitle">
                <span class="sappc-doc-report-title__line" id="sappcCertReportService">CHRISTENING</span>
                <span class="sappc-doc-report-title__line">CERTIFICATION REPORT</span>
                <span class="sappc-doc-report-title__line">OF <span id="sappcCertReportLabel">{{ strtoupper($certReportLabel ?? '') }}</span></span>
            </h2>

            <div class="sappc-doc-table-wrap" id="sappcCertTableOuter">
                <table id="sappcCertDataTable" class="sappc-doc-table table table-bordered align-middle w-100">
                    <thead>
                        <tr>
                            <th scope="col">NO.</th>
                            <th scope="col">REFERENCE CODE</th>
                            <th scope="col">CLIENT</th>
                                <th scope="col">ADDRESS</th>
                                <th scope="col">CONTACT NUMBER</th>
                                <th scope="col">DATE &amp; TIME</th>
                        </tr>
                    </thead>
                    <tbody id="sappcCertTableBody">
                        <tr>
                            <td colspan="6" class="text-center py-3">Select a certification type and click View Report.</td>
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
@endsection

@push('scripts')
    <script>
        (function ($) {
            var recordsUrl = @json(route('admin.certification.records'));
            var $root = $('#sappcCertPageRoot');
            var $picker = $('#sappcCertPickerWrap');
            var $sheet = $('#sappcCertDocumentSheet');
            var $type = $('#sappcCertReportType');
            var $btn = $('#sappcCertViewReportBtn');
            var $monthPicker = $('#sappcCertReportMonth');
            var $monthToolbar = $('#sappcCertReportMonthToolbar');
            var $tbody = $('#sappcCertTableBody');
            var $service = $('#sappcCertReportService');
            var $label = $('#sappcCertReportLabel');

            function esc(v) {
                return $('<div/>').text(v == null ? '' : String(v)).html();
            }

            function syncViewBtn() {
                var ok = $type.val() !== '' && $type.val() != null;
                $btn.prop('disabled', !ok);
                var typeVal = ($type.val() || '').toString().trim();
                if (typeVal && $service.length) {
                    var optText = $type.find('option:selected').text();
                    $service.text(String(optText || typeVal).toUpperCase());
                }
            }

            function showPicker() {
                $picker.removeClass('sappc-doc-picker-wrap--hidden');
                $sheet.hide();
                $root.removeClass('sappc-doc-page--report-active');
            }

            function showSheet() {
                $picker.addClass('sappc-doc-picker-wrap--hidden');
                $sheet.show();
                $root.addClass('sappc-doc-page--report-active');
            }

            function renderRows(rows) {
                if (!Array.isArray(rows) || rows.length === 0) {
                    $tbody.html(
                        '<tr><td colspan="6" class="text-center text-muted py-3">No certification records found.</td></tr>'
                    );
                    return;
                }
                var html = '';
                rows.forEach(function (r) {
                    html +=
                        '<tr>' +
                        '<td class="text-center">' + esc(r.no) + '</td>' +
                        '<td>' + esc(r.reference_code) + '</td>' +
                        '<td>' + esc(r.client) + '</td>' +
                        '<td>' + esc(r.address) + '</td>' +
                        '<td>' + esc(r.contact_number) + '</td>' +
                        '<td>' + esc(r.date) + '</td>' +
                        '</tr>';
                });
                $tbody.html(html);
            }

            function monthLabelFromYm(ym) {
                var parts = String(ym || '').split('-');
                if (parts.length !== 2) {
                    return '';
                }
                var year = parseInt(parts[0], 10);
                var monthNum = parseInt(parts[1], 10);
                if (isNaN(year) || isNaN(monthNum) || monthNum < 1 || monthNum > 12) {
                    return '';
                }
                var names = [
                    'January', 'February', 'March', 'April', 'May', 'June',
                    'July', 'August', 'September', 'October', 'November', 'December'
                ];
                return names[monthNum - 1] + ' ' + year;
            }

            function updateReportLabel(monthVal) {
                var labelText = monthLabelFromYm(monthVal);
                if ($label.length && labelText) {
                    $label.text(labelText.toUpperCase());
                }
            }

            function syncMonthInputs(monthVal) {
                if (!monthVal) {
                    return;
                }
                if ($monthPicker.length) {
                    $monthPicker.val(monthVal);
                }
                if ($monthToolbar.length) {
                    $monthToolbar.val(monthVal);
                }
                updateReportLabel(monthVal);
            }

            function pushMonthToUrl(monthVal) {
                if (!monthVal) {
                    return;
                }
                var u = new URL(window.location.href);
                u.searchParams.set('month', monthVal);
                window.history.replaceState({}, '', u);
            }

            function loadReport(forcedMonth) {
                var typeVal = ($type.val() || '').toString().trim();
                var monthVal = (forcedMonth || '').toString().trim();
                if (!monthVal) {
                    monthVal = ($monthToolbar.val() || $monthPicker.val() || '').toString().trim();
                }
                if (!typeVal || !monthVal) {
                    return;
                }

                syncMonthInputs(monthVal);
                pushMonthToUrl(monthVal);

                $tbody.html(
                    '<tr><td colspan="6" class="text-center text-muted py-3">Loading…</td></tr>'
                );

                $.ajax({
                    url: recordsUrl,
                    type: 'GET',
                    dataType: 'json',
                    cache: false,
                    data: {
                        report_type: typeVal,
                        month: monthVal,
                        _: Date.now(),
                    },
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                })
                    .done(function (res) {
                        if (!res || !res.ok) {
                            $tbody.html(
                                '<tr><td colspan="6" class="text-center text-danger py-3">Invalid response.</td></tr>'
                            );
                            return;
                        }
                        if ($service.length && res.service_heading) {
                            $service.text(String(res.service_heading).toUpperCase());
                        }
                        updateReportLabel(monthVal);
                        renderRows(res.rows || []);
                    })
                    .fail(function (xhr) {
                        var msg = 'Could not load certification report.';
                        var data = xhr && xhr.responseJSON ? xhr.responseJSON : null;
                        if (data && data.message) {
                            msg = data.message;
                        }
                        $tbody.html(
                            '<tr><td colspan="6" class="text-center text-danger py-3">' + esc(msg) + '</td></tr>'
                        );
                    });
            }

            $type.on('change', syncViewBtn);
            syncViewBtn();

            var initialMonth = ($monthPicker.val() || $monthToolbar.val() || '').toString().trim();
            if (initialMonth) {
                syncMonthInputs(initialMonth);
            }

            $monthToolbar.on('change input', function () {
                var v = ($(this).val() || '').toString().trim();
                if (!v) {
                    return;
                }
                syncMonthInputs(v);
                pushMonthToUrl(v);
                if ($sheet.is(':visible')) {
                    loadReport(v);
                }
            });

            $monthPicker.on('change', function () {
                var m = ($(this).val() || '').toString().trim();
                if (!m) {
                    return;
                }
                syncMonthInputs(m);
                pushMonthToUrl(m);
                if ($sheet.is(':visible')) {
                    loadReport(m);
                }
            });

            $btn.on('click', function () {
                var typeVal = ($type.val() || '').toString().trim();
                if (!typeVal) {
                    return;
                }
                var monthVal = ($monthPicker.val() || '').toString().trim();
                syncMonthInputs(monthVal);
                showSheet();
                loadReport(monthVal);
            });

            $('#sappcCertChangeReportBtn').on('click', function () {
                showPicker();
            });

            $('#sappcCertPrintBtn').on('click', function () {
                window.print();
            });

            $(document).on('click', '[data-cert-export]', function () {
                var fmt = ($(this).attr('data-cert-export') || '').toLowerCase();
                if (fmt === 'pdf' || fmt === 'docx' || fmt === 'xlsx') {
                    window.alert(
                        'Download ' + fmt.toUpperCase() + ' is not wired yet. Use Print Report for a paper copy.'
                    );
                }
            });
        })(jQuery);
    </script>
@endpush
