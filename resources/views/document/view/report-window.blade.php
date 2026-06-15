@extends('layouts.documentReportWindow')

@section('title', ($service_heading ?? 'Report') . ' — ' . config('app.name', 'SAPP Church'))

@section('content')
    <div class="sappc-doc-sheet sappc-doc-report-window_sheet" style="display:block;">
        <div class="sappc-doc-sheet__actions no-print sappc-doc-report-window_toolbar" role="toolbar">
            <button type="button" class="sappc-doc-picker_btn sappc-doc-toolbar_print" onclick="window.print()">
                <i class="fa-solid fa-print" aria-hidden="true"></i>
                Print Report
            </button>
            <button type="button" class="sappc-doc-toolbar_filters" onclick="window.close()">
                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                Close window
            </button>
        </div>

        <header class="sappc-doc-letterhead" aria-label="Parish header">
            <img class="sappc-doc-letterhead_crest" src="{{ asset('assets/logos/DSA.jpg') }}" width="100" height="100" alt="">
            <div class="sappc-doc-letterhead_center">
                <p class="sappc-doc-letterhead_line sappc-doc-letterhead_line--primary">THE ROMAN CATHOLIC PARISH OF ST. ANTHONY OF PADUA</p>
                <p class="sappc-doc-letterhead_line sappc-doc-letterhead_line--sub">DIOCESE OF SAN JOSE DE ANTIQUE</p>
                <p class="sappc-doc-letterhead_line sappc-doc-letterhead_line--sub">BARBAZA, 5706, ANTIQUE, PHILIPPINES</p>
            </div>
            <img class="sappc-doc-letterhead_seal" src="{{ asset('assets/logos/SAPPC.png') }}" width="100" height="100" alt="">
        </header>

        <h2 class="sappc-doc-report-title">
            {{ strtoupper($service_heading ?? '') }} REPORT OF {{ strtoupper($report_label ?? '') }}
        </h2>

        <div class="sappc-doc-table-wrap">
            <table class="sappc-doc-table">
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
                <tbody>
                    @forelse ($rows ?? [] as $r)
                        <tr>
                            <td>{{ $r['no'] ?? '' }}</td>
                            <td>{{ $r['reference_code'] ?? '' }}</td>
                            <td>{{ $r['client'] ?? '' }}</td>
                            <td>{{ $r['address'] ?? '' }}</td>
                            <td>{{ $r['contact_number'] ?? '' }}</td>
                            <td>{{ $r['date'] ?? '' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-3">No records for this month.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <footer class="sappc-doc-signature">
            <p class="sappc-doc-signature_name">REV. FR. RAMON A. NAVALLASCA</p>
            <p class="sappc-doc-signature_role">Parish Priest</p>
        </footer>
    </div>
@endsection
