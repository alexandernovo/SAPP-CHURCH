<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BurialController extends Controller
{
    private const BURIAL_REFERENCE_SUFFIX = 'D';

    private const BURIAL_REFERENCE_CHARSET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    public function index(Request $request): View
    {
        $request->merge(['registry_type' => 'burial']);

        $dashboard = app(DashboardController::class);
        $data = $dashboard->registryIndexData($request);

        return view('burial.views.burial', [
            'records' => $data['records'],
            'initialTablePayload' => $data['initialTablePayload'],
            'perPageOptions' => DashboardController::perPageOptionsList(),
            'letterOptions' => range('A', 'Z'),
            'generatedReferenceCode' => $this->generateUniqueBurialReferenceCode(),
        ]);
    }

    private function generateUniqueBurialReferenceCode(): string
    {
        $year = (int) date('Y');

        for ($attempt = 0; $attempt < 50; $attempt++) {
            $code = $this->formatBurialReferenceCode($year, $this->randomBurialReferenceCodeSegment(7));
            if (! DB::table('burial')->where('referenceCode', $code)->exists()) {
                return $code;
            }
        }

        throw new \RuntimeException('Could not generate a unique burial reference code.');
    }

    private function formatBurialReferenceCode(int $year, string $middle): string
    {
        return $year.'-'.$middle.'-'.self::BURIAL_REFERENCE_SUFFIX;
    }

    private function randomBurialReferenceCodeSegment(int $length): string
    {
        $max = strlen(self::BURIAL_REFERENCE_CHARSET) - 1;
        $out = '';
        for ($i = 0; $i < $length; $i++) {
            $out .= self::BURIAL_REFERENCE_CHARSET[random_int(0, $max)];
        }

        return $out;
    }

    public function scheduleBurial(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'burial_id' => ['nullable', 'integer'],
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
            $ref = $this->generateUniqueBurialReferenceCode();
        }

        $query = DB::table('burial');
        if (! empty($validated['burial_id'])) {
            $query->where('burialId', $validated['burial_id']);
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

                    return (int) DB::table('burial')->insertGetId($insertData);
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
                'message' => 'Burial schedule created successfully.',
                'created' => true,
                'data' => [
                    'burial_id' => $newId,
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
            'message' => 'Burial schedule updated successfully.',
            'created' => false,
            'data' => [
                'burial_id' => $existing->burialId ?? null,
                'reference_code' => $existing->referenceCode ?? $ref,
                'schedule_requested' => $scheduleAt,
            ],
        ]);
    }

    public function burialReservedDates(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        $year = (int) $validated['year'];
        $month = (int) $validated['month'];

        $dates = DB::table('burial')
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

    public function burialPaymentDetails(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'burial_id' => ['required', 'integer', 'min:1'],
        ]);

        $burialId = (int) $validated['burial_id'];

        $row = DB::table('burial')->where('burialId', $burialId)->first();
        if ($row === null) {
            return response()->json(['message' => 'Burial record not found.'], 404);
        }

        return response()->json([
            'ok' => true,
            'data' => $this->mapBurialRowToPaymentFormFields($row),
        ]);
    }

    public function burialPaymentSave(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'burial_id' => ['required', 'integer', 'min:1'],
            'client' => ['nullable', 'string', 'max:255'],
            'contact_number' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'fee_rows' => ['nullable', 'array'],
            'fee_rows.*.label' => ['nullable', 'string', 'max:500'],
            'fee_rows.*.paid' => ['nullable', 'boolean'],
            'fee_rows.*.date_paid' => ['nullable', 'string', 'max:50'],
        ]);

        $burialId = (int) $validated['burial_id'];

        $existing = DB::table('burial')->where('burialId', $burialId)->first();
        if ($existing === null) {
            return response()->json(['message' => 'Burial record not found.'], 404);
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
            $normalized = $this->defaultBurialPaymentFeeRows();
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
            DB::table('burial')->where('burialId', $burialId)->update($update);
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
                'burial_id' => $burialId,
                'payment_status' => $paymentStatus,
            ],
        ]);
    }

    private function defaultBurialPaymentFeeRows(): array
    {
        return [
            ['label' => 'Chapel / cemetery (Arancel)', 'paid' => false, 'date_paid' => null],
            ['label' => 'Opening / digging', 'paid' => false, 'date_paid' => null],
            ['label' => 'Vault / urn', 'paid' => false, 'date_paid' => null],
            ['label' => 'Mass / memorial offering', 'paid' => false, 'date_paid' => null],
            ['label' => 'Others:', 'paid' => false, 'date_paid' => null],
        ];
    }

    private function mapBurialRowToPaymentFormFields(object $row): array
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
            $feeRows = $this->defaultBurialPaymentFeeRows();
        } else {
            $feeRows = $this->normalizeBurialPaymentFeeRowsFromStorage($feeRows);
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

    private function normalizeBurialPaymentFeeRowsFromStorage(array $rows): array
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

        return $out === [] ? $this->defaultBurialPaymentFeeRows() : $out;
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
