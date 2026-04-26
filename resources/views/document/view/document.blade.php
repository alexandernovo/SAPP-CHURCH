@extends('layouts.document')

@section('title', 'Document — ' . config('app.name', 'SAPP Church'))

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
        BURIAL REPORT AS OF <span id="sappcDocReportLabel">{{ strtoupper($reportLabel) }}</span>
    </h2>

    <div class="sappc-doc-table-wrap">
        <table class="sappc-doc-table">
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
                    <td colspan="7" class="text-center py-3">Loading…</td>
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
    @include('document.js.documentScipt', ['burialReportUrl' => route('admin.document.burial-report')])
@endpush
