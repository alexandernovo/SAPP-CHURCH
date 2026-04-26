<?php

namespace App\Http\Controllers;

use App\Support\ClientNameDisplay;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DocumentationController extends Controller
{
    public function document(Request $request): View
    {
        $docReportMonth = $this->resolveMonthString($request->input('month'));
        $reportLabel = Carbon::createFromFormat('Y-m', $docReportMonth)->translatedFormat('F Y');

        return view('document.view.document', [
            'docReportMonth' => $docReportMonth,
            'reportLabel' => $reportLabel,
        ]);
    }

    public function burialReport(Request $request): JsonResponse
    {
        $month = $this->resolveMonthString($request->input('month'));

        try {
            $carbon = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        } catch (\Throwable) {
            $carbon = now()->startOfMonth();
            $month = $carbon->format('Y-m');
        }

        $rows = DB::table('burial')
            ->whereYear('dateCreated', $carbon->year)
            ->whereMonth('dateCreated', $carbon->month)
            ->orderBy('dateCreated')
            ->orderBy('burialId')
            ->get();

        $out = [];
        $n = 1;
        foreach ($rows as $r) {
            $out[] = [
                'no' => $n++,
                'reference_code' => (string) ($r->referenceCode ?? ''),
                'client' => ClientNameDisplay::fullDisplayName(
                    $r->clientFName ?? null,
                    $r->clientMName ?? null,
                    $r->clientLName ?? null
                ) ?: '—',
                'address' => ($r->address ?? '') !== '' ? (string) $r->address : '—',
                'sex' => ($r->sex ?? '') !== '' ? (string) $r->sex : '—',
                'contact_number' => ($r->contactNum ?? '') !== '' ? (string) $r->contactNum : '—',
                'date' => ClientNameDisplay::formatDateCreated($r->dateCreated ?? null),
            ];
        }

        return response()->json([
            'ok' => true,
            'month' => $month,
            'report_label' => $carbon->translatedFormat('F Y'),
            'rows' => $out,
        ]);
    }

    private function resolveMonthString(?string $value): string
    {
        if ($value === null || $value === '') {
            return now()->format('Y-m');
        }
        try {
            return Carbon::createFromFormat('Y-m', $value)->format('Y-m');
        } catch (\Throwable) {
            return now()->format('Y-m');
        }
    }
}
