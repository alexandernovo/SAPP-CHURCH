@extends('layouts.document')

@section('title', 'Document — ' . config('app.name', 'SAPP Church'))

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" crossorigin="anonymous">
@endpush

@section('document')
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
        <span id="sappcDocReportService">DOCUMENT</span> REPORT AS OF
        <span id="sappcDocReportLabel">{{ strtoupper($reportLabel) }}</span>
    </h2>

    <div class="sappc-doc-table-wrap" id="sappcDocTableOuter">
        <table id="sappcDocDataTable" class="sappc-doc-table table table-bordered table-hover align-middle w-100">
            <thead>
                <tr>
                    <th scope="col">NO.</th>
                    <th scope="col">REFERENCE CODE</th>
                    <th scope="col">CLIENT</th>
                    <th scope="col">ADDRESS</th>
                    <th scope="col">SEX</th>
                    <th scope="col">CONTACT NUMBER</th>
                    <th scope="col">DATE</th>
                </tr>
            </thead>
            <tbody id="sappcDocTableBody">
                <tr>
                    <td colspan="7" class="text-center py-3">Select a report type and click View Report.</td>
                </tr>
            </tbody>
        </table>
    </div>

    <footer class="sappc-doc-signature">
        <p class="sappc-doc-signature_name">REV. FR. RAMON A. NAVALLASCA</p>
        <p class="sappc-doc-signature_role">Parish Priest</p>
    </footer>
@endsection

@push('document_scripts')
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js" crossorigin="anonymous"></script>
    @include('document.js.documentScipt', [
        'applicationReportUrl' => $applicationReportUrl ?? route('admin.document.application-form-report'),
    ])
@endpush
