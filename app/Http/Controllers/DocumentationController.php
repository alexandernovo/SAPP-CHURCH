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
            'applicationReportUrl' => route('admin.document.application-form-report'),
        ]);
    }

    public function reportWindow(Request $request): View
    {
        $validated = $request->validate([
            'month' => ['nullable', 'string'],
            'service_type' => ['required', 'string', 'in:christening,burial,confirmation,wedding'],
        ]);

        $data = $this->buildApplicationFormReportData(
            $this->resolveMonthString($validated['month'] ?? null),
            (string) $validated['service_type']
        );

        return view('document.view.report-window', $data);
    }

    public function applicationFormReport(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'month' => ['nullable', 'string'],
            'service_type' => ['required', 'string', 'in:christening,burial,confirmation,wedding'],
        ]);

        $data = $this->buildApplicationFormReportData(
            $this->resolveMonthString($validated['month'] ?? null),
            (string) $validated['service_type']
        );

        return response()->json([
            'ok' => true,
            'month' => $data['month'],
            'service_type' => $data['service_type'],
            'report_label' => $data['report_label'],
            'service_heading' => $data['service_heading'],
            'rows' => $data['rows'],
        ]);
    }

    /**
     * Rows for the document report: records from the live registry table for the
     * calendar month of `dateCreated` (christening, confirmation, wedding, burial).
     *
     * @return array{month: string, service_type: string, report_label: string, service_heading: string, rows: array<int, array<string, mixed>>}
     */
    private function buildApplicationFormReportData(string $month, string $serviceType): array
    {
        try {
            $carbon = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        } catch (\Throwable) {
            $carbon = now()->startOfMonth();
            $month = $carbon->format('Y-m');
        }

        /** @var array<string, array{0: string, 1: string}> */
        $registry = [
            'christening' => ['christening', 'christeningId'],
            'confirmation' => ['confirmation', 'confirmationId'],
            'wedding' => ['wedding', 'weddingId'],
            'burial' => ['burial', 'burialId'],
        ];

        [$table, $idColumn] = $registry[$serviceType];

        $rows = DB::table($table)
            ->whereYear('dateCreated', $carbon->year)
            ->whereMonth('dateCreated', $carbon->month)
            ->orderByDesc('dateCreated')
            ->orderByDesc($idColumn)
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
                'address' => ($r->address ?? '') !== '' ? ClientNameDisplay::formatAddress((string) $r->address) : '—',
                'sex' => ($r->sex ?? '') !== '' ? (string) $r->sex : '—',
                'contact_number' => ($r->contactNum ?? '') !== '' ? (string) $r->contactNum : '—',
                'date' => ClientNameDisplay::formatDateCreated($r->dateCreated ?? null),
            ];
        }

        $serviceHeading = match ($serviceType) {
            'christening' => 'CHRISTENING',
            'burial' => 'BURIAL',
            'confirmation' => 'CONFIRMATION',
            'wedding' => 'WEDDING',
            default => 'DOCUMENT',
        };

        return [
            'month' => $month,
            'service_type' => $serviceType,
            'report_label' => $carbon->translatedFormat('F Y'),
            'service_heading' => $serviceHeading,
            'rows' => $out,
        ];
    }

    public function burialReport(Request $request): JsonResponse
    {
        $month = $this->resolveMonthString($request->input('month'));
        $data = $this->buildApplicationFormReportData($month, 'burial');

        return response()->json([
            'ok' => true,
            'month' => $data['month'],
            'report_label' => $data['report_label'],
            'rows' => $data['rows'],
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
