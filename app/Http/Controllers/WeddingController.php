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

class WeddingController extends Controller
{
    private const WEDDING_REFERENCE_SUFFIX = 'W';

    private const WEDDING_REFERENCE_CHARSET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    public function index(Request $request): View
    {
        $request->merge(['registry_type' => 'wedding']);

        $dashboard = app(DashboardController::class);
        $data = $dashboard->registryIndexData($request);

        return view('wedding.view.wedding', [
            'records' => $data['records'],
            'initialTablePayload' => $data['initialTablePayload'],
            'perPageOptions' => DashboardController::perPageOptionsList(),
            'letterOptions' => range('A', 'Z'),
            'generatedReferenceCode' => $this->generateUniqueWeddingReferenceCode(),
        ]);
    }

    private function generateUniqueWeddingReferenceCode(): string
    {
        $year = (int) date('Y');

        for ($attempt = 0; $attempt < 50; $attempt++) {
            $code = $this->formatWeddingReferenceCode($year, $this->randomWeddingReferenceCodeSegment(7));
            if (! DB::table('wedding')->where('referenceCode', $code)->exists()) {
                return $code;
            }
        }

        throw new \RuntimeException('Could not generate a unique wedding reference code.');
    }

    private function formatWeddingReferenceCode(int $year, string $middle): string
    {
        return $year.'-'.$middle.'-'.self::WEDDING_REFERENCE_SUFFIX;
    }

    private function randomWeddingReferenceCodeSegment(int $length): string
    {
        $max = strlen(self::WEDDING_REFERENCE_CHARSET) - 1;
        $out = '';
        for ($i = 0; $i < $length; $i++) {
            $out .= self::WEDDING_REFERENCE_CHARSET[random_int(0, $max)];
        }

        return $out;
    }

    public function scheduleWedding(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'wedding_id' => ['nullable', 'integer'],
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
            $ref = $this->generateUniqueWeddingReferenceCode();
        }

        $query = DB::table('wedding');
        if (! empty($validated['wedding_id'])) {
            $query->where('weddingId', $validated['wedding_id']);
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

                    return (int) DB::table('wedding')->insertGetId($insertData);
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
                'message' => 'Wedding schedule created successfully.',
                'created' => true,
                'data' => [
                    'wedding_id' => $newId,
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
            'message' => 'Wedding schedule updated successfully.',
            'created' => false,
            'data' => [
                'wedding_id' => $existing->weddingId ?? null,
                'reference_code' => $existing->referenceCode ?? $ref,
                'schedule_requested' => $scheduleAt,
            ],
        ]);
    }

    public function weddingScheduleDetails(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'wedding_id' => ['required', 'integer', 'min:1'],
        ]);
        $weddingId = (int) $validated['wedding_id'];

        $row = DB::table('wedding')->where('weddingId', $weddingId)->first();
        if ($row === null) {
            return response()->json([
                'ok' => false,
                'message' => 'Wedding record not found.',
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
                'wedding_id' => $weddingId,
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

    public function weddingReservedDates(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        $year = (int) $validated['year'];
        $month = (int) $validated['month'];

        $dates = DB::table('wedding')
            ->whereNotNull('scheduleRequested')
            ->whereYear('scheduleRequested', $year)
            ->whereMonth('scheduleRequested', $month)
            ->selectRaw('DATE(scheduleRequested) as d')
            ->distinct()
            ->pluck('d')
            ->map(fn ($d) => Carbon::parse($d)->format('Y-m-d'))
            ->values()
            ->all();

        return response()->json([
            'ok' => true,
            'dates' => $dates,
        ]);
    }

    public function weddingPaymentDetails(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'wedding_id' => ['required', 'integer', 'min:1'],
        ]);

        $weddingId = (int) $validated['wedding_id'];

        $row = DB::table('wedding')->where('weddingId', $weddingId)->first();
        if ($row === null) {
            return response()->json(['message' => 'Wedding record not found.'], 404);
        }

        return response()->json([
            'ok' => true,
            'data' => $this->mapWeddingRowToPaymentFormFields($row),
        ]);
    }

    public function weddingPaymentSave(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'wedding_id' => ['required', 'integer', 'min:1'],
            'client' => ['nullable', 'string', 'max:255'],
            'contact_number' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'fee_rows' => ['nullable', 'array'],
            'fee_rows.*.label' => ['nullable', 'string', 'max:500'],
            'fee_rows.*.paid' => ['nullable', 'boolean'],
            'fee_rows.*.date_paid' => ['nullable', 'string', 'max:50'],
        ]);

        $weddingId = (int) $validated['wedding_id'];

        $existing = DB::table('wedding')->where('weddingId', $weddingId)->first();
        if ($existing === null) {
            return response()->json(['message' => 'Wedding record not found.'], 404);
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
            $normalized = $this->defaultWeddingPaymentFeeRows();
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
            DB::table('wedding')->where('weddingId', $weddingId)->update($update);
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
                'wedding_id' => $weddingId,
                'payment_status' => $paymentStatus,
            ],
        ]);
    }

    public function weddingMarriageApplicationDetails(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'wedding_id' => ['required', 'integer', 'min:1'],
        ]);
        $weddingId = (int) $validated['wedding_id'];
        $row = DB::table('wedding')->where('weddingId', $weddingId)->first();
        if ($row === null) {
            return response()->json(['message' => 'Wedding record not found.'], 404);
        }

        return response()->json([
            'ok' => true,
            'data' => $this->decodeMarriageApplication($row),
        ]);
    }

    public function weddingMarriageApplicationSave(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'wedding_id' => ['required', 'integer', 'min:1'],
        ]);
        $weddingId = (int) $validated['wedding_id'];
        $existing = DB::table('wedding')->where('weddingId', $weddingId)->first();
        if ($existing === null) {
            return response()->json(['message' => 'Wedding record not found.'], 404);
        }

        $data = $request->json() ? $request->json()->all() : $request->all();
        if (! is_array($data)) {
            $data = [];
        }
        unset($data['wedding_id'], $data['_token']);

        $encoded = json_encode($data, JSON_UNESCAPED_UNICODE);
        if ($encoded === false) {
            return response()->json([
                'ok' => false,
                'message' => 'Could not encode the marriage application data.',
            ], 422);
        }

        try {
            DB::table('wedding')->where('weddingId', $weddingId)->update([
                'marriageApplication' => $encoded,
            ]);
        } catch (QueryException $e) {
            report($e);

            return response()->json([
                'ok' => false,
                'message' => 'Could not save. If the problem persists, run database migrations and try again.',
            ], 422);
        }

        DocumentationApplicationReportWriter::syncWedding($weddingId, $data);

        return response()->json([
            'ok' => true,
            'message' => 'Marriage application saved.',
        ]);
    }

    public function weddingCertificationDetails(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'wedding_id' => ['required', 'integer', 'min:1'],
        ]);
        $weddingId = (int) $validated['wedding_id'];
        $row = DB::table('wedding')->where('weddingId', $weddingId)->first();
        if ($row === null) {
            return response()->json(['message' => 'Wedding record not found.'], 404);
        }

        $details = DB::table('wedding_details')
            ->where('weddingId', $weddingId)
            ->orderByDesc('weddingDetailsId')
            ->first();

        $data = [
            'first_name' => '',
            'middle_name' => '',
            'family_name' => '',
            'date_of_birth' => '',
            'place_of_birth' => '',
            'father_first_name' => '',
            'father_middle_name' => '',
            'father_last_name' => '',
            'mother_first_name' => '',
            'mother_middle_name' => '',
            'mother_last_name' => '',
            'barangay' => '',
            'municipality' => '',
            'province' => 'Antique',
            'date_received' => '',
            'date_issued' => '',
            'book_no' => '',
            'register_no' => '',
            'page_no' => '',
            'priest' => '',
            'sponsors' => '',
            'purpose' => '',
        ];

        if ($details !== null) {
            $groom = $this->splitFullNameThreeParts($details->groomFullName ?? '');
            $data['first_name'] = $groom['first'];
            $data['middle_name'] = $groom['middle'];
            $data['family_name'] = $groom['last'];
            $data['date_of_birth'] = $this->dateForForm($details->groomDateOfBirth ?? null);
            $data['place_of_birth'] = (string) ($details->groomPlaceOfBirth ?? '');

            $father = $this->splitFullNameThreeParts($details->groomFather ?? '');
            $data['father_first_name'] = $father['first'];
            $data['father_middle_name'] = $father['middle'];
            $data['father_last_name'] = $father['last'];

            $mother = $this->splitFullNameThreeParts($details->groomMotherMaiden ?? '');
            $data['mother_first_name'] = $mother['first'];
            $data['mother_middle_name'] = $mother['middle'];
            $data['mother_last_name'] = $mother['last'];

            $addrBits = array_values(array_filter(array_map('trim', explode(',', (string) ($details->groomPresentAddress ?? ''))), fn ($s) => $s !== ''));
            if (isset($addrBits[0])) {
                $data['barangay'] = $addrBits[0];
            }
            if (isset($addrBits[1])) {
                $data['municipality'] = $addrBits[1];
            }
            if (count($addrBits) > 2) {
                $data['province'] = implode(', ', array_slice($addrBits, 2));
            }

            $data['priest'] = (string) ($details->officiatingPriest ?? '');
            $data['sponsors'] = implode('; ', array_values(array_filter([
                trim((string) ($details->sponsorsLine1 ?? '')),
                trim((string) ($details->sponsorsLine2 ?? '')),
                trim((string) ($details->sponsorsLine3 ?? '')),
            ], fn ($s) => $s !== '')));
        } else {
            $data['first_name'] = trim((string) ($row->clientFName ?? ''));
            $data['middle_name'] = trim((string) ($row->clientMName ?? ''));
            $data['family_name'] = trim((string) ($row->clientLName ?? ''));
        }

        return response()->json([
            'ok' => true,
            'data' => $data,
        ]);
    }

    public function deleteWeddingRecord(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'wedding_id' => ['required', 'integer', 'min:1'],
        ]);

        $weddingId = (int) $validated['wedding_id'];

        $row = DB::table('wedding')->where('weddingId', $weddingId)->first();
        if ($row === null) {
            return response()->json([
                'ok' => false,
                'message' => 'Wedding record not found.',
            ], 404);
        }

        try {
            DB::transaction(function () use ($weddingId) {
                app(DashboardController::class)->deleteWeddingRegistryRow($weddingId);
            });
        } catch (QueryException $e) {
            report($e);

            return response()->json([
                'ok' => false,
                'message' => 'Could not delete this wedding record. If this persists, run database migrations and try again.',
            ], 422);
        }

        return response()->json([
            'ok' => true,
            'message' => 'Wedding record deleted.',
        ]);
    }

    private function decodeMarriageApplication(object $row): array
    {
        $raw = $row->marriageApplication ?? null;
        if ($raw === null || $raw === '') {
            return [];
        }
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);

            return is_array($decoded) ? $decoded : [];
        }
        if (is_array($raw)) {
            return $raw;
        }

        return [];
    }

    /**
     * @return array{first:string,middle:string,last:string}
     */
    private function splitFullNameThreeParts(mixed $value): array
    {
        $full = trim((string) ($value ?? ''));
        if ($full === '') {
            return ['first' => '', 'middle' => '', 'last' => ''];
        }
        $parts = preg_split('/\s+/', $full) ?: [];
        if (count($parts) === 1) {
            return ['first' => $parts[0], 'middle' => '', 'last' => ''];
        }
        if (count($parts) === 2) {
            return ['first' => $parts[0], 'middle' => '', 'last' => $parts[1]];
        }

        return [
            'first' => $parts[0],
            'middle' => implode(' ', array_slice($parts, 1, -1)),
            'last' => $parts[count($parts) - 1],
        ];
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

    private function defaultWeddingPaymentFeeRows(): array
    {
        return [
            ['label' => 'Church / venue (Arancel)', 'paid' => false, 'date_paid' => null],
            ['label' => 'Marriage license / permit', 'paid' => false, 'date_paid' => null],
            ['label' => 'Music / choir', 'paid' => false, 'date_paid' => null],
            ['label' => 'Flowers / decorations', 'paid' => false, 'date_paid' => null],
            ['label' => 'Others:', 'paid' => false, 'date_paid' => null],
        ];
    }

    private function mapWeddingRowToPaymentFormFields(object $row): array
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
            $feeRows = $this->defaultWeddingPaymentFeeRows();
        } else {
            $feeRows = $this->normalizeWeddingPaymentFeeRowsFromStorage($feeRows);
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

    private function normalizeWeddingPaymentFeeRowsFromStorage(array $rows): array
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

        return $out === [] ? $this->defaultWeddingPaymentFeeRows() : $out;
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

    private function nullableText(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $s = trim((string) $value);

        return $s === '' ? null : $s;
    }
}
