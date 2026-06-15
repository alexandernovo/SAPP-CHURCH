<?php

namespace App\Http\Controllers;

use App\Support\ClientNameDisplay;
use App\Support\DocumentationApplicationReportWriter;
use App\Support\SacramentApplicationGate;
use App\Support\SacramentRegistrySectionFilter;
use App\Support\SacramentScheduleReservedDates;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class WeddingController extends Controller
{
    private const WEDDING_REFERENCE_SUFFIX = 'W';

    private const WEDDING_REFERENCE_CHARSET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    private const DEFAULT_CERT_PURPOSE = 'For all legal purposes';

    public function index(Request $request)
    {
        return redirect()->route('admin.wedding.application', $request->query());
    }

    public function scheduleIndex(Request $request): View
    {
        return view('wedding.view.schedule', $this->weddingSectionViewData($request, SacramentRegistrySectionFilter::SECTION_SCHEDULE));
    }

    public function certificationIndex(Request $request): View
    {
        return view('wedding.view.certification', $this->weddingSectionViewData($request, SacramentRegistrySectionFilter::SECTION_CERTIFICATION));
    }

    public function paymentIndex(Request $request): View
    {
        return view('wedding.view.payment', $this->weddingSectionViewData($request, SacramentRegistrySectionFilter::SECTION_PAYMENT));
    }

    public function applicationIndex(Request $request): View
    {
        return view('wedding.view.application-form', $this->weddingSectionViewData($request, SacramentRegistrySectionFilter::SECTION_APPLICATION));
    }

    public function nextWeddingReferenceCode(): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'reference_code' => $this->generateUniqueWeddingReferenceCode(),
        ]);
    }


    private function weddingSectionViewData(Request $request, string $section): array
    {
        $request->merge([
            'registry_type' => 'wedding',
            'registry_section' => $section,
        ]);

        $dashboard = app(DashboardController::class);
        $data = $dashboard->registryIndexData($request);

        return [
            'records' => $data['records'],
            'initialTablePayload' => $data['initialTablePayload'],
            'perPageOptions' => DashboardController::perPageOptionsList(),
            'letterOptions' => range('A', 'Z'),
            'generatedReferenceCode' => $this->generateUniqueWeddingReferenceCode(),
        ];
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
                    if (Schema::hasColumn('wedding', 'scheduleCompletedAt')) {
                        $insertData['scheduleCompletedAt'] = now();
                    }
                    if (Schema::hasColumn('wedding', 'workflowStep')) {
                        $insertData['workflowStep'] = SacramentRegistrySectionFilter::SECTION_SCHEDULE;
                    }
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

        $existingWeddingId = (int) $existing->weddingId;
        if (! SacramentApplicationGate::weddingIsPaymentComplete($existingWeddingId)) {
            return SacramentApplicationGate::paymentDenyResponse();
        }
        if (! SacramentApplicationGate::weddingIsCertificationSaved($existingWeddingId)) {
            return SacramentApplicationGate::certificationDenyResponse();
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
        if (Schema::hasColumn('wedding', 'scheduleCompletedAt')) {
            $updateData['scheduleCompletedAt'] = now();
        }
        if (Schema::hasColumn('wedding', 'workflowStep')) {
            $updateData['workflowStep'] = SacramentRegistrySectionFilter::SECTION_SCHEDULE;
        }
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

        if (! SacramentApplicationGate::weddingIsPaymentComplete($weddingId)) {
            return SacramentApplicationGate::paymentDenyResponse();
        }
        if (! SacramentApplicationGate::weddingIsCertificationSaved($weddingId)) {
            return SacramentApplicationGate::certificationDenyResponse();
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
                'address' => ClientNameDisplay::formatAddress((string) ($row->address ?? '')),
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
        $byDate = SacramentScheduleReservedDates::forMonth($year, $month);

        return response()->json([
            'ok' => true,
            'by_date' => $byDate,
            'dates' => array_keys($byDate),
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
            'payment_complete' => SacramentApplicationGate::weddingIsPaymentComplete($weddingId),
            'data' => $this->mapWeddingRowToPaymentFormFields($row),
        ]);
    }

    public function weddingPaymentSave(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'wedding_id' => ['nullable', 'integer', 'min:1'],
            'reference_code' => ['nullable', 'string', 'max:255'],
            'client' => ['nullable', 'string', 'max:255'],
            'contact_number' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'fee_rows' => ['nullable', 'array'],
            'fee_rows.*.label' => ['nullable', 'string', 'max:500'],
            'fee_rows.*.paid' => ['nullable', 'boolean'],
            'fee_rows.*.date_paid' => ['nullable', 'string', 'max:50'],
        ]);

        $weddingId = ! empty($validated['wedding_id']) ? (int) $validated['wedding_id'] : 0;
        $ref = trim((string) ($validated['reference_code'] ?? ''));
        if ($ref === '' && $weddingId <= 0) {
            $ref = $this->generateUniqueWeddingReferenceCode();
        }

        $existing = null;
        if ($weddingId > 0) {
            $existing = DB::table('wedding')->where('weddingId', $weddingId)->first();
            if ($existing === null) {
                return response()->json(['message' => 'Wedding record not found.'], 404);
            }
        } elseif ($ref !== '') {
            $existing = DB::table('wedding')->where('referenceCode', $ref)->first();
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
        if (Schema::hasColumn('wedding', 'paymentCompletedAt')) {
            $update['paymentCompletedAt'] = now();
        }
        if (Schema::hasColumn('wedding', 'workflowStep')) {
            $update['workflowStep'] = SacramentRegistrySectionFilter::SECTION_PAYMENT;
        }

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
                $weddingId = DB::transaction(function () use ($ref, $first, $middle, $last, $validated, $update) {
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
                    if (Schema::hasColumn('wedding', 'workflowStep')) {
                        $insertData['workflowStep'] = SacramentRegistrySectionFilter::SECTION_PAYMENT;
                    }
                    if (Schema::hasColumn('wedding', 'paymentCompletedAt')) {
                        $insertData['paymentCompletedAt'] = now();
                    }
                    $insertData = array_filter($insertData, fn ($v) => $v !== null);

                    return (int) DB::table('wedding')->insertGetId($insertData);
                });
            } catch (QueryException $e) {
                report($e);

                return response()->json([
                    'ok' => false,
                    'message' => 'Could not save payment details. If this persists, run database migrations and try again.',
                ], 422);
            }
        } else {
            $weddingId = (int) $existing->weddingId;
            if ($ref === '') {
                $ref = (string) ($existing->referenceCode ?? '');
            }

            try {
                DB::table('wedding')->where('weddingId', $weddingId)->update($update);
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
                'wedding_id' => $weddingId,
                'reference_code' => $ref,
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
            'application_saved' => SacramentApplicationGate::weddingIsSaved($weddingId),
            'data' => ClientNameDisplay::normalizeApplicationNameFields($this->decodeMarriageApplication($row)),
        ]);
    }

    public function weddingMarriageApplicationSave(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'wedding_id' => ['nullable', 'integer', 'min:1'],
        ]);
        $weddingId = (int) ($validated['wedding_id'] ?? 0);

        $data = $request->json() ? $request->json()->all() : $request->all();
        if (! is_array($data)) {
            $data = [];
        }
        unset($data['wedding_id'], $data['_token']);
        $data = ClientNameDisplay::normalizeApplicationNameFields($data);

        $groomName = trim((string) ($data['groom_full_name'] ?? ''));
        $brideName = trim((string) ($data['bride_full_name'] ?? ''));
        if ($groomName === '' || $brideName === '') {
            return response()->json([
                'ok' => false,
                'message' => 'Please enter the groom\'s and bride\'s first and last names on the marriage application form.',
                'errors' => [
                    'first_name' => ['Groom and bride first and last names are required.'],
                ],
            ], 422);
        }

        $created = false;
        $referenceCode = null;

        if ($weddingId <= 0) {
            try {
                [$weddingId, $referenceCode] = $this->createWeddingRegistryFromMarriageApplication($data);
                $created = true;
            } catch (QueryException $e) {
                report($e);

                return response()->json([
                    'ok' => false,
                    'message' => 'Could not create the wedding record. Please try again.',
                ], 422);
            }
        } elseif (DB::table('wedding')->where('weddingId', $weddingId)->first() === null) {
            return response()->json(['message' => 'Wedding record not found.'], 404);
        }

        $encoded = json_encode($data, JSON_UNESCAPED_UNICODE);
        if ($encoded === false) {
            return response()->json([
                'ok' => false,
                'message' => 'Could not encode the marriage application data.',
            ], 422);
        }

        try {
            DB::transaction(function () use ($weddingId, $encoded, $data, $groomName) {
                $nameParts = $this->splitRegistryClientName($groomName);
                $headerUpdate = array_filter([
                    'clientFName' => $nameParts['first'] !== '' ? $nameParts['first'] : null,
                    'clientMName' => $nameParts['middle'],
                    'clientLName' => $nameParts['last'] !== '' ? $nameParts['last'] : null,
                    'marriageApplication' => $encoded,
                ], fn ($v) => $v !== null);
                if (Schema::hasColumn('wedding', 'applicationCompletedAt')) {
                    $headerUpdate['applicationCompletedAt'] = now();
                }
                if (Schema::hasColumn('wedding', 'workflowStep')) {
                    $headerUpdate['workflowStep'] = SacramentRegistrySectionFilter::SECTION_APPLICATION;
                }
                DB::table('wedding')->where('weddingId', $weddingId)->update($headerUpdate);
                $this->upsertWeddingDetailsFromMarriageApplication($weddingId, $data);
            });
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
            'created' => $created,
            'data' => [
                'wedding_id' => $weddingId,
                'reference_code' => $referenceCode,
            ],
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

        if (! SacramentApplicationGate::weddingIsPaymentComplete($weddingId)) {
            return SacramentApplicationGate::paymentDenyResponse();
        }

        $details = DB::table('wedding_details')
            ->where('weddingId', $weddingId)
            ->orderByDesc('weddingDetailsId')
            ->first();

        $app = $this->decodeMarriageApplication($row);
        $fromDetails = $details !== null
            ? $this->mapWeddingDetailsRowToCertificationData($details)
            : $this->emptyWeddingCertificationData();
        $fromApp = $this->mapMarriageApplicationToCertificationData($app);
        $data = $this->mergeWeddingCertificationData($fromDetails, $fromApp);

        if (trim(implode('', [
            $data['first_name'] ?? '',
            $data['middle_name'] ?? '',
            $data['family_name'] ?? '',
        ])) === '') {
            $data['first_name'] = trim((string) ($row->clientFName ?? ''));
            $data['middle_name'] = trim((string) ($row->clientMName ?? ''));
            $data['family_name'] = trim((string) ($row->clientLName ?? ''));
        }

        if (trim((string) ($data['date_received'] ?? '')) === '' && trim((string) ($data['marriage']['date'] ?? '')) !== '') {
            $data['date_received'] = (string) $data['marriage']['date'];
        }

        $data['purpose'] = trim((string) ($data['purpose'] ?? '')) !== ''
            ? trim((string) $data['purpose'])
            : self::DEFAULT_CERT_PURPOSE;

        if (Schema::hasTable('wedding_certification')) {
            $certRow = DB::table('wedding_certification')->where('weddingId', $weddingId)->first();
            $certFields = $this->mapWeddingCertificationRowToCertificationData($certRow);
            foreach ($certFields as $k => $v) {
                if ($v === null || $v === '') {
                    continue;
                }
                if (is_array($v) && isset($data[$k]) && is_array($data[$k])) {
                    $data[$k] = $this->mergeWeddingCertificationData($data[$k], $v);
                } else {
                    $data[$k] = $v;
                }
            }
            if (trim((string) ($data['purpose'] ?? '')) === '') {
                $data['purpose'] = self::DEFAULT_CERT_PURPOSE;
            }
        } else {
            $certRow = null;
        }

        return response()->json([
            'ok' => true,
            'has_saved_cert' => $certRow !== null,
            'certification_saved' => SacramentApplicationGate::weddingIsCertificationSaved($weddingId),
            'data' => $data,
        ]);
    }

    public function weddingCertificationForm(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'wedding_id' => ['required', 'integer', 'min:1'],
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

        $weddingId = (int) $validated['wedding_id'];
        $wedding = DB::table('wedding')->where('weddingId', $weddingId)->first();
        if ($wedding === null) {
            return response()->json(['message' => 'Wedding record not found.'], 404);
        }

        if (! SacramentApplicationGate::weddingIsPaymentComplete($weddingId)) {
            return SacramentApplicationGate::paymentDenyResponse();
        }

        if (! Schema::hasTable('wedding_certification')) {
            return response()->json([
                'ok' => false,
                'message' => 'Certification table is missing. Run database migrations and try again.',
            ], 422);
        }

        $certRow = $this->mapWeddingCertificationRequestToTableRow($request);
        $certificationDetailsRow = $this->mapWeddingCertificationRequestToCertificationDetailsRow($request, $wedding);

        try {
            DB::transaction(function () use ($weddingId, $certRow, $certificationDetailsRow, $request) {
                $existing = DB::table('wedding_certification')->where('weddingId', $weddingId)->first();

                if ($existing) {
                    DB::table('wedding_certification')
                        ->where('weddingCertificationId', $existing->weddingCertificationId)
                        ->update($certRow);
                } else {
                    DB::table('wedding_certification')->insert(array_merge($certRow, [
                        'weddingId' => $weddingId,
                        'created_at' => now(),
                    ]));
                }

                DB::table('certification_details')->insert($certificationDetailsRow);

                $headerUpdate = [
                    'contactNum' => $this->nullableText($request->input('contact_number')),
                    'address' => ClientNameDisplay::nullableFormattedAddress($request->input('top_address')),
                ];
                if (Schema::hasColumn('wedding', 'certificationCompletedAt')) {
                    $headerUpdate['certificationCompletedAt'] = now();
                }
                if (Schema::hasColumn('wedding', 'workflowStep')) {
                    $headerUpdate['workflowStep'] = SacramentRegistrySectionFilter::SECTION_CERTIFICATION;
                }
                DB::table('wedding')->where('weddingId', $weddingId)->update($headerUpdate);
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
                'wedding_id' => $weddingId,
            ],
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

            return is_array($decoded) ? ClientNameDisplay::normalizeApplicationNameFields($decoded) : [];
        }
        if (is_array($raw)) {
            return ClientNameDisplay::normalizeApplicationNameFields($raw);
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
            'address' => ClientNameDisplay::formatAddress((string) ($row->address ?? '')),
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

    /**
     * @return array<string, mixed>
     */
    private function emptyWeddingCertificationData(): array
    {
        return [
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
            'bride' => [
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
            ],
            'marriage' => [
                'place' => '',
                'date' => '',
                'time' => '',
            ],
            'registry_header' => [
                'province' => 'Antique',
                'city_municipality' => 'Barbaza',
            ],
            'groom_sex' => 'Male',
            'bride_sex' => 'Female',
            'groom_age' => '',
            'bride_age' => '',
            'groom_citizenship' => 'Filipino',
            'bride_citizenship' => 'Filipino',
            'groom_religion' => '',
            'bride_religion' => '',
            'groom_civil_status' => '',
            'bride_civil_status' => '',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mapWeddingDetailsRowToCertificationData(object $details): array
    {
        $data = $this->emptyWeddingCertificationData();

        $groom = $this->splitPersonName($details->groomFullName ?? '');
        $data['first_name'] = $groom['first'];
        $data['middle_name'] = $groom['middle'];
        $data['family_name'] = $groom['last'];
        $data['date_of_birth'] = $this->dateForForm($details->groomDateOfBirth ?? null);
        $data['place_of_birth'] = (string) ($details->groomPlaceOfBirth ?? '');

        $father = $this->splitPersonName($details->groomFather ?? '');
        $data['father_first_name'] = $father['first'];
        $data['father_middle_name'] = $father['middle'];
        $data['father_last_name'] = $father['last'];

        $mother = $this->splitPersonName($details->groomMotherMaiden ?? '');
        $data['mother_first_name'] = $mother['first'];
        $data['mother_middle_name'] = $mother['middle'];
        $data['mother_last_name'] = $mother['last'];

        $addr = $this->parseAddressCommaParts((string) ($details->groomPresentAddress ?? ''));
        $data['barangay'] = $addr['barangay'];
        $data['municipality'] = $addr['municipality'];
        $data['province'] = $addr['province'] !== '' ? $addr['province'] : 'Antique';

        $data['priest'] = ClientNameDisplay::formatPriestName((string) ($details->officiatingPriest ?? ''));
        $data['sponsors'] = implode('; ', array_values(array_filter([
            trim((string) ($details->sponsorsLine1 ?? '')),
            trim((string) ($details->sponsorsLine2 ?? '')),
            trim((string) ($details->sponsorsLine3 ?? '')),
        ], fn ($s) => $s !== '')));

        $bride = $this->splitPersonName($details->brideFullName ?? '');
        $data['bride']['first_name'] = $bride['first'];
        $data['bride']['middle_name'] = $bride['middle'];
        $data['bride']['family_name'] = $bride['last'];
        $data['bride']['date_of_birth'] = $this->dateForForm($details->brideDateOfBirth ?? null);
        $data['bride']['place_of_birth'] = (string) ($details->bridePlaceOfBirth ?? '');

        $brideFather = $this->splitPersonName($details->brideFather ?? '');
        $data['bride']['father_first_name'] = $brideFather['first'];
        $data['bride']['father_middle_name'] = $brideFather['middle'];
        $data['bride']['father_last_name'] = $brideFather['last'];

        $brideMother = $this->splitPersonName($details->brideMotherMaiden ?? '');
        $data['bride']['mother_first_name'] = $brideMother['first'];
        $data['bride']['mother_middle_name'] = $brideMother['middle'];
        $data['bride']['mother_last_name'] = $brideMother['last'];

        $brideAddr = $this->parseAddressCommaParts((string) ($details->bridePresentAddress ?? ''));
        $data['bride']['barangay'] = $brideAddr['barangay'];
        $data['bride']['municipality'] = $brideAddr['municipality'];
        $data['bride']['province'] = $brideAddr['province'] !== '' ? $brideAddr['province'] : 'Antique';

        $churchDate = $this->dateForForm($details->churchWeddingDate ?? null);
        $data['marriage']['place'] = trim((string) ($details->churchWeddingPlace ?? ''));
        $data['marriage']['date'] = $churchDate;
        $data['date_received'] = $churchDate;

        if ($details->groomAge !== null) {
            $data['groom_age'] = (string) $details->groomAge;
        }
        if ($details->brideAge !== null) {
            $data['bride_age'] = (string) $details->brideAge;
        }

        $data['groom_religion'] = trim((string) ($details->groomReligion ?? ''));
        $data['bride_religion'] = trim((string) ($details->brideReligion ?? ''));

        if ($data['municipality'] !== '') {
            $data['registry_header']['city_municipality'] = $data['municipality'];
        }
        if ($data['province'] !== '') {
            $data['registry_header']['province'] = $data['province'];
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $app
     * @return array<string, mixed>
     */
    private function mapMarriageApplicationToCertificationData(array $app): array
    {
        $data = $this->emptyWeddingCertificationData();

        $groom = $this->splitPersonName($app['groom_full_name'] ?? '');
        $data['first_name'] = $groom['first'];
        $data['middle_name'] = $groom['middle'];
        $data['family_name'] = $groom['last'];
        $data['date_of_birth'] = $this->dateForForm($app['groom_date_of_birth'] ?? null);
        $data['place_of_birth'] = trim((string) ($app['groom_place_of_birth'] ?? ''));

        $father = $this->splitPersonName($app['groom_father'] ?? '');
        $data['father_first_name'] = $father['first'];
        $data['father_middle_name'] = $father['middle'];
        $data['father_last_name'] = $father['last'];

        $mother = $this->splitPersonName($app['groom_mother_maiden'] ?? '');
        $data['mother_first_name'] = $mother['first'];
        $data['mother_middle_name'] = $mother['middle'];
        $data['mother_last_name'] = $mother['last'];

        $addr = $this->parseAddressCommaParts((string) ($app['groom_present_address'] ?? ''));
        $data['barangay'] = $addr['barangay'];
        $data['municipality'] = $addr['municipality'];
        $data['province'] = $addr['province'] !== '' ? $addr['province'] : 'Antique';

        $data['priest'] = trim((string) ($app['officiating_priest'] ?? ''));
        $data['sponsors'] = implode('; ', array_values(array_filter([
            trim((string) ($app['sponsors_line1'] ?? '')),
            trim((string) ($app['sponsors_line2'] ?? '')),
            trim((string) ($app['sponsors_line3'] ?? '')),
        ], fn ($s) => $s !== '')));

        $bride = $this->splitPersonName($app['bride_full_name'] ?? '');
        $data['bride']['first_name'] = $bride['first'];
        $data['bride']['middle_name'] = $bride['middle'];
        $data['bride']['family_name'] = $bride['last'];
        $data['bride']['date_of_birth'] = $this->dateForForm($app['bride_date_of_birth'] ?? null);
        $data['bride']['place_of_birth'] = trim((string) ($app['bride_place_of_birth'] ?? ''));

        $brideFather = $this->splitPersonName($app['bride_father'] ?? '');
        $data['bride']['father_first_name'] = $brideFather['first'];
        $data['bride']['father_middle_name'] = $brideFather['middle'];
        $data['bride']['father_last_name'] = $brideFather['last'];

        $brideMother = $this->splitPersonName($app['bride_mother_maiden'] ?? '');
        $data['bride']['mother_first_name'] = $brideMother['first'];
        $data['bride']['mother_middle_name'] = $brideMother['middle'];
        $data['bride']['mother_last_name'] = $brideMother['last'];

        $brideAddr = $this->parseAddressCommaParts((string) ($app['bride_present_address'] ?? ''));
        $data['bride']['barangay'] = $brideAddr['barangay'];
        $data['bride']['municipality'] = $brideAddr['municipality'];
        $data['bride']['province'] = $brideAddr['province'] !== '' ? $brideAddr['province'] : 'Antique';

        $churchDate = $this->dateForForm($app['church_wedding_date'] ?? null);
        $data['marriage']['place'] = trim((string) ($app['church_wedding_place'] ?? ''));
        $data['marriage']['date'] = $churchDate;
        $data['date_received'] = $churchDate;
        $data['date_issued'] = $this->dateForForm($app['date_of_application'] ?? null);

        $groomAge = trim((string) ($app['groom_age'] ?? ''));
        if ($groomAge !== '') {
            $data['groom_age'] = $groomAge;
        }
        $brideAge = trim((string) ($app['bride_age'] ?? ''));
        if ($brideAge !== '') {
            $data['bride_age'] = $brideAge;
        }

        $data['groom_religion'] = trim((string) ($app['groom_religion'] ?? ''));
        $data['bride_religion'] = trim((string) ($app['bride_religion'] ?? ''));

        if ($data['municipality'] !== '') {
            $data['registry_header']['city_municipality'] = $data['municipality'];
        }
        if ($data['province'] !== '') {
            $data['registry_header']['province'] = $data['province'];
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $primary
     * @param  array<string, mixed>  $fallback
     * @return array<string, mixed>
     */
    private function mergeWeddingCertificationData(array $primary, array $fallback): array
    {
        foreach ($fallback as $key => $value) {
            if (is_array($value)) {
                $existing = isset($primary[$key]) && is_array($primary[$key]) ? $primary[$key] : [];
                $primary[$key] = $this->mergeWeddingCertificationData($existing, $value);

                continue;
            }
            $current = $primary[$key] ?? '';
            if ($current === null || trim((string) $current) === '') {
                $primary[$key] = $value;
            }
        }

        return $primary;
    }

    /**
     * @param  array<string, mixed>  $app
     */
    private function upsertWeddingDetailsFromMarriageApplication(int $weddingId, array $app): void
    {
        if (! Schema::hasTable('wedding_details')) {
            return;
        }

        $row = $this->mapMarriageApplicationPayloadToWeddingDetailsRow($weddingId, $app);
        $existing = DB::table('wedding_details')
            ->where('weddingId', $weddingId)
            ->orderByDesc('weddingDetailsId')
            ->first();

        if ($existing !== null) {
            DB::table('wedding_details')
                ->where('weddingDetailsId', $existing->weddingDetailsId)
                ->update($row);

            return;
        }

        $row['created_at'] = now();
        DB::table('wedding_details')->insert($row);
    }

    /**
     * @param  array<string, mixed>  $app
     * @return array<string, mixed>
     */
    private function mapMarriageApplicationPayloadToWeddingDetailsRow(int $weddingId, array $app): array
    {
        $groomAge = trim((string) ($app['groom_age'] ?? ''));
        $brideAge = trim((string) ($app['bride_age'] ?? ''));

        return [
            'weddingId' => $weddingId,
            'groomFullName' => $this->nullableText($app['groom_full_name'] ?? null),
            'groomAge' => $groomAge !== '' && ctype_digit($groomAge) ? (int) $groomAge : null,
            'groomDateOfBirth' => $this->parseFlexibleDate($app['groom_date_of_birth'] ?? null),
            'groomPlaceOfBirth' => $this->nullableText($app['groom_place_of_birth'] ?? null),
            'groomPresentAddress' => ClientNameDisplay::nullableFormattedAddress($app['groom_present_address'] ?? null),
            'groomFather' => $this->nullableText($app['groom_father'] ?? null),
            'groomMotherMaiden' => $this->nullableText($app['groom_mother_maiden'] ?? null),
            'groomReligion' => $this->nullableText($app['groom_religion'] ?? null),
            'groomBaptismDate' => $this->parseFlexibleDate($app['groom_baptism_date'] ?? null),
            'groomBaptismPlace' => $this->nullableText($app['groom_baptism_place'] ?? null),
            'groomConfirmationDate' => $this->nullableText($app['groom_confirmation_date'] ?? null),
            'groomContact' => $this->nullableText($app['groom_contact'] ?? null),
            'groomSignature' => $this->nullableText($app['groom_signature'] ?? null),
            'brideFullName' => $this->nullableText($app['bride_full_name'] ?? null),
            'brideAge' => $brideAge !== '' && ctype_digit($brideAge) ? (int) $brideAge : null,
            'brideDateOfBirth' => $this->parseFlexibleDate($app['bride_date_of_birth'] ?? null),
            'bridePlaceOfBirth' => $this->nullableText($app['bride_place_of_birth'] ?? null),
            'bridePresentAddress' => ClientNameDisplay::nullableFormattedAddress($app['bride_present_address'] ?? null),
            'brideFather' => $this->nullableText($app['bride_father'] ?? null),
            'brideMotherMaiden' => $this->nullableText($app['bride_mother_maiden'] ?? null),
            'brideReligion' => $this->nullableText($app['bride_religion'] ?? null),
            'brideBaptismDate' => $this->parseFlexibleDate($app['bride_baptism_date'] ?? null),
            'brideBaptismPlace' => $this->nullableText($app['bride_baptism_place'] ?? null),
            'brideConfirmationDate' => $this->nullableText($app['bride_confirmation_date'] ?? null),
            'brideContact' => $this->nullableText($app['bride_contact'] ?? null),
            'brideSignature' => $this->nullableText($app['bride_signature'] ?? null),
            'civilMarriageDate' => $this->parseFlexibleDate($app['civil_marriage_date'] ?? null),
            'civilMarriagePlace' => $this->nullableText($app['civil_marriage_place'] ?? null),
            'prenuptialInvestigationDate' => $this->parseFlexibleDate($app['prenuptial_investigation_date'] ?? null),
            'churchWeddingDate' => $this->parseFlexibleDate($app['church_wedding_date'] ?? null),
            'churchWeddingPlace' => $this->nullableText($app['church_wedding_place'] ?? null),
            'officiatingPriest' => ClientNameDisplay::nullableFormattedPriest($app['officiating_priest'] ?? null),
            'sponsorsLine1' => $this->nullableText($app['sponsors_line1'] ?? null),
            'sponsorsLine2' => $this->nullableText($app['sponsors_line2'] ?? null),
            'sponsorsLine3' => $this->nullableText($app['sponsors_line3'] ?? null),
            'parishSecretaryName' => $this->nullableText($app['parish_secretary_name'] ?? null),
            'dateOfApplication' => $this->parseFlexibleDate($app['date_of_application'] ?? null),
            'arNumber' => $this->nullableText($app['ar_number'] ?? null),
            'updated_at' => now(),
        ];
    }

    /**
     * @return array{barangay:string,municipality:string,province:string}
     */
    private function parseAddressCommaParts(string $address): array
    {
        $bits = array_values(array_filter(array_map('trim', explode(',', $address)), fn ($s) => $s !== ''));

        return [
            'barangay' => $bits[0] ?? '',
            'municipality' => $bits[1] ?? '',
            'province' => count($bits) > 2 ? implode(', ', array_slice($bits, 2)) : '',
        ];
    }

    /**
     * @return array{first:string,middle:string,last:string}
     */
    private function splitPersonName(mixed $value): array
    {
        $full = trim((string) ($value ?? ''));
        if ($full === '') {
            return ['first' => '', 'middle' => '', 'last' => ''];
        }
        if (str_contains($full, ',')) {
            $parts = array_map('trim', explode(',', $full, 2));
            $last = $parts[0] ?? '';
            $rest = $this->splitFullNameThreeParts($parts[1] ?? '');

            return [
                'first' => $rest['first'],
                'middle' => $rest['middle'],
                'last' => $last !== '' ? $last : $rest['last'],
            ];
        }

        return $this->splitFullNameThreeParts($full);
    }

    private function parseFlexibleDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function mapWeddingCertificationRequestToTableRow(Request $request): array
    {
        return [
            'groomFirstName' => $this->nullableText($request->input('child_first_name')),
            'groomMiddleName' => $this->nullableText($request->input('child_middle_name')),
            'groomFamilyName' => $this->nullableText($request->input('child_last_name')),
            'dateOfBirth' => $this->parseFlexibleDate($request->input('birthday')),
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
            'certDateReceived' => $this->parseFlexibleDate($request->input('date_received')),
            'certDateIssued' => $this->parseFlexibleDate($request->input('date_issued')),
            'priest' => ClientNameDisplay::nullableFormattedPriest($request->input('priest')),
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
    private function mapWeddingCertificationRequestToCertificationDetailsRow(Request $request, object $wedding): array
    {
        $resolvedReferenceCode = trim((string) ($request->input('reference_code') ?? ''));
        if ($resolvedReferenceCode === '') {
            $resolvedReferenceCode = trim((string) ($wedding->referenceCode ?? ''));
        }

        $resolvedClient = trim((string) ($request->input('client') ?? ''));
        if ($resolvedClient === '') {
            $resolvedClient = trim(implode(' ', array_filter([
                trim((string) ($wedding->clientFName ?? '')),
                trim((string) ($wedding->clientMName ?? '')),
                trim((string) ($wedding->clientLName ?? '')),
            ], fn ($part) => $part !== '')));
        }

        $resolvedAddress = trim((string) ($request->input('top_address') ?? ''));
        if ($resolvedAddress === '') {
            $resolvedAddress = trim((string) ($wedding->address ?? ''));
        }

        $resolvedContact = trim((string) ($request->input('contact_number') ?? ''));
        if ($resolvedContact === '') {
            $resolvedContact = trim((string) ($wedding->contactNum ?? ''));
        }

        $resolvedDate = $this->parseFlexibleDate($request->input('date_issued'));
        if ($resolvedDate === null) {
            $resolvedDate = now()->format('Y-m-d');
        }

        return [
            'referenceCode' => $this->nullableText($resolvedReferenceCode),
            'client' => $this->nullableText($resolvedClient),
            'address' => ClientNameDisplay::nullableFormattedAddress($resolvedAddress),
            'sex' => $this->nullableText('Male'),
            'contactNumber' => $this->nullableText($resolvedContact),
            'date' => $resolvedDate,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mapWeddingCertificationRowToCertificationData(?object $row): array
    {
        if ($row === null) {
            return [];
        }

        return [
            'first_name' => (string) ($row->groomFirstName ?? ''),
            'middle_name' => (string) ($row->groomMiddleName ?? ''),
            'family_name' => (string) ($row->groomFamilyName ?? ''),
            'date_of_birth' => $this->dateForForm($row->dateOfBirth ?? null),
            'place_of_birth' => (string) ($row->placeOfBirth ?? ''),
            'father_first_name' => (string) ($row->fatherFirstName ?? ''),
            'father_middle_name' => (string) ($row->fatherMiddleName ?? ''),
            'father_last_name' => (string) ($row->fatherLastName ?? ''),
            'mother_first_name' => (string) ($row->motherFirstName ?? ''),
            'mother_middle_name' => (string) ($row->motherMiddleName ?? ''),
            'mother_last_name' => (string) ($row->motherLastName ?? ''),
            'barangay' => (string) ($row->addressBarangay ?? ''),
            'municipality' => (string) ($row->addressMunicipality ?? ''),
            'province' => (string) ($row->addressProvince ?? ''),
            'date_received' => $this->dateForForm($row->certDateReceived ?? null),
            'date_issued' => $this->dateForForm($row->certDateIssued ?? null),
            'book_no' => (string) ($row->certBookNo ?? ''),
            'register_no' => (string) ($row->certRegisterNo ?? ''),
            'page_no' => (string) ($row->certPageNo ?? ''),
            'priest' => ClientNameDisplay::formatPriestName((string) ($row->priest ?? '')),
            'sponsors' => (string) ($row->certSponsors ?? ''),
            'purpose' => (string) ($row->certPurpose ?? ''),
        ];
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
    private function createWeddingRegistryFromMarriageApplication(array $app): array
    {
        $groomName = trim((string) ($app['groom_full_name'] ?? ''));
        $nameParts = $this->splitRegistryClientName($groomName);
        if ($nameParts['last'] === '') {
            throw new \InvalidArgumentException('Groom name must include a last name.');
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

            $ref = $this->generateUniqueWeddingReferenceCode();
            $insertData = [
                'referenceCode' => $ref,
                'clientFName' => $nameParts['first'],
                'clientMName' => $nameParts['middle'],
                'clientLName' => $nameParts['last'],
                'address' => ClientNameDisplay::nullableFormattedAddress($app['groom_present_address'] ?? null),
                'paymentStatus' => 'Unpaid',
                'dateCreated' => now(),
                'customerId' => $customerId,
            ];
            if (Schema::hasColumn('wedding', 'workflowStep')) {
                $insertData['workflowStep'] = 'application';
            }
            $insertData = array_filter($insertData, fn ($v) => $v !== null);
            $id = (int) DB::table('wedding')->insertGetId($insertData);

            return [$id, $ref];
        });
    }
}
