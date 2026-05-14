<?php

namespace App\Http\Controllers;

use App\Support\ClientNameDisplay;
use App\Support\DocumentationApplicationReportWriter;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ChristeningController extends Controller
{
    private const CHRISTENING_GODPARENT_FORM_ROWS = 13;

    private const CHRISTENING_FIXED_BAPTISM_PLACE = 'Saint Anthony of Padua Parish Church';

    private const CHRISTENING_REFERENCE_SUFFIX = 'B';

    private const CHRISTENING_REFERENCE_CHARSET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    public function index(Request $request): View
    {

        $request->merge(['registry_type' => 'christening']);

        $dashboard = app(DashboardController::class);
        $data = $dashboard->registryIndexData($request);

        return view('christening.view.christening', [
            'records' => $data['records'],
            'initialTablePayload' => $data['initialTablePayload'],
            'perPageOptions' => DashboardController::perPageOptionsList(),
            'letterOptions' => range('A', 'Z'),
            'generatedReferenceCode' => $this->generateUniqueChristeningReferenceCode(),
        ]);
    }

    public function certificationPage(Request $request): View
    {
        $certReportMonth = $this->resolveCertificationReportMonth($request->input('month'));
        $certReportLabel = Carbon::createFromFormat('Y-m', $certReportMonth)->translatedFormat('F Y');

        return view('certification.view.certification', [
            'certReportMonth' => $certReportMonth,
            'certReportLabel' => $certReportLabel,
        ]);
    }

    public function certificationReportWindow(Request $request): View
    {
        $validated = $request->validate([
            'report_type' => ['nullable', 'string', 'in:christening,wedding'],
            'month' => ['nullable', 'string'],
        ]);
        $reportType = (string) ($validated['report_type'] ?? '');
        $month = $this->resolveCertificationReportMonth($validated['month'] ?? null);
        $reportLabel = Carbon::createFromFormat('Y-m', $month)->translatedFormat('F Y');

        return view('certification.view.certificationReportWindow', [
            'reportType' => $reportType,
            'reportMonth' => $month,
            'reportLabel' => $reportLabel,
            'serviceHeading' => $reportType === 'wedding' ? 'WEDDING CERTIFICATION' : 'CHRISTENING CERTIFICATION',
            'rows' => $this->buildCertificationRowsFromDetailsTable($reportType, $month),
        ]);
    }

    public function certificationRecords(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'report_type' => ['nullable', 'string', 'in:christening,wedding'],
            'month' => ['nullable', 'string'],
        ]);

        $reportType = (string) ($validated['report_type'] ?? '');
        $month = $this->resolveCertificationReportMonth($validated['month'] ?? null);
        $out = $this->buildCertificationRowsFromDetailsTable($reportType, $month);
        $reportLabel = Carbon::createFromFormat('Y-m', $month)->translatedFormat('F Y');
        $serviceHeading = $reportType === 'wedding' ? 'WEDDING CERTIFICATION' : 'CHRISTENING CERTIFICATION';

        return response()->json([
            'ok' => true,
            'rows' => $out,
            'report_type' => $reportType,
            'month' => $month,
            'report_label' => $reportLabel,
            'service_heading' => $serviceHeading,
        ]);
    }

    /**
     * @return array<int, array<string, string|int>>
     */
    private function buildCertificationRowsFromDetailsTable(string $reportType, ?string $monthYm = null): array
    {
        $rowsQuery = DB::table('certification_details')
            ->orderByDesc('date')
            ->orderByDesc('certificationDetailsId');

        if ($reportType !== '') {
            $suffixMap = [
                'christening' => '-'.self::CHRISTENING_REFERENCE_SUFFIX,
                'wedding' => '-W',
            ];
            $suffix = $suffixMap[$reportType] ?? '';
            if ($suffix !== '') {
                $rowsQuery->where('referenceCode', 'like', '%'.$suffix);
            }
        }

        $resolvedMonth = $monthYm !== null && $monthYm !== '' ? $this->resolveCertificationReportMonth($monthYm) : null;
        if ($resolvedMonth !== null) {
            try {
                $carbon = Carbon::createFromFormat('Y-m', $resolvedMonth)->startOfMonth();
                $rowsQuery->whereYear('date', $carbon->year)
                    ->whereMonth('date', $carbon->month);
            } catch (\Throwable) {
                // ignore invalid month; return unfiltered rows for the chosen report type
            }
        }

        $rows = $rowsQuery->get();
        $out = [];
        $n = 1;
        foreach ($rows as $row) {
            $out[] = [
                'no' => $n++,
                'reference_code' => trim((string) ($row->referenceCode ?? '')),
                'client' => trim((string) ($row->client ?? '')),
                'address' => trim((string) ($row->address ?? '')),
                'sex' => trim((string) ($row->sex ?? '')),
                'contact_number' => trim((string) ($row->contactNumber ?? '')),
                'date' => ClientNameDisplay::formatDateCreated($row->date ?? null),
            ];
        }

        return $out;
    }

    private function resolveCertificationReportMonth(?string $value): string
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

    private function generateUniqueChristeningReferenceCode(): string
    {
        $year = (int) date('Y');

        for ($attempt = 0; $attempt < 50; $attempt++) {
            $code = $this->formatChristeningReferenceCode($year, $this->randomChristeningReferenceSegment(7));
            if (! DB::table('christening')->where('referenceCode', $code)->exists()) {
                return $code;
            }
        }

        throw new \RuntimeException('Could not generate a unique christening reference code.');
    }

    private function formatChristeningReferenceCode(int $year, string $middle): string
    {
        return $year.'-'.$middle.'-'.self::CHRISTENING_REFERENCE_SUFFIX;
    }

    private function randomChristeningReferenceSegment(int $length): string
    {
        $max = strlen(self::CHRISTENING_REFERENCE_CHARSET) - 1;
        $out = '';
        for ($i = 0; $i < $length; $i++) {
            $out .= self::CHRISTENING_REFERENCE_CHARSET[random_int(0, $max)];
        }

        return $out;
    }

    public function scheduleChristening(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'christening_id' => ['nullable', 'integer'],
            'reference_code' => ['nullable', 'string', 'max:255'],
            'client' => ['nullable', 'string', 'max:255'],
            'contact_number' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'sex' => ['nullable', 'string', 'max:50'],
            'schedule_date' => ['required', 'date_format:Y-m-d'],
            'schedule_time' => ['required', 'date_format:H:i'],
        ]);
        $scheduleAt = Carbon::createFromFormat(
            'Y-m-d H:i',
            $validated['schedule_date'].' '.$validated['schedule_time']
        )->format('Y-m-d H:i:s');

        $ref = trim((string) ($validated['reference_code'] ?? ''));
        if ($ref === '') {
            $ref = $this->generateUniqueChristeningReferenceCode();
        }

        $query = DB::table('christening');
        if (! empty($validated['christening_id'])) {
            $query->where('christeningId', $validated['christening_id']);
        } else {
            $query->where('referenceCode', $validated['reference_code']);
        }
        $existing = (clone $query)->first();

        $parts = preg_split('/\s+/', trim((string) ($validated['client'] ?? ''))) ?: [];
        $first = $parts[0] ?? null;
        $last = count($parts) > 1 ? array_pop($parts) : null;
        $middle = count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : null;

        if (! $existing) {
            $clientTrim = trim((string) ($validated['client'] ?? ''));
            if ($clientTrim === '' || $last === null || $last === '') {
                return response()->json([
                    'ok' => false,
                    'message' => 'Please enter the client\'s full name (first name and last name).',
                    'errors' => [
                        'client' => ['Enter at least two name parts (e.g. Juan Dela Cruz).'],
                    ],
                ], 422);
            }

            try {
                $newId = DB::transaction(function () use ($ref, $first, $middle, $last, $validated, $scheduleAt) {
                    $user = Auth::user();
                    $customerRow = [
                        'customerFName' => $first ?? '',
                        'customerMName' => $middle,
                        'customerLName' => $last ?? '',
                        'updatedAt' => now(),
                        'createdBy' => $user?->userName ?? $user?->userfName ?? null,
                        'userId' => $user?->getAuthIdentifier(),
                    ];
                    $customerRow = array_filter($customerRow, fn ($v) => $v !== null);
                    $customerId = DB::table('customer')->insertGetId($customerRow);

                    $insertData = [
                        'referenceCode' => $ref,
                        'clientFName' => $first ?? '',
                        'clientMName' => $middle,
                        'clientLName' => $last ?? '',
                        'contactNum' => $validated['contact_number'] ?? null,
                        'sex' => $validated['sex'] ?? null,
                        'address' => $validated['address'] ?? null,
                        'scheduleRequested' => $scheduleAt,
                        'paymentStatus' => 'Unpaid',
                        'dateCreated' => now(),
                        'customerId' => $customerId,
                    ];
                    $insertData = array_filter($insertData, fn ($v) => $v !== null);

                    return (int) DB::table('christening')->insertGetId($insertData);
                });
            } catch (QueryException $e) {
                report($e);

                return response()->json([
                    'ok' => false,
                    'message' => 'Could not save the schedule. Check that all required fields are filled and try again.',
                ], 422);
            }

            return response()->json([
                'ok' => true,
                'message' => 'Christening schedule created successfully.',
                'created' => true,
                'data' => [
                    'christening_id' => $newId,
                    'reference_code' => $ref,
                    'schedule_requested' => $scheduleAt,
                ],
            ]);
        }
        if (! empty($existing->customerId)) {
            $user = Auth::user();
            $customerUpdate = [
                'customerFName' => $first,
                'customerMName' => $middle,
                'customerLName' => $last,
                'updatedAt' => now(),
            ];
            $customerUpdate = array_filter($customerUpdate, fn ($v) => $v !== null);
            if ($customerUpdate !== []) {
                DB::table('customer')
                    ->where('customerId', $existing->customerId)
                    ->update($customerUpdate);
            }
        }
        $updateData = ['scheduleRequested' => $scheduleAt];
        if (! empty($validated['contact_number'])) {
            $updateData['contactNum'] = $validated['contact_number'];
        }
        if (! empty($validated['address'])) {
            $updateData['address'] = $validated['address'];
        }
        if (! empty($validated['sex'])) {
            $updateData['sex'] = $validated['sex'];
        }
        if ($first) {
            $updateData['clientFName'] = $first;
        }
        if ($middle !== null) {
            $updateData['clientMName'] = $middle;
        }
        if ($last) {
            $updateData['clientLName'] = $last;
        }
        if (empty($existing->paymentStatus)) {
            $updateData['paymentStatus'] = 'Unpaid';
        }
        $query->update($updateData);

        return response()->json([
            'ok' => true,
            'message' => 'Christening schedule updated successfully.',
            'created' => false,
            'data' => [
                'christening_id' => $existing->christeningId ?? null,
                'reference_code' => $existing->referenceCode ?? $ref,
                'schedule_requested' => $scheduleAt,
            ],
        ]);
    }

    public function christeningScheduleDetails(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'christening_id' => ['required', 'integer', 'min:1'],
        ]);
        $christeningId = (int) $validated['christening_id'];

        $row = DB::table('christening')->where('christeningId', $christeningId)->first();
        if ($row === null) {
            return response()->json([
                'ok' => false,
                'message' => 'Christening record not found.',
            ], 404);
        }

        $client = ClientNameDisplay::fullDisplayName(
            $row->clientFName ?? null,
            $row->clientMName ?? null,
            $row->clientLName ?? null,
        );

        $scheduleDate = null;
        $scheduleTime = null;
        if (! empty($row->scheduleRequested)) {
            try {
                $dt = Carbon::parse($row->scheduleRequested);
                $scheduleDate = $dt->format('Y-m-d');
                $scheduleTime = $dt->format('H:i');
            } catch (\Throwable) {
                $scheduleDate = null;
                $scheduleTime = null;
            }
        }

        return response()->json([
            'ok' => true,
            'data' => [
                'christening_id' => $christeningId,
                'reference_code' => (string) ($row->referenceCode ?? ''),
                'client' => $client,
                'address' => (string) ($row->address ?? ''),
                'sex' => (string) ($row->sex ?? ''),
                'contact_number' => (string) ($row->contactNum ?? ''),
                'schedule_date' => $scheduleDate,
                'schedule_time' => $scheduleTime,
            ],
        ]);
    }

    public function christeningApplicationForm(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'christening_id' => ['required', 'integer', 'min:1'],
        ]);

        $christeningId = (int) $validated['christening_id'];

        if (! DB::table('christening')->where('christeningId', $christeningId)->exists()) {
            return response()->json(['message' => 'Christening record not found.'], 404);
        }

        $row = $this->mapApplicationRequestToDetailsRow($request);

        try {
            $existing = DB::table('christening_details')
                ->where('christeningId', $christeningId)
                ->orderByDesc('christeningDetailsId')
                ->first();
        } catch (QueryException $e) {
            report($e);

            return response()->json([
                'ok' => false,
                'message' => $this->isCorruptedChristeningDetailsIndex($e)
                    ? 'The christening details index appears corrupted. Please repair the table and try again.'
                    : 'Could not load christening details. Please try again.',
            ], 422);
        }

        if ($existing) {
            $row['updated_at'] = now();
            DB::table('christening_details')
                ->where('christeningDetailsId', $existing->christeningDetailsId)
                ->update($row);
            $detailsId = (int) $existing->christeningDetailsId;
        } else {
            $row['christeningId'] = $christeningId;
            $row['created_at'] = now();
            $row['updated_at'] = now();
            $detailsId = (int) DB::table('christening_details')->insertGetId($row);
        }

        DocumentationApplicationReportWriter::syncChristening(
            $christeningId,
            $row['firstName'] ?? null,
            $row['middleName'] ?? null,
            $row['familyName'] ?? null,
        );

        return response()->json([
            'ok' => true,
            'message' => 'Application saved.',
            'data' => [
                'christening_id' => $christeningId,
                'christening_details_id' => $detailsId,
            ],
        ]);
    }

    public function christeningApplicationDetails(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'christening_id' => ['required', 'integer', 'min:1'],
        ]);

        $christeningId = (int) $validated['christening_id'];

        if (! DB::table('christening')->where('christeningId', $christeningId)->exists()) {
            return response()->json(['message' => 'Christening record not found.'], 404);
        }

        $christening = DB::table('christening')->where('christeningId', $christeningId)->first();

        try {
            $details = DB::table('christening_details')
                ->where('christeningId', $christeningId)
                ->orderByDesc('christeningDetailsId')
                ->first();
        } catch (QueryException $e) {
            report($e);

            return response()->json([
                'ok' => false,
                'message' => $this->isCorruptedChristeningDetailsIndex($e)
                    ? 'The christening details index appears corrupted. Please repair the table and try again.'
                    : 'Could not load christening details. Please try again.',
            ], 422);
        }

        $data = $this->mapChristeningDetailsRowToFormFields($details);

        if ($details === null && $christening !== null) {
            $data['first_name'] = trim((string) ($christening->clientFName ?? ''));
            $data['middle_name'] = trim((string) ($christening->clientMName ?? ''));
            $data['family_name'] = trim((string) ($christening->clientLName ?? ''));
        }

        if ($christening !== null && ! empty($christening->scheduleRequested)) {
            $data['baptism_date'] = $this->dateForForm($christening->scheduleRequested);
        }

        $data['baptism_place'] = self::CHRISTENING_FIXED_BAPTISM_PLACE;

        if ($christening !== null) {
            $guardianContact = trim((string) ($data['guardian_contact'] ?? ''));
            if ($guardianContact === '') {
                $fromRegistry = $this->digitsOnlyForContactSlots((string) ($christening->contactNum ?? ''));
                if ($fromRegistry !== '') {
                    $data['guardian_contact'] = $fromRegistry;
                }
            }
        }

        return response()->json([
            'ok' => true,
            'data' => $data,
        ]);
    }

    private function digitsOnlyForContactSlots(string $raw): string
    {
        $digits = preg_replace('/\D+/', '', $raw) ?? '';

        return $digits === '' ? '' : (strlen($digits) > 11 ? substr($digits, -11) : $digits);
    }


    private function defaultChristeningPaymentFeeRows(): array
    {
        return [
            ['label' => 'Arancel (For Parents if by Appointment)', 'paid' => false, 'date_paid' => null],
            ['label' => 'Baptismal Symbols (White Garment, Candle, etc.)', 'paid' => false, 'date_paid' => null],
            ['label' => 'Godparents', 'paid' => false, 'date_paid' => null],
            ['label' => 'Parent\'s Seminar (if by Appointment)', 'paid' => false, 'date_paid' => null],
            ['label' => 'Others:', 'paid' => false, 'date_paid' => null],
        ];
    }


    private function mapChristeningRowToPaymentFormFields(object $row): array
    {
        $parts = array_filter([
            trim((string) ($row->clientFName ?? '')),
            trim((string) ($row->clientMName ?? '')),
            trim((string) ($row->clientLName ?? '')),
        ], fn ($s) => $s !== '');
        $client = implode(' ', $parts);

        $rawFee = $row->paymentFeeRows ?? null;
        $feeRows = null;
        if ($rawFee !== null && $rawFee !== '') {
            if (is_string($rawFee)) {
                $decoded = json_decode($rawFee, true);
                $feeRows = is_array($decoded) ? $decoded : null;
            } elseif (is_array($rawFee)) {
                $feeRows = $rawFee;
            }
        }
        if (! is_array($feeRows) || $feeRows === []) {
            $feeRows = $this->defaultChristeningPaymentFeeRows();
        } else {
            $feeRows = $this->normalizePaymentFeeRowsFromStorage($feeRows);
        }

        $status = trim((string) ($row->paymentStatus ?? ''));
        if ($status === '') {
            $status = 'Unpaid';
        }

        return [
            'reference_code' => (string) ($row->referenceCode ?? ''),
            'client' => $client,
            'contact_number' => (string) ($row->contactNum ?? ''),
            'address' => (string) ($row->address ?? ''),
            'payment_status' => $status,
            'fee_rows' => $feeRows,
        ];
    }

    private function normalizePaymentFeeRowsFromStorage(array $rows): array
    {
        $out = [];
        foreach ($rows as $r) {
            if (! is_array($r)) {
                continue;
            }
            $label = isset($r['label']) ? trim((string) $r['label']) : '';
            $paid = ! empty($r['paid']);
            $datePaid = $r['date_paid'] ?? null;
            $datePaid = is_string($datePaid) && $datePaid !== '' ? $this->normalizePaymentDatePaid($datePaid) : null;
            if (! $paid) {
                $datePaid = null;
            }
            $out[] = ['label' => $label, 'paid' => $paid, 'date_paid' => $datePaid];
        }

        return $out === [] ? $this->defaultChristeningPaymentFeeRows() : $out;
    }

    private function normalizePaymentDatePaid(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    public function christeningPaymentDetails(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'christening_id' => ['required', 'integer', 'min:1'],
        ]);

        $christeningId = (int) $validated['christening_id'];

        $row = DB::table('christening')->where('christeningId', $christeningId)->first();
        if ($row === null) {
            return response()->json(['message' => 'Christening record not found.'], 404);
        }

        return response()->json([
            'ok' => true,
            'data' => $this->mapChristeningRowToPaymentFormFields($row),
        ]);
    }

    public function christeningPaymentSave(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'christening_id' => ['required', 'integer', 'min:1'],
            'client' => ['nullable', 'string', 'max:255'],
            'contact_number' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'fee_rows' => ['nullable', 'array'],
            'fee_rows.*.label' => ['nullable', 'string', 'max:500'],
            'fee_rows.*.paid' => ['nullable', 'boolean'],
            'fee_rows.*.date_paid' => ['nullable', 'string', 'max:50'],
        ]);

        $christeningId = (int) $validated['christening_id'];

        $existing = DB::table('christening')->where('christeningId', $christeningId)->first();
        if ($existing === null) {
            return response()->json(['message' => 'Christening record not found.'], 404);
        }

        $feeRows = $validated['fee_rows'] ?? [];
        if (! is_array($feeRows)) {
            $feeRows = [];
        }
        $normalized = [];
        foreach ($feeRows as $r) {
            if (! is_array($r)) {
                continue;
            }
            $label = isset($r['label']) ? trim((string) $r['label']) : '';
            $paid = ! empty($r['paid']);
            $datePaid = $r['date_paid'] ?? null;
            $datePaid = is_string($datePaid) && $datePaid !== '' ? $this->normalizePaymentDatePaid($datePaid) : null;
            if (! $paid) {
                $datePaid = null;
            }
            $normalized[] = ['label' => $label, 'paid' => $paid, 'date_paid' => $datePaid];
        }
        if ($normalized === []) {
            $normalized = $this->defaultChristeningPaymentFeeRows();
        }

        $allPaid = true;
        foreach ($normalized as $line) {
            if (! $line['paid']) {
                $allPaid = false;
                break;
            }
        }
        $paymentStatus = $allPaid ? 'Paid' : 'Unpaid';

        $clientTrim = trim((string) ($validated['client'] ?? ''));
        $parts = preg_split('/\s+/', $clientTrim) ?: [];
        $first = $parts[0] ?? null;
        $last = count($parts) > 1 ? array_pop($parts) : null;
        $middle = count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : null;

        $update = [
            'paymentStatus' => $paymentStatus,
            'contactNum' => $this->nullableText($validated['contact_number'] ?? null),
            'address' => $this->nullableText($validated['address'] ?? null),
        ];
        if ($clientTrim !== '') {
            if ($first) {
                $update['clientFName'] = $first;
            }
            if ($middle !== null) {
                $update['clientMName'] = $middle;
            }
            if ($last) {
                $update['clientLName'] = $last;
            }
        }

        $encoded = json_encode($normalized, JSON_UNESCAPED_UNICODE);
        if ($encoded === false) {
            return response()->json([
                'ok' => false,
                'message' => 'Could not encode fee rows.',
            ], 422);
        }
        $update['paymentFeeRows'] = $encoded;

        try {
            DB::table('christening')->where('christeningId', $christeningId)->update($update);
        } catch (QueryException $e) {
            report($e);

            return response()->json([
                'ok' => false,
                'message' => 'Could not save payment details. If this persists, run database migrations and try again.',
            ], 422);
        }

        return response()->json([
            'ok' => true,
            'message' => 'Payment record saved.',
            'data' => [
                'christening_id' => $christeningId,
                'payment_status' => $paymentStatus,
            ],
        ]);
    }

    public function deleteChristeningRecord(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'christening_id' => ['required', 'integer', 'min:1'],
        ]);

        $christeningId = (int) $validated['christening_id'];

        $row = DB::table('christening')->where('christeningId', $christeningId)->first();
        if ($row === null) {
            return response()->json([
                'ok' => false,
                'message' => 'Christening record not found.',
            ], 404);
        }

        try {
            DB::transaction(function () use ($christeningId) {
                app(DashboardController::class)->deleteChristeningRegistryRow($christeningId);
            });
        } catch (QueryException $e) {
            report($e);

            return response()->json([
                'ok' => false,
                'message' => 'Could not delete this christening record. If this persists, run database migrations and try again.',
            ], 422);
        }

        return response()->json([
            'ok' => true,
            'message' => 'Christening record deleted.',
        ]);
    }

    public function christeningReservedDates(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        $year = (int) $validated['year'];
        $month = (int) $validated['month'];

        $start = Carbon::create($year, $month, 1)->startOfDay();
        $end = (clone $start)->endOfMonth()->endOfDay();

        $dates = DB::table('christening')
            ->whereNotNull('scheduleRequested')
            ->whereBetween('scheduleRequested', [$start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')])
            ->selectRaw('DATE(scheduleRequested) as d')
            ->distinct()
            ->orderBy('d')
            ->pluck('d')
            ->map(fn ($d) => Carbon::parse($d)->format('Y-m-d'))
            ->values()
            ->all();

        return response()->json([
            'ok' => true,
            'dates' => $dates,
        ]);
    }

    private function mapChristeningDetailsRowToFormFields(?object $row): array
    {
        $out = [
            'first_name' => '',
            'middle_name' => '',
            'family_name' => '',
            'date_of_birth' => '',
            'registry_number' => '',
            'place_of_birth' => '',
            'father_name' => '',
            'mother_maiden_name' => '',
            'parent_address' => '',
            'parent_status' => [],
            'marriage_date_1' => '',
            'marriage_place_1' => '',
            'marriage_date_2' => '',
            'marriage_place_2' => '',
            'marriage_date_3' => '',
            'marriage_place_3' => '',
            'marriage_contract_no' => '',
            'guardian_contact' => '',
            'baptism_date' => '',
            'baptism_place' => self::CHRISTENING_FIXED_BAPTISM_PLACE,
            'minister' => '',
            'fee_arancel' => '',
            'fee_symbols' => '',
            'fee_godparents' => '',
            'fee_seminar' => '',
            'fee_others' => '',
            'fee_total' => '',
            'approval_bpc_chairman' => '',
            'approval_prejordan_instructor' => '',
            'approval_parish_secretary' => '',
            'approval_parish_priest' => '',
        ];

        for ($g = 1; $g <= self::CHRISTENING_GODPARENT_FORM_ROWS; $g++) {
            $out['godparent_'.$g.'a'] = '';
            $out['godparent_'.$g.'b'] = '';
        }

        if ($row === null) {
            $out['baptism_place'] = self::CHRISTENING_FIXED_BAPTISM_PLACE;

            return $out;
        }

        $out['first_name'] = (string) ($row->firstName ?? '');
        $out['middle_name'] = (string) ($row->middleName ?? '');
        $out['family_name'] = (string) ($row->familyName ?? '');
        $out['date_of_birth'] = $this->dateForForm($row->dateOfBirth ?? null);
        $out['registry_number'] = (string) ($row->birthRegistryNumber ?? '');
        $out['place_of_birth'] = (string) ($row->placeOfBirth ?? '');
        $out['father_name'] = (string) ($row->fatherName ?? '');
        $out['mother_maiden_name'] = (string) ($row->motherMaidenName ?? '');
        $out['parent_address'] = (string) ($row->parentAddress ?? '');
        $out['marriage_date_1'] = $this->dateForForm($row->civillyMarriedDate ?? null);
        $out['marriage_place_1'] = (string) ($row->civillyMarriedPlace ?? '');
        $out['marriage_date_2'] = $this->dateForForm($row->marriedOtherDenominationDate ?? null);
        $out['marriage_place_2'] = (string) ($row->marriedOtherDenominationPlace ?? '');
        $out['marriage_date_3'] = $this->dateForForm($row->churchMarriageDate ?? null);
        $out['marriage_place_3'] = (string) ($row->churchMarriagePlace ?? '');
        $out['marriage_contract_no'] = (string) ($row->marriageContractNumber ?? '');
        $out['guardian_contact'] = (string) ($row->parentGuardianContact ?? '');
        $out['baptism_date'] = $this->dateForForm($row->dateOfBaptism ?? null);
        $out['baptism_place'] = self::CHRISTENING_FIXED_BAPTISM_PLACE;
        $out['minister'] = (string) ($row->ministerOfSacrament ?? '');

        $out['fee_arancel'] = $this->decimalForForm($row->feeArancel ?? null);
        $out['fee_symbols'] = $this->decimalForForm($row->feeBaptismalSymbols ?? null);
        $out['fee_godparents'] = $this->decimalForForm($row->feeGodparents ?? null);
        $out['fee_seminar'] = $this->decimalForForm($row->feeParentsSeminar ?? null);
        $out['fee_others'] = $this->decimalForForm($row->feeOthers ?? null);
        $out['fee_total'] = $this->decimalForForm($row->feeTotal ?? null);
        $out['approval_bpc_chairman'] = (string) ($row->approvedByBpcChairman ?? '');
        $out['approval_prejordan_instructor'] = (string) ($row->approvedByPreJordanInstructor ?? '');
        $out['approval_parish_secretary'] = (string) ($row->approvedByParishSecretary ?? '');
        $out['approval_parish_priest'] = (string) ($row->approvedByParishPriest ?? '');

        if (! empty($row->parentStatus)) {
            foreach (preg_split('/\s*,\s*/', (string) $row->parentStatus) as $p) {
                $p = trim($p);
                if ($p !== '') {
                    $out['parent_status'][] = $p;
                }
            }
        }

        if (! empty($row->godparents)) {
            $decoded = json_decode((string) $row->godparents, true);
            if (is_array($decoded)) {
                $i = 1;
                foreach ($decoded as $pair) {
                    if ($i > self::CHRISTENING_GODPARENT_FORM_ROWS) {
                        break;
                    }
                    if (! is_array($pair)) {
                        continue;
                    }
                    $out['godparent_'.$i.'a'] = (string) ($pair['maninoy'] ?? $pair[0] ?? '');
                    $out['godparent_'.$i.'b'] = (string) ($pair['maninay'] ?? $pair[1] ?? '');
                    $i++;
                }
            }
        }

        return $out;
    }

    private function dateForForm(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return trim((string) $value);
        }
    }

    private function decimalForForm(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        if (! is_numeric($value)) {
            return trim((string) $value);
        }

        return number_format((float) $value, 2, '.', '');
    }

    private function mapApplicationRequestToDetailsRow(Request $request): array
    {
        $parentStatus = $request->input('parent_status');
        if (is_string($parentStatus)) {
            $parentStatus = [$parentStatus];
        }
        if (! is_array($parentStatus)) {
            $parentStatus = [];
        }
        $parentStatusText = implode(', ', array_filter(array_map('strval', $parentStatus)));

        $godparents = [];
        for ($i = 1; $i <= self::CHRISTENING_GODPARENT_FORM_ROWS; $i++) {
            $a = $request->input("godparent_{$i}a");
            $b = $request->input("godparent_{$i}b");
            $a = is_string($a) ? trim($a) : '';
            $b = is_string($b) ? trim($b) : '';
            if ($a !== '' || $b !== '') {
                $godparents[] = ['maninoy' => $a, 'maninay' => $b];
            }
        }

        $dob = $this->parseFlexibleDate($request->input('date_of_birth'));
        $age = null;
        if ($dob !== null) {
            try {
                $age = max(0, min(255, Carbon::parse($dob)->age));
            } catch (\Throwable) {
                $age = null;
            }
        }

        return [
            'firstName' => $this->nullableText($request->input('first_name')),
            'middleName' => $this->nullableText($request->input('middle_name')),
            'familyName' => $this->nullableText($request->input('family_name')),
            'dateOfBirth' => $dob,
            'birthRegistryNumber' => $this->nullableText($request->input('registry_number')),
            'placeOfBirth' => $this->nullableText($request->input('place_of_birth')),
            'fatherName' => $this->nullableText($request->input('father_name')),
            'motherMaidenName' => $this->nullableText($request->input('mother_maiden_name')),
            'parentAddress' => $this->nullableText($request->input('parent_address')),
            'parentStatus' => $parentStatusText !== '' ? $parentStatusText : null,
            'civillyMarriedDate' => $this->parseFlexibleDate($request->input('marriage_date_1')),
            'civillyMarriedPlace' => $this->nullableText($request->input('marriage_place_1')),
            'marriedOtherDenominationDate' => $this->parseFlexibleDate($request->input('marriage_date_2')),
            'marriedOtherDenominationPlace' => $this->nullableText($request->input('marriage_place_2')),
            'churchMarriageDate' => $this->parseFlexibleDate($request->input('marriage_date_3')),
            'churchMarriagePlace' => $this->nullableText($request->input('marriage_place_3')),
            'marriageContractNumber' => $this->nullableText($request->input('marriage_contract_no')),
            'parentGuardianContact' => $this->nullableText($request->input('guardian_contact')),
            'dateOfBaptism' => $this->parseFlexibleDate($request->input('baptism_date')),
            'placeOfBaptism' => self::CHRISTENING_FIXED_BAPTISM_PLACE,
            'ministerOfSacrament' => $this->nullableText($request->input('minister')),
            'age' => $age,
            'feeArancel' => $this->nullableDecimal($request->input('fee_arancel')),
            'feeBaptismalSymbols' => $this->nullableDecimal($request->input('fee_symbols')),
            'feeGodparents' => $this->nullableDecimal($request->input('fee_godparents')),
            'feeParentsSeminar' => $this->nullableDecimal($request->input('fee_seminar')),
            'feeOthers' => $this->nullableDecimal($request->input('fee_others')),
            'feeTotal' => $this->nullableDecimal($request->input('fee_total')),
            'godparents' => count($godparents) ? json_encode($godparents, JSON_UNESCAPED_UNICODE) : null,
            'approvedByBpcChairman' => $this->nullableText($request->input('approval_bpc_chairman')),
            'approvedByPreJordanInstructor' => $this->nullableText($request->input('approval_prejordan_instructor')),
            'approvedByParishSecretary' => $this->nullableText($request->input('approval_parish_secretary')),
            'approvedByParishPriest' => $this->nullableText($request->input('approval_parish_priest')),
        ];
    }

    private function nullableText(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $s = trim((string) $value);

        return $s === '' ? null : $s;
    }

    private function nullableDecimal(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_numeric($value)) {
            return number_format((float) $value, 2, '.', '');
        }
        $clean = preg_replace('/[^\d.\-]/', '', (string) $value);
        if ($clean === '' || ! is_numeric($clean)) {
            return null;
        }

        return number_format((float) $clean, 2, '.', '');
    }

    private function parseFlexibleDate(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $s = trim((string) $value);
        if ($s === '') {
            return null;
        }
        try {
            return Carbon::parse($s)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    public function christeningCertificationForm(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'christening_id' => ['required', 'integer', 'min:1'],
            'reference_code' => ['nullable', 'string', 'max:255'],
            'client' => ['nullable', 'string', 'max:255'],
            'contact_number' => ['nullable', 'string', 'max:50'],
            'top_address' => ['nullable', 'string', 'max:500'],
            'child_first_name' => ['nullable', 'string', 'max:255'],
            'child_middle_name' => ['nullable', 'string', 'max:255'],
            'child_last_name' => ['nullable', 'string', 'max:255'],
            'birthday' => ['nullable', 'date'],
            'birthplace' => ['nullable', 'string', 'max:500'],
            'father_first_name' => ['nullable', 'string', 'max:255'],
            'father_middle_name' => ['nullable', 'string', 'max:255'],
            'father_last_name' => ['nullable', 'string', 'max:255'],
            'mother_first_name' => ['nullable', 'string', 'max:255'],
            'mother_middle_name' => ['nullable', 'string', 'max:255'],
            'mother_last_name' => ['nullable', 'string', 'max:255'],
            'barangay' => ['nullable', 'string', 'max:255'],
            'municipality' => ['nullable', 'string', 'max:255'],
            'province' => ['nullable', 'string', 'max:255'],
            'date_received' => ['nullable', 'date'],
            'priest' => ['nullable', 'string', 'max:500'],
            'sponsors' => ['nullable', 'string', 'max:2000'],
            'purpose' => ['nullable', 'string', 'max:2000'],
            'book_no' => ['nullable', 'string', 'max:120'],
            'register_no' => ['nullable', 'string', 'max:120'],
            'page_no' => ['nullable', 'string', 'max:120'],
            'date_issued' => ['nullable', 'date'],
        ]);

        $christeningId = (int) $validated['christening_id'];

        $christening = DB::table('christening')->where('christeningId', $christeningId)->first();
        if ($christening === null) {
            return response()->json(['message' => 'Christening record not found.'], 404);
        }

        $certRow = $this->mapCertificationRequestToCertificationTableRow($request);
        $certificationDetailsRow = $this->mapCertificationRequestToCertificationDetailsRow($request, $christening);

        try {
            DB::transaction(function () use ($christeningId, $certRow, $certificationDetailsRow) {
                $existing = DB::table('christening_certification')->where('christeningId', $christeningId)->first();

                if ($existing) {
                    DB::table('christening_certification')
                        ->where('christeningCertificationId', $existing->christeningCertificationId)
                        ->update($certRow);
                } else {
                    $insert = array_merge($certRow, [
                        'christeningId' => $christeningId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    DB::table('christening_certification')->insert($insert);
                }

                DB::table('certification_details')->insert($certificationDetailsRow);
            });
        } catch (QueryException $e) {
            report($e);

            return response()->json([
                'ok' => false,
                'message' => 'Could not save certification. If this persists, run database migrations and try again.',
            ], 422);
        }

        return response()->json([
            'ok' => true,
            'message' => 'Certification record saved.',
            'data' => [
                'christening_id' => $christeningId,
            ],
        ]);
    }

    public function christeningCertificationDetails(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'christening_id' => ['required', 'integer', 'min:1'],
        ]);

        $christeningId = (int) $validated['christening_id'];

        if (! DB::table('christening')->where('christeningId', $christeningId)->exists()) {
            return response()->json(['message' => 'Christening record not found.'], 404);
        }

        $christening = DB::table('christening')->where('christeningId', $christeningId)->first();

        try {
            $details = DB::table('christening_details')
                ->where('christeningId', $christeningId)
                ->orderByDesc('christeningDetailsId')
                ->first();
        } catch (QueryException $e) {
            report($e);

            return response()->json([
                'ok' => false,
                'message' => $this->isCorruptedChristeningDetailsIndex($e)
                    ? 'The christening details index appears corrupted. Please repair the table and try again.'
                    : 'Could not load christening details. Please try again.',
            ], 422);
        }

        $overlay = $this->mapChristeningDetailsRowToCertificationOverlay($details);

        if ($details === null && $christening !== null) {
            $overlay['first_name'] = trim((string) ($christening->clientFName ?? ''));
            $overlay['middle_name'] = trim((string) ($christening->clientMName ?? ''));
            $overlay['family_name'] = trim((string) ($christening->clientLName ?? ''));
        }

        $certRow = DB::table('christening_certification')->where('christeningId', $christeningId)->first();
        $certFields = $this->mapChristeningCertificationRowToApplicationStyleFields($certRow);
        foreach ($certFields as $k => $v) {
            if ($v === null || $v === '') {
                continue;
            }
            $overlay[$k] = $v;
        }

        return response()->json([
            'ok' => true,
            'has_saved_cert' => $certRow !== null,
            'data' => $overlay,
        ]);
    }

    private function isCorruptedChristeningDetailsIndex(QueryException $e): bool
    {
        $errorInfo = $e->errorInfo;
        $vendorCode = isset($errorInfo[1]) ? (int) $errorInfo[1] : null;
        $message = strtolower($e->getMessage());

        return $vendorCode === 1712
            && str_contains($message, 'christening_details')
            && str_contains($message, 'corrupt');
    }

    /**
     * @return array<string, string>
     */
    private function mapChristeningDetailsRowToCertificationOverlay(?object $details): array
    {
        $app = $this->mapChristeningDetailsRowToFormFields($details);
        $sponsors = '';
        if ($details !== null && ! empty($details->godparents)) {
            $sponsors = $this->christeningGodparentsJsonToSponsorsString($details->godparents);
        }

        return [
            'first_name' => (string) ($app['first_name'] ?? ''),
            'middle_name' => (string) ($app['middle_name'] ?? ''),
            'family_name' => (string) ($app['family_name'] ?? ''),
            'date_of_birth' => (string) ($app['date_of_birth'] ?? ''),
            'place_of_birth' => (string) ($app['place_of_birth'] ?? ''),
            'father_first_name' => '',
            'father_middle_name' => '',
            'father_last_name' => '',
            'father_name' => (string) ($app['father_name'] ?? ''),
            'mother_first_name' => '',
            'mother_middle_name' => '',
            'mother_last_name' => '',
            'mother_maiden_name' => (string) ($app['mother_maiden_name'] ?? ''),
            'minister' => (string) ($app['minister'] ?? ''),
            'barangay' => '',
            'municipality' => '',
            'province' => '',
            'parent_address' => (string) ($app['parent_address'] ?? ''),
            'date_received' => (string) ($app['baptism_date'] ?? ''),
            'date_issued' => '',
            'book_no' => '',
            'register_no' => '',
            'page_no' => '',
            'sponsors' => $sponsors,
            'purpose' => '',
        ];
    }

    private function christeningGodparentsJsonToSponsorsString(mixed $godparents): string
    {
        if ($godparents === null || $godparents === '') {
            return '';
        }
        $decoded = is_string($godparents) ? json_decode($godparents, true) : (is_array($godparents) ? $godparents : null);
        if (! is_array($decoded)) {
            return '';
        }
        $parts = [];
        foreach ($decoded as $pair) {
            if (! is_array($pair)) {
                continue;
            }
            $a = trim((string) ($pair['maninoy'] ?? $pair[0] ?? ''));
            $b = trim((string) ($pair['maninay'] ?? $pair[1] ?? ''));
            $line = $a;
            if ($a !== '' && $b !== '') {
                $line .= '; '.$b;
            } elseif ($b !== '') {
                $line = $b;
            }
            if ($line !== '') {
                $parts[] = $line;
            }
        }

        return implode('; ', $parts);
    }

    private function mapChristeningCertificationRowToApplicationStyleFields(?object $row): array
    {
        if ($row === null) {
            return [];
        }

        return [
            'first_name' => (string) ($row->childFirstName ?? ''),
            'middle_name' => (string) ($row->childMiddleName ?? ''),
            'family_name' => (string) ($row->childFamilyName ?? ''),
            'date_of_birth' => $this->dateForForm($row->dateOfBirth ?? null),
            'place_of_birth' => (string) ($row->placeOfBirth ?? ''),
            'father_first_name' => (string) ($row->fatherFirstName ?? ''),
            'father_middle_name' => (string) ($row->fatherMiddleName ?? ''),
            'father_last_name' => (string) ($row->fatherLastName ?? ''),
            'father_name' => '',
            'mother_first_name' => (string) ($row->motherFirstName ?? ''),
            'mother_middle_name' => (string) ($row->motherMiddleName ?? ''),
            'mother_last_name' => (string) ($row->motherLastName ?? ''),
            'mother_maiden_name' => '',
            'minister' => (string) ($row->priest ?? ''),
            'barangay' => (string) ($row->addressBarangay ?? ''),
            'municipality' => (string) ($row->addressMunicipality ?? ''),
            'province' => (string) ($row->addressProvince ?? ''),
            'parent_address' => '',
            'date_received' => $this->dateForForm($row->certDateReceived ?? null),
            'date_issued' => $this->dateForForm($row->certDateIssued ?? null),
            'book_no' => (string) ($row->certBookNo ?? ''),
            'register_no' => (string) ($row->certRegisterNo ?? ''),
            'page_no' => (string) ($row->certPageNo ?? ''),
            'sponsors' => (string) ($row->certSponsors ?? ''),
            'purpose' => (string) ($row->certPurpose ?? ''),
        ];
    }

    /**
     * Persisted row for `christening_certification` (one row per christening record).
     *
     * @return array<string, mixed>
     */
    private function mapCertificationRequestToCertificationTableRow(Request $request): array
    {
        $dob = $this->parseFlexibleDate($request->input('birthday'));
        $dateReceived = $this->parseFlexibleDate($request->input('date_received'));
        $dateIssued = $this->parseFlexibleDate($request->input('date_issued'));

        return [
            'childFirstName' => $this->nullableText($request->input('child_first_name')),
            'childMiddleName' => $this->nullableText($request->input('child_middle_name')),
            'childFamilyName' => $this->nullableText($request->input('child_last_name')),
            'dateOfBirth' => $dob,
            'placeOfBirth' => $this->nullableText($request->input('birthplace')),
            'fatherFirstName' => $this->nullableText($request->input('father_first_name')),
            'fatherMiddleName' => $this->nullableText($request->input('father_middle_name')),
            'fatherLastName' => $this->nullableText($request->input('father_last_name')),
            'motherFirstName' => $this->nullableText($request->input('mother_first_name')),
            'motherMiddleName' => $this->nullableText($request->input('mother_middle_name')),
            'motherLastName' => $this->nullableText($request->input('mother_last_name')),
            'addressBarangay' => $this->nullableText($request->input('barangay')),
            'addressMunicipality' => $this->nullableText($request->input('municipality')),
            'addressProvince' => $this->nullableText($request->input('province')),
            'certDateReceived' => $dateReceived,
            'certDateIssued' => $dateIssued,
            'priest' => $this->nullableText($request->input('priest')),
            'certSponsors' => $this->nullableText($request->input('sponsors')),
            'certPurpose' => $this->nullableText($request->input('purpose')),
            'certBookNo' => $this->nullableText($request->input('book_no')),
            'certRegisterNo' => $this->nullableText($request->input('register_no')),
            'certPageNo' => $this->nullableText($request->input('page_no')),
            'updated_at' => now(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mapCertificationRequestToCertificationDetailsRow(Request $request, object $christening): array
    {
        $resolvedReferenceCode = trim((string) ($request->input('reference_code') ?? ''));
        if ($resolvedReferenceCode === '') {
            $resolvedReferenceCode = trim((string) ($christening->referenceCode ?? ''));
        }

        $resolvedClient = trim((string) ($request->input('client') ?? ''));
        if ($resolvedClient === '') {
            $resolvedClient = trim(implode(' ', array_filter([
                trim((string) ($christening->clientFName ?? '')),
                trim((string) ($christening->clientMName ?? '')),
                trim((string) ($christening->clientLName ?? '')),
            ], fn ($part) => $part !== '')));
        }

        $resolvedAddress = trim((string) ($request->input('top_address') ?? ''));
        if ($resolvedAddress === '') {
            $resolvedAddress = trim((string) ($christening->address ?? ''));
        }

        $resolvedSex = trim((string) ($christening->sex ?? ''));
        $resolvedContact = trim((string) ($request->input('contact_number') ?? ''));
        if ($resolvedContact === '') {
            $resolvedContact = trim((string) ($christening->contactNum ?? ''));
        }

        $rawDate = $request->input('date_issued');
        $resolvedDate = $this->parseFlexibleDate($rawDate);
        if ($resolvedDate === null) {
            $resolvedDate = now()->format('Y-m-d');
        }

        return [
            'referenceCode' => $this->nullableText($resolvedReferenceCode),
            'client' => $this->nullableText($resolvedClient),
            'address' => $this->nullableText($resolvedAddress),
            'sex' => $this->nullableText($resolvedSex),
            'contactNumber' => $this->nullableText($resolvedContact),
            'date' => $resolvedDate,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

}
