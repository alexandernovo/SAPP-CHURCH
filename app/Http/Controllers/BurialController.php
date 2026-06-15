<?php

namespace App\Http\Controllers;

use App\Support\ClientNameDisplay;
use App\Support\DocumentationApplicationReportWriter;
use App\Support\SacramentApplicationGate;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class BurialController extends Controller
{
    private const BURIAL_REFERENCE_SUFFIX = 'D';

    private const BURIAL_REFERENCE_CHARSET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    public function index(Request $request)
    {
        return redirect()->route('admin.burial.application', $request->query());
    }

    public function scheduleIndex(Request $request): View
    {
        return view('burial.view.schedule', $this->burialSectionViewData($request));
    }

    public function certificationIndex(Request $request): View
    {
        return view('burial.view.certification', $this->burialSectionViewData($request));
    }

    public function paymentIndex(Request $request): View
    {
        return view('burial.view.payment', $this->burialSectionViewData($request));
    }

    public function applicationIndex(Request $request): View
    {
        return view('burial.view.application-form', $this->burialSectionViewData($request));
    }

    /**
     * @return array<string, mixed>
     */
    private function burialSectionViewData(Request $request): array
    {
        $request->merge(['registry_type' => 'burial']);

        $dashboard = app(DashboardController::class);
        $data = $dashboard->registryIndexData($request);

        return [
            'records' => $data['records'],
            'initialTablePayload' => $data['initialTablePayload'],
            'perPageOptions' => DashboardController::perPageOptionsList(),
            'letterOptions' => range('A', 'Z'),
            'generatedReferenceCode' => $this->generateUniqueBurialReferenceCode(),
        ];
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

        $existingBurialId = (int) $existing->burialId;
        if (! SacramentApplicationGate::burialIsPaymentComplete($existingBurialId)) {
            return SacramentApplicationGate::paymentDenyResponse();
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

    public function burialScheduleDetails(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'burial_id' => ['required', 'integer', 'min:1'],
        ]);
        $burialId = (int) $validated['burial_id'];

        $row = DB::table('burial')->where('burialId', $burialId)->first();
        if ($row === null) {
            return response()->json([
                'ok' => false,
                'message' => 'Burial record not found.',
            ], 404);
        }

        if (! SacramentApplicationGate::burialIsPaymentComplete($burialId)) {
            return SacramentApplicationGate::paymentDenyResponse();
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
                'burial_id' => $burialId,
                'reference_code' => (string) ($row->referenceCode ?? ''),
                'client' => $client,
                'address' => ClientNameDisplay::formatAddress((string) ($row->address ?? '')),
                'sex' => (string) ($row->sex ?? ''),
                'contact_number' => (string) ($row->contactNum ?? ''),
                'schedule_date' => $scheduleDate,
                'schedule_time' => $scheduleTime,
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
            'payment_complete' => SacramentApplicationGate::burialIsPaymentComplete($burialId),
            'data' => $this->mapBurialRowToPaymentFormFields($row),
        ]);
    }

    public function burialPaymentSave(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'burial_id' => ['nullable', 'integer', 'min:1'],
            'reference_code' => ['nullable', 'string', 'max:255'],
            'client' => ['nullable', 'string', 'max:255'],
            'contact_number' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'fee_rows' => ['nullable', 'array'],
            'fee_rows.*.label' => ['nullable', 'string', 'max:500'],
            'fee_rows.*.paid' => ['nullable', 'boolean'],
            'fee_rows.*.date_paid' => ['nullable', 'string', 'max:50'],
        ]);

        $burialId = ! empty($validated['burial_id']) ? (int) $validated['burial_id'] : 0;
        $ref = trim((string) ($validated['reference_code'] ?? ''));
        if ($ref === '' && $burialId <= 0) {
            $ref = $this->generateUniqueBurialReferenceCode();
        }

        $existing = null;
        if ($burialId > 0) {
            $existing = DB::table('burial')->where('burialId', $burialId)->first();
            if ($existing === null) {
                return response()->json(['message' => 'Burial record not found.'], 404);
            }
        } elseif ($ref !== '') {
            $existing = DB::table('burial')->where('referenceCode', $ref)->first();
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
            'address' => ClientNameDisplay::nullableFormattedAddress($validated['address'] ?? null),
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

        if ($existing === null) {
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
                $burialId = DB::transaction(function () use ($ref, $first, $middle, $last, $validated, $update) {
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

                    $insertData = array_merge([
                        'referenceCode' => $ref,
                        'clientFName' => $first ?? '',
                        'clientMName' => $middle,
                        'clientLName' => $last ?? '',
                        'contactNum' => $validated['contact_number'] ?? null,
                        'address' => ClientNameDisplay::nullableFormattedAddress($validated['address'] ?? null),
                        'dateCreated' => now(),
                        'customerId' => $customerId,
                    ], $update);
                    if (Schema::hasColumn('burial', 'workflowStep')) {
                        $insertData['workflowStep'] = 'payment';
                    }
                    $insertData = array_filter($insertData, fn ($v) => $v !== null);

                    return (int) DB::table('burial')->insertGetId($insertData);
                });
            } catch (QueryException $e) {
                report($e);

                return response()->json([
                    'ok' => false,
                    'message' => 'Could not save payment details. If this persists, run database migrations and try again.',
                ], 422);
            }
        } else {
            $burialId = (int) $existing->burialId;
            if ($ref === '') {
                $ref = (string) ($existing->referenceCode ?? '');
            }

            try {
                DB::table('burial')->where('burialId', $burialId)->update($update);
            } catch (QueryException $e) {
                report($e);

                return response()->json([
                    'ok' => false,
                    'message' => 'Could not save payment details. If this persists, run database migrations and try again.',
                ], 422);
            }
        }

        return response()->json([
            'ok' => true,
            'message' => 'Payment record saved.',
            'data' => [
                'burial_id' => $burialId,
                'reference_code' => $ref,
                'payment_status' => $paymentStatus,
            ],
        ]);
    }

    public function burialApplicationDetails(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'burial_id' => ['required', 'integer', 'min:1'],
        ]);
        $burialId = (int) $validated['burial_id'];
        $row = DB::table('burial')->where('burialId', $burialId)->first();
        if ($row === null) {
            return response()->json(['message' => 'Burial record not found.'], 404);
        }

        $details = DB::table('burial_details')
            ->where('burialId', $burialId)
            ->orderByDesc('burialDetailsId')
            ->first();

        return response()->json([
            'ok' => true,
            'application_saved' => SacramentApplicationGate::burialIsSaved($burialId),
            'data' => ClientNameDisplay::normalizeApplicationNameFields($this->mapBurialDetailsRowToApplicationPayload($details)),
        ]);
    }

    public function burialApplicationSave(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'burial_id' => ['nullable', 'integer', 'min:1'],
        ]);
        $burialId = (int) ($validated['burial_id'] ?? 0);

        $data = $request->json() ? $request->json()->all() : $request->all();
        if (! is_array($data)) {
            $data = [];
        }
        unset($data['burial_id'], $data['_token']);
        $data = ClientNameDisplay::normalizeApplicationNameFields($data);

        $deceasedName = trim((string) ($data['deceased_name'] ?? ''));
        if ($deceasedName === '') {
            return response()->json([
                'ok' => false,
                'message' => 'Please enter the deceased name on the burial application form.',
                'errors' => [
                    'deceased_name' => ['Deceased name is required.'],
                ],
            ], 422);
        }

        $created = false;
        $referenceCode = null;

        if ($burialId <= 0) {
            try {
                [$burialId, $referenceCode] = $this->createBurialRegistryFromApplication($data);
                $created = true;
            } catch (QueryException $e) {
                report($e);

                return response()->json([
                    'ok' => false,
                    'message' => 'Could not create the burial record. Please try again.',
                ], 422);
            }
        } elseif (DB::table('burial')->where('burialId', $burialId)->first() === null) {
            return response()->json(['message' => 'Burial record not found.'], 404);
        }

        $detailsRow = $this->mapBurialApplicationPayloadToDetailsRow($data);

        try {
            DB::transaction(function () use ($burialId, $detailsRow, $deceasedName, $data) {
                $nameParts = $this->splitRegistryClientName($deceasedName);
                DB::table('burial')->where('burialId', $burialId)->update(array_filter([
                    'clientFName' => $nameParts['first'] !== '' ? $nameParts['first'] : null,
                    'clientMName' => $nameParts['middle'],
                    'clientLName' => $nameParts['last'] !== '' ? $nameParts['last'] : null,
                    'address' => ClientNameDisplay::nullableFormattedAddress($data['deceased_address'] ?? null),
                ], fn ($v) => $v !== null));
                $existingDetails = DB::table('burial_details')->where('burialId', $burialId)->first();
                if ($existingDetails) {
                    DB::table('burial_details')
                        ->where('burialDetailsId', $existingDetails->burialDetailsId)
                        ->update(array_merge($detailsRow, [
                            'updated_at' => now(),
                        ]));
                } else {
                    DB::table('burial_details')->insert(array_merge($detailsRow, [
                        'burialId' => $burialId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]));
                }
            });
        } catch (QueryException $e) {
            report($e);

            return response()->json([
                'ok' => false,
                'message' => 'Could not save. If the problem persists, run database migrations and try again.',
            ], 422);
        }

        DocumentationApplicationReportWriter::syncBurial($burialId, $data);

        return response()->json([
            'ok' => true,
            'message' => 'Burial application saved.',
            'created' => $created,
            'data' => [
                'burial_id' => $burialId,
                'reference_code' => $referenceCode,
            ],
        ]);
    }

    public function burialCertificationDetails(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'burial_id' => ['required', 'integer', 'min:1'],
        ]);
        $burialId = (int) $validated['burial_id'];
        $row = DB::table('burial')->where('burialId', $burialId)->first();
        if ($row === null) {
            return response()->json(['message' => 'Burial record not found.'], 404);
        }

        if (! SacramentApplicationGate::burialIsPaymentComplete($burialId)) {
            return SacramentApplicationGate::paymentDenyResponse();
        }

        $details = DB::table('burial_details')
            ->where('burialId', $burialId)
            ->orderByDesc('burialDetailsId')
            ->first();

        $data = [
            'first_name' => trim((string) ($row->clientFName ?? '')),
            'middle_name' => trim((string) ($row->clientMName ?? '')),
            'family_name' => trim((string) ($row->clientLName ?? '')),
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
            $deceased = $this->splitFullNameThreeParts($details->deceasedName ?? '');
            if ($deceased['first'] !== '' || $deceased['middle'] !== '' || $deceased['last'] !== '') {
                $data['first_name'] = $deceased['first'];
                $data['middle_name'] = $deceased['middle'];
                $data['family_name'] = $deceased['last'];
            }
            $data['date_of_birth'] = $this->dateForForm($details->baptismDate ?? null);
            $data['place_of_birth'] = (string) ($details->claimantPlace ?? '');

            $father = $this->splitFullNameThreeParts($details->minorFatherName ?? '');
            $data['father_first_name'] = $father['first'];
            $data['father_middle_name'] = $father['middle'];
            $data['father_last_name'] = $father['last'];

            $mother = $this->splitFullNameThreeParts($details->minorMotherName ?? '');
            $data['mother_first_name'] = $mother['first'];
            $data['mother_middle_name'] = $mother['middle'];
            $data['mother_last_name'] = $mother['last'];

            $addrBits = array_values(array_filter(array_map('trim', explode(',', (string) ($details->deceasedAddress ?? ''))), fn ($s) => $s !== ''));
            if (isset($addrBits[0])) {
                $data['barangay'] = $addrBits[0];
            }
            if (isset($addrBits[1])) {
                $data['municipality'] = $addrBits[1];
            }
            if (count($addrBits) > 2) {
                $data['province'] = implode(', ', array_slice($addrBits, 2));
            }
        }

        return response()->json([
            'ok' => true,
            'data' => $data,
        ]);
    }

    public function burialCertificationForm(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'burial_id' => ['required', 'integer', 'min:1'],
            'reference_code' => ['nullable', 'string', 'max:255'],
            'client' => ['nullable', 'string', 'max:255'],
            'contact_number' => ['nullable', 'string', 'max:50'],
            'top_address' => ['nullable', 'string', 'max:500'],
            'date_issued' => ['nullable', 'date'],
        ]);

        $burialId = (int) $validated['burial_id'];
        $burial = DB::table('burial')->where('burialId', $burialId)->first();
        if ($burial === null) {
            return response()->json(['message' => 'Burial record not found.'], 404);
        }

        if (! SacramentApplicationGate::burialIsPaymentComplete($burialId)) {
            return SacramentApplicationGate::paymentDenyResponse();
        }

        $resolvedReferenceCode = trim((string) ($validated['reference_code'] ?? ''));
        if ($resolvedReferenceCode === '') {
            $resolvedReferenceCode = trim((string) ($burial->referenceCode ?? ''));
        }

        $resolvedClient = trim((string) ($validated['client'] ?? ''));
        if ($resolvedClient === '') {
            $resolvedClient = trim(implode(' ', array_filter([
                trim((string) ($burial->clientFName ?? '')),
                trim((string) ($burial->clientMName ?? '')),
                trim((string) ($burial->clientLName ?? '')),
            ], fn ($part) => $part !== '')));
        }

        $resolvedAddress = trim((string) ($validated['top_address'] ?? ''));
        if ($resolvedAddress === '') {
            $resolvedAddress = trim((string) ($burial->address ?? ''));
        }

        $resolvedContact = trim((string) ($validated['contact_number'] ?? ''));
        if ($resolvedContact === '') {
            $resolvedContact = trim((string) ($burial->contactNum ?? ''));
        }

        $resolvedDate = $this->nullableDate($validated['date_issued'] ?? null);
        if ($resolvedDate === null) {
            $resolvedDate = now()->format('Y-m-d');
        }

        try {
            DB::table('certification_details')->insert([
                'referenceCode' => $this->nullableText($resolvedReferenceCode),
                'client' => $this->nullableText($resolvedClient),
                'address' => ClientNameDisplay::nullableFormattedAddress($resolvedAddress),
                'sex' => $this->nullableText($burial->sex ?? null),
                'contactNumber' => $this->nullableText($resolvedContact),
                'date' => $resolvedDate,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
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
        ]);
    }

    public function deleteBurialRecord(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'burial_id' => ['required', 'integer', 'min:1'],
        ]);

        $burialId = (int) $validated['burial_id'];

        $row = DB::table('burial')->where('burialId', $burialId)->first();
        if ($row === null) {
            return response()->json([
                'ok' => false,
                'message' => 'Burial record not found.',
            ], 404);
        }

        try {
            DB::transaction(function () use ($burialId) {
                app(DashboardController::class)->deleteBurialRegistryRow($burialId);
            });
        } catch (QueryException $e) {
            report($e);

            return response()->json([
                'ok' => false,
                'message' => 'Could not delete this burial record. If this persists, run database migrations and try again.',
            ], 422);
        }

        return response()->json([
            'ok' => true,
            'message' => 'Burial record deleted.',
        ]);
    }

    private function mapBurialDetailsRowToApplicationPayload(?object $details): array
    {
        if ($details === null) {
            return [];
        }

        return ClientNameDisplay::normalizeApplicationNameFields([
            'deceased_name' => (string) ($details->deceasedName ?? ''),
            'deceased_age' => (string) ($details->deceasedAge ?? ''),
            'marital_status' => (string) ($details->maritalStatus ?? ''),
            'spouse_name' => (string) ($details->spouseName ?? ''),
            'deceased_address' => (string) ($details->deceasedAddress ?? ''),
            'kinamatyan' => (string) ($details->kinamatyan ?? ''),
            'occupation' => (string) ($details->occupation ?? ''),
            'claimant_name' => (string) ($details->claimantName ?? ''),
            'claimant_relation' => (string) ($details->claimantRelation ?? ''),
            'claimant_place' => (string) ($details->claimantPlace ?? ''),
            'church_obligation' => (string) ($details->churchObligation ?? ''),
            'parish_bec' => (string) ($details->parishBec ?? ''),
            'bec_selda' => (string) ($details->becSelda ?? ''),
            'stewardship' => (string) ($details->stewardship ?? ''),
            'baptized_sacrament' => (string) ($details->baptizedSacrament ?? ''),
            'baptism_date' => $this->dateForForm($details->baptismDate ?? null),
            'death_date' => $this->dateForForm($details->deathDate ?? null),
            'burial_date' => $this->dateForForm($details->burialDate ?? null),
            'burial_time' => $details->burialTime !== null ? substr((string) $details->burialTime, 0, 5) : '',
            'burial_permit_no' => (string) ($details->burialPermitNo ?? ''),
            'minor_father_name' => (string) ($details->minorFatherName ?? ''),
            'minor_mother_name' => (string) ($details->minorMotherName ?? ''),
            'ceremony_type' => (string) ($details->ceremonyType ?? ''),
            'interment_type' => (string) ($details->intermentType ?? ''),
            'niche_no' => (string) ($details->nicheNo ?? ''),
            'ar_panteon_amount' => $details->arPanteonAmount !== null ? (string) $details->arPanteonAmount : '',
            'ar_panteon_remarks' => (string) ($details->arPanteonRemarks ?? ''),
            'ar_land_amount' => $details->arLandAmount !== null ? (string) $details->arLandAmount : '',
            'ar_land_remarks' => (string) ($details->arLandRemarks ?? ''),
            'ar_kalkal_amount' => $details->arKalkalAmount !== null ? (string) $details->arKalkalAmount : '',
            'ar_kalkal_remarks' => (string) ($details->arKalkalRemarks ?? ''),
            'ar_cemetery_amount' => $details->arCemeteryAmount !== null ? (string) $details->arCemeteryAmount : '',
            'ar_cemetery_remarks' => (string) ($details->arCemeteryRemarks ?? ''),
            'ar_mass_amount' => $details->arMassAmount !== null ? (string) $details->arMassAmount : '',
            'ar_mass_remarks' => (string) ($details->arMassRemarks ?? ''),
            'ar_proroga_amount' => $details->arProrogaAmount !== null ? (string) $details->arProrogaAmount : '',
            'ar_proroga_remarks' => (string) ($details->arProrogaRemarks ?? ''),
            'ar_others_amount' => $details->arOthersAmount !== null ? (string) $details->arOthersAmount : '',
            'ar_others_remarks' => (string) ($details->arOthersRemarks ?? ''),
            'ar_extra_1_amount' => $details->arExtra1Amount !== null ? (string) $details->arExtra1Amount : '',
            'ar_extra_1_remarks' => (string) ($details->arExtra1Remarks ?? ''),
            'ar_extra_2_amount' => $details->arExtra2Amount !== null ? (string) $details->arExtra2Amount : '',
            'ar_extra_2_remarks' => (string) ($details->arExtra2Remarks ?? ''),
            'noted_bpc_chairman' => (string) ($details->notedByBpcChairman ?? ''),
            'noted_parish_fiscal' => (string) ($details->notedByParishFiscalSecretary ?? ''),
            'approved_parish_priest' => (string) ($details->approvedByParishPriest ?? ''),
        ]);
    }

    private function mapBurialApplicationPayloadToDetailsRow(array $data): array
    {
        return [
            'deceasedName' => ClientNameDisplay::nullableFormattedFamilyName($data['deceased_name'] ?? null),
            'deceasedAge' => $this->nullableText($data['deceased_age'] ?? null),
            'maritalStatus' => $this->nullableText($data['marital_status'] ?? null),
            'spouseName' => ClientNameDisplay::nullableFormattedFamilyName($data['spouse_name'] ?? null),
            'deceasedAddress' => ClientNameDisplay::nullableFormattedAddress($data['deceased_address'] ?? null),
            'kinamatyan' => $this->nullableText($data['kinamatyan'] ?? null),
            'occupation' => $this->nullableText($data['occupation'] ?? null),
            'claimantName' => ClientNameDisplay::nullableFormattedFamilyName($data['claimant_name'] ?? null),
            'claimantRelation' => $this->nullableText($data['claimant_relation'] ?? null),
            'claimantPlace' => $this->nullableText($data['claimant_place'] ?? null),
            'churchObligation' => $this->nullableText($data['church_obligation'] ?? null),
            'parishBec' => $this->nullableText($data['parish_bec'] ?? null),
            'becSelda' => $this->nullableText($data['bec_selda'] ?? null),
            'stewardship' => $this->nullableText($data['stewardship'] ?? null),
            'baptizedSacrament' => $this->nullableText($data['baptized_sacrament'] ?? null),
            'baptismDate' => $this->nullableDate($data['baptism_date'] ?? null),
            'deathDate' => $this->nullableDate($data['death_date'] ?? null),
            'burialDate' => $this->nullableDate($data['burial_date'] ?? null),
            'burialTime' => $this->nullableTime($data['burial_time'] ?? null),
            'burialPermitNo' => $this->nullableText($data['burial_permit_no'] ?? null),
            'minorFatherName' => ClientNameDisplay::nullableFormattedFamilyName($data['minor_father_name'] ?? null),
            'minorMotherName' => ClientNameDisplay::nullableFormattedFamilyName($data['minor_mother_name'] ?? null),
            'ceremonyType' => $this->nullableText($data['ceremony_type'] ?? null),
            'intermentType' => $this->nullableText($data['interment_type'] ?? null),
            'nicheNo' => $this->nullableText($data['niche_no'] ?? null),
            'arPanteonAmount' => $this->nullableInteger($data['ar_panteon_amount'] ?? null),
            'arPanteonRemarks' => $this->nullableText($data['ar_panteon_remarks'] ?? null),
            'arLandAmount' => $this->nullableInteger($data['ar_land_amount'] ?? null),
            'arLandRemarks' => $this->nullableText($data['ar_land_remarks'] ?? null),
            'arKalkalAmount' => $this->nullableInteger($data['ar_kalkal_amount'] ?? null),
            'arKalkalRemarks' => $this->nullableText($data['ar_kalkal_remarks'] ?? null),
            'arCemeteryAmount' => $this->nullableInteger($data['ar_cemetery_amount'] ?? null),
            'arCemeteryRemarks' => $this->nullableText($data['ar_cemetery_remarks'] ?? null),
            'arMassAmount' => $this->nullableInteger($data['ar_mass_amount'] ?? null),
            'arMassRemarks' => $this->nullableText($data['ar_mass_remarks'] ?? null),
            'arProrogaAmount' => $this->nullableInteger($data['ar_proroga_amount'] ?? null),
            'arProrogaRemarks' => $this->nullableText($data['ar_proroga_remarks'] ?? null),
            'arOthersAmount' => $this->nullableInteger($data['ar_others_amount'] ?? null),
            'arOthersRemarks' => $this->nullableText($data['ar_others_remarks'] ?? null),
            'arExtra1Amount' => $this->nullableInteger($data['ar_extra_1_amount'] ?? null),
            'arExtra1Remarks' => $this->nullableText($data['ar_extra_1_remarks'] ?? null),
            'arExtra2Amount' => $this->nullableInteger($data['ar_extra_2_amount'] ?? null),
            'arExtra2Remarks' => $this->nullableText($data['ar_extra_2_remarks'] ?? null),
            'notedByBpcChairman' => ClientNameDisplay::nullableFormattedFamilyName($data['noted_bpc_chairman'] ?? null),
            'notedByParishFiscalSecretary' => ClientNameDisplay::nullableFormattedFamilyName($data['noted_parish_fiscal'] ?? null),
            'approvedByParishPriest' => ClientNameDisplay::nullableFormattedPriest($data['approved_parish_priest'] ?? null),
        ];
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
            'address' => ClientNameDisplay::formatAddress((string) ($row->address ?? '')),
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

    private function nullableDate(mixed $value): ?string
    {
        $s = trim((string) ($value ?? ''));
        if ($s === '') {
            return null;
        }
        try {
            return Carbon::parse($s)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    private function nullableTime(mixed $value): ?string
    {
        $s = trim((string) ($value ?? ''));
        if ($s === '') {
            return null;
        }
        try {
            return Carbon::parse($s)->format('H:i:s');
        } catch (\Throwable) {
            return null;
        }
    }

    private function nullableInteger(mixed $value): ?int
    {
        $s = trim((string) ($value ?? ''));
        if ($s === '') {
            return null;
        }
        $normalized = str_replace(',', '', $s);
        if (! is_numeric($normalized)) {
            return null;
        }

        return (int) round((float) $normalized);
    }

    /**
     * @return array{first:string,middle:?string,last:string}
     */
    private function splitRegistryClientName(string $full): array
    {
        $parts = preg_split('/\s+/u', trim($full), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if ($parts === []) {
            return ['first' => '', 'middle' => null, 'last' => ''];
        }
        if (count($parts) === 1) {
            return ['first' => $parts[0], 'middle' => null, 'last' => ''];
        }

        $last = array_pop($parts);
        $first = array_shift($parts);

        return [
            'first' => $first ?? '',
            'middle' => $parts !== [] ? implode(' ', $parts) : null,
            'last' => $last ?? '',
        ];
    }

    /**
     * @param  array<string, mixed>  $app
     * @return array{0:int,1:string}
     */
    private function createBurialRegistryFromApplication(array $app): array
    {
        $deceasedName = trim((string) ($app['deceased_name'] ?? ''));
        $nameParts = $this->splitRegistryClientName($deceasedName);
        if ($nameParts['last'] === '') {
            throw new \InvalidArgumentException('Deceased name must include a last name.');
        }

        return DB::transaction(function () use ($nameParts, $app) {
            $user = Auth::user();
            $customerId = DB::table('customer')->insertGetId(array_filter([
                'customerFName' => $nameParts['first'],
                'customerMName' => $nameParts['middle'],
                'customerLName' => $nameParts['last'],
                'updatedAt' => now(),
                'createdBy' => $user?->userName ?? $user?->userfName ?? null,
                'userId' => $user?->getAuthIdentifier(),
            ], fn ($v) => $v !== null));

            $ref = $this->generateUniqueBurialReferenceCode();
            $insertData = [
                'referenceCode' => $ref,
                'clientFName' => $nameParts['first'],
                'clientMName' => $nameParts['middle'],
                'clientLName' => $nameParts['last'],
                'address' => ClientNameDisplay::nullableFormattedAddress($app['deceased_address'] ?? null),
                'paymentStatus' => 'Unpaid',
                'dateCreated' => now(),
                'customerId' => $customerId,
            ];
            if (Schema::hasColumn('burial', 'workflowStep')) {
                $insertData['workflowStep'] = 'application';
            }
            $insertData = array_filter($insertData, fn ($v) => $v !== null);
            $id = (int) DB::table('burial')->insertGetId($insertData);

            return [$id, $ref];
        });
    }
}
