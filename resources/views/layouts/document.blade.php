@extends('layouts.adminDashboard')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/document/sappcDocumentLayout.css') }}">
@endpush

@section('content')
    <div class="sappc-doc-page">
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

        <div class="sappc-doc-picker-wrap">
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
                    <button type="button" class="sappc-doc-picker_btn" id="sappcDocViewReportBtn" disabled>View Report</button>
                </div>

                <input
                    type="month"
                    class="sappc-doc-hidden-month"
                    id="sappcDocReportMonth"
                    name="report_month"
                    value="{{ $docReportMonth ?? request('month', now()->format('Y-m')) }}"
                    aria-hidden="true"
                    tabindex="-1"
                >
            </div>
        </div>

        <div class="sappc-doc-sheet" id="sappcDocumentSheet" style="display:none;">
            @yield('document')
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function ($) {
            var $type = $('#sappcDocType');
            var $btn = $('#sappcDocViewReportBtn');

            function sync() {
                $btn.prop('disabled', $type.val() === '' || !$type.val());
            }

            $type.on('change', sync);
            sync();

            $('#sappcDocViewReportBtn').on('click', function () {
                var type = $type.val();
                if (!type) {
                    return;
                }

                if (type !== 'burial') {
                    alert('Selected report type is not available yet.');
                    return;
                }

                $('#sappcDocumentSheet').show();
            });
        })(jQuery);
    </script>
    @stack('document_scripts')
@endpush
