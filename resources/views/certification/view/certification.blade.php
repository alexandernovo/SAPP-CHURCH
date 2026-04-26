@extends('layouts.adminDashboard')

@section('title', 'Report — ' . config('app.name', 'SAPP Church'))

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/certification/certificationReport.css') }}">
@endpush

@section('content')
    <div class="sappc-cert-report">
        <header class="sappc-cert-report_head">
            <h1 class="sappc-cert-report_title">
                <i class="fa-solid fa-file-lines" aria-hidden="true"></i>
                REPORT
            </h1>
            <p class="sappc-cert-report_breadcrumb mb-0">
                <a href="{{ route('admin.dashboard') }}">Dashboard</a>
                <span class="sappc-cert-report_sep" aria-hidden="true">|</span>
                <a href="{{ route('admin.document') }}">Document</a>
            </p>
        </header>

        <div class="sappc-cert-report_center">
            <div class="sappc-cert-report_card">
                <a
                    href="{{ route('admin.document') }}"
                    class="sappc-cert-report_close"
                    aria-label="Close and return to Document"
                >&times;</a>

                <div class="sappc-cert-report_card-header">
                    <img
                        class="sappc-cert-report_logo"
                        src="{{ asset('assets/logos/SAPPC.png') }}"
                        width="120"
                        height="120"
                        alt="Saint Anthony of Padua Parish"
                    >
                    <p class="sappc-cert-report_church">SAINT ANTHONY OF PADUA PARISH CHURCH</p>
                </div>

                <hr class="sappc-cert-report_divider" />

                <div class="sappc-cert-report_body">
                    <label class="sappc-cert-report_label" for="certReportType">Certification Report Type:</label>
                    <select class="sappc-cert-report_select" id="certReportType" name="report_type" aria-label="Certification report type">
                        <option value="">Please Select</option>
                        <option value="christening">CHRISTENING</option>
                        <option value="wedding">WEDDING</option>
                    </select>
                    <button type="button" class="sappc-cert-report_cta" id="certReportViewBtn" disabled>View Report</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function ($) {
            const $type = $('#certReportType');
            const $btn = $('#certReportViewBtn');
            const routes = {
                christening: @json(route('admin.christening')),
                wedding: @json(route('admin.wedding'))
            };
            function sync() {
                $btn.prop('disabled', $type.val() === '');
            }
            $type.on('change', sync);
            sync();
            $btn.on('click', function () {
                const v = $type.val();
                if (v && routes[v]) {
                    window.location.href = routes[v];
                }
            });
        })(jQuery);
    </script>
@endpush
