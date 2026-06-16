<?php

namespace App\Http\Controllers;

use App\Support\ClientNameDisplay;
use App\Support\DocumentationApplicationReportWriter;
use App\Support\SacramentApplicationGate;
use App\Support\SacramentReferenceCode;
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

class ConfirmationController extends Controller
{
    private const CONFIRMATION_REFERENCE_SUFFIX = 'T';

    private const CONFIRMATION_REFERENCE_CHARSET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    public function index(Request $request)
    {
        return redirect()->route('admin.confirmation.application', $request->query());
    }

    public function scheduleIndex(Request $request): View
    {
        return view('confirmation.view.schedule', $this->confirmationSectionViewData($request, SacramentRegistrySectionFilter::SECTION_SCHEDULE));
    }

    public function certificationIndex(Request $request): View
    {
        return view('confirmation.view.certification', $this->confirmationSectionViewData($request, SacramentRegistrySectionFilter::SECTION_CERTIFICATION));
    }

    public function paymentIndex(Request $request): View
    {
        return view('confirmation.view.payment', $this->confirmationSectionViewData($request, SacramentRegistrySectionFilter::SECTION_PAYMENT));
    }

    public function applicationIndex(Request $request): View
    {
        return view('confirmation.view.application-form', $this->confirmationSectionViewData($request, SacramentRegistrySectionFilter::SECTION_APPLICATION));
    }

    public function nextConfirmationReferenceCode(): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'reference_code' => $this->generateUniqueConfirmationReferenceCode(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function confirmationSectionViewData(Request $request, string $section): array
    {
        $request->merge([
            'registry_type' => 'confirmation',
            'registry_section' => $section,
        ]);

        $dashboard = app(DashboardController::class);
        $data = $dashboard->registryIndexData($request);

        return [
            'records' => $data['records'],
            'initialTablePayload' => $data['initialTablePayload'],
            'perPageOptions' => DashboardController::perPageOptionsList(),
            'letterOptions' => range('A', 'Z'),
            'generatedReferenceCode' => $this->generateUniqueConfirmationReferenceCode(),
        ];
    }

    private function generateUniqueConfirmationReferenceCode(): string
    {
        $year = (int) date('Y');

        for ($attempt = 0; $attempt < 50; $attempt++) {
            $code = $this->formatConfirmationReferenceCode($year, $this->randomConfirmationReferenceCodeSegment(7));
            if (! DB::table('confirmation')->where('referenceCode', $code)->exists()) {
                return $code;
            }
        }

        throw new \RuntimeException('Could not generate a unique confirmation reference code.');
    }

    private function formatConfirmationReferenceCode(int $year, string $middle): string
    {
        return $year.'-'.$middle.'-'.self::CONFIRMATION_REFERENCE_SUFFIX;
    }

    private function randomConfirmationReferenceCodeSegment(int $length): string
    {
        $max = strlen(self::CONFIRMATION_REFERENCE_CHARSET) - 1;
        $out = '';
        for ($i = 0; $i < $length; $i++) {
            $out .= self::CONFIRMATION_REFERENCE_CHARSET[random_int(0, $max)];
        }

        return $out;
    }

    public function scheduleConfirmation(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'confirmation_id' => ['nullable', 'integer'],
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
            $ref = $this->generateUniqueConfirmationReferenceCode();
        }

        $query = DB::table('confirmation');
        if (! empty($validated['confirmation_id'])) {
            $query->where('confirmationId', $validated['confirmation_id']);
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
                    if (Schema::hasColumn('confirmation', 'scheduleCompletedAt')) {
                        $insertData['scheduleCompletedAt'] = now();
                    }
                    if (Schema::hasColumn('confirmation', 'workflowStep')) {
                        $insertData['workflowStep'] = SacramentRegistrySectionFilter::SECTION_SCHEDULE;
                    }
                    $insertData = array_filter($insertData, fn ($v) => $v !== null);

                    return (int) DB::table('confirmation')->insertGetId($insertData);
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
                'message' => 'Confirmation schedule created successfully.',
                'created' => true,
                'data' => [
                    'confirmation_id' => $newId,
                    'reference_code' => $ref,
                    'schedule_requested' => $scheduleAt,
                ],
            ]);
        }

        $existingConfirmationId = (int) $existing->confirmationId;
        if (! SacramentApplicationGate::confirmationIsPaymentComplete($existingConfirmationId)) {
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
        if (Schema::hasColumn('confirmation', 'scheduleCompletedAt')) {
            $updateData['scheduleCompletedAt'] = now();
        }
        if (Schema::hasColumn('confirmation', 'workflowStep')) {
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
            'message' => 'Confirmation schedule updated successfully.',
            'created' => false,
            'data' => [
                'confirmation_id' => $existing->confirmationId ?? null,
                'reference_code' => $existing->referenceCode ?? $ref,
                'schedule_requested' => $scheduleAt,
            ],
        ]);
    }

    public function confirmationScheduleDetails(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'confirmation_id' => ['required', 'integer', 'min:1'],
        ]);
        $confirmationId = (int) $validated['confirmation_id'];

        $row = DB::table('confirmation')->where('confirmationId', $confirmationId)->first();
        if ($row === null) {
            return response()->json([
                'ok' => false,
                'message' => 'Confirmation record not found.',
            ], 404);
        }

        if (! SacramentApplicationGate::confirmationIsPaymentComplete($confirmationId)) {
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
                'confirmation_id' => $confirmationId,
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

    public function confirmationReservedDates(Request $request): JsonResponse
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

    public function confirmationPaymentDetails(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'confirmation_id' => ['required', 'integer', 'min:1'],
        ]);

        $confirmationId = (int) $validated['confirmation_id'];

        $row = DB::table('confirmation')->where('confirmationId', $confirmationId)->first();
        if ($row === null) {
            return response()->json(['message' => 'Confirmation record not found.'], 404);
        }

        $this->ensureConfirmationReferenceCode($confirmationId);
        $row = DB::table('confirmation')->where('confirmationId', $confirmationId)->first();

        return response()->json([
            'ok' => true,
            'payment_complete' => SacramentApplicationGate::confirmationIsPaymentComplete($confirmationId),
            'data' => $this->mapConfirmationRowToPaymentFormFields($row),
        ]);
    }

    public function confirmationPaymentSave(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'confirmation_id' => ['nullable', 'integer', 'min:1'],
            'reference_code' => ['nullable', 'string', 'max:255'],
            'client' => ['nullable', 'string', 'max:255'],
            'contact_number' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'fee_rows' => ['nullable', 'array'],
            'fee_rows.*.label' => ['nullable', 'string', 'max:500'],
            'fee_rows.*.paid' => ['nullable', 'boolean'],
            'fee_rows.*.date_paid' => ['nullable', 'string', 'max:50'],
        ]);

        $confirmationId = ! empty($validated['confirmation_id']) ? (int) $validated['confirmation_id'] : 0;
        $ref = trim((string) ($validated['reference_code'] ?? ''));
        if ($ref === '' && $confirmationId <= 0) {
            $ref = $this->generateUniqueConfirmationReferenceCode();
        }

        $existing = null;
        if ($confirmationId > 0) {
            $existing = DB::table('confirmation')->where('confirmationId', $confirmationId)->first();
            if ($existing === null) {
                return response()->json(['message' => 'Confirmation record not found.'], 404);
            }
        } elseif ($ref !== '') {
            $existing = DB::table('confirmation')->where('referenceCode', $ref)->first();
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
            $normalized = $this->defaultConfirmationPaymentFeeRows();
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
        if (Schema::hasColumn('confirmation', 'paymentCompletedAt')) {
            $update['paymentCompletedAt'] = now();
        }
        if (Schema::hasColumn('confirmation', 'workflowStep')) {
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
                $confirmationId = DB::transaction(function () use ($ref, $first, $middle, $last, $validated, $update) {
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
                    if (Schema::hasColumn('confirmation', 'workflowStep')) {
                        $insertData['workflowStep'] = SacramentRegistrySectionFilter::SECTION_PAYMENT;
                    }
                    if (Schema::hasColumn('confirmation', 'paymentCompletedAt')) {
                        $insertData['paymentCompletedAt'] = now();
                    }
                    $insertData = array_filter($insertData, fn ($v) => $v !== null);

                    return (int) DB::table('confirmation')->insertGetId($insertData);
                });
            } catch (QueryException $e) {
                report($e);

                return response()->json([
                    'ok' => false,
                    'message' => 'Could not save payment details. If this persists, run database migrations and try again.',
                ], 422);
            }
        } else {
            $confirmationId = (int) $existing->confirmationId;
            if ($ref === '') {
                $ref = (string) ($existing->referenceCode ?? '');
            }

            try {
                DB::table('confirmation')->where('confirmationId', $confirmationId)->update($update);
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
                'confirmation_id' => $confirmationId,
                'reference_code' => $ref,
                'payment_status' => $paymentStatus,
            ],
        ]);
    }

    public function deleteConfirmationRecord(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'confirmation_id' => ['required', 'integer', 'min:1'],
        ]);

        $confirmationId = (int) $validated['confirmation_id'];

        $row = DB::table('confirmation')->where('confirmationId', $confirmationId)->first();
        if ($row === null) {
            return response()->json([
                'ok' => false,
                'message' => 'Confirmation record not found.',
            ], 404);
        }

        try {
            DB::transaction(function () use ($confirmationId) {
                app(DashboardController::class)->deleteConfirmationRegistryRow($confirmationId);
            });
        } catch (QueryException $e) {
            report($e);

            return response()->json([
                'ok' => false,
                'message' => 'Could not delete this confirmation record. If this persists, run database migrations and try again.',
            ], 422);
        }

        return response()->json([
            'ok' => true,
            'message' => 'Confirmation record deleted.',
        ]);
    }

    public function confirmationApplicationDetails(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'confirmation_id' => ['required', 'integer', 'min:1'],
        ]);
        $id = (int) $validated['confirmation_id'];
        $row = DB::table('confirmation')->where('confirmationId', $id)->first();
        if ($row === null) {
            return response()->json(['message' => 'Confirmation record not found.'], 404);
        }

        $details = $this->latestConfirmationDetailsRow($id);
        $data = $this->mapConfirmationDetailsRowToApplicationForm($details);
        $this->overlayNonEmptyJsonFields($data, $this->decodeConfirmationJsonColumn($row, 'confirmationApplication'));
        $this->applyRegistryClientNamesToConfirmationApplicationData($row, $data);
        $this->applyChristeningScheduleToConfirmationApplicationData($row, $data);
        $data = ClientNameDisplay::normalizeApplicationNameFields($data);

        return response()->json([
            'ok' => true,
            'application_saved' => SacramentApplicationGate::confirmationIsSaved($id),
            'data' => $data,
        ]);
    }

    public function confirmationApplicationSave(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'confirmation_id' => ['nullable', 'integer', 'min:1'],
        ]);
        $id = (int) ($validated['confirmation_id'] ?? 0);

        $data = $request->json() ? $request->json()->all() : $request->all();
        if (! is_array($data)) {
            $data = [];
        }
        unset($data['confirmation_id'], $data['_token']);
        $data = ClientNameDisplay::normalizeApplicationNameFields($data);

        $firstName = trim((string) ($data['first_name'] ?? ''));
        $middleName = trim((string) ($data['middle_name'] ?? ''));
        $familyName = trim((string) ($data['family_name'] ?? ''));
        if ($firstName === '' || $familyName === '') {
            return response()->json([
                'ok' => false,
                'message' => 'Please enter the candidate\'s first name and last name on the application form.',
                'errors' => [
                    'first_name' => ['First and last name are required.'],
                ],
            ], 422);
        }

        $created = false;
        $referenceCode = null;

        if ($id <= 0) {
            try {
                [$id, $referenceCode] = $this->createConfirmationRegistryFromApplicationNames(
                    $firstName,
                    $middleName !== '' ? $middleName : null,
                    $familyName,
                    $data['address'] ?? null
                );
                $created = true;
            } catch (QueryException $e) {
                report($e);

                return response()->json([
                    'ok' => false,
                    'message' => 'Could not create the confirmation record. Please try again.',
                ], 422);
            }
        } elseif (DB::table('confirmation')->where('confirmationId', $id)->first() === null) {
            return response()->json(['message' => 'Confirmation record not found.'], 404);
        }

        $encoded = json_encode($data, JSON_UNESCAPED_UNICODE);
        if ($encoded === false) {
            return response()->json(['ok' => false, 'message' => 'Could not encode the application.'], 422);
        }

        try {
            $headerUpdate = [
                'clientFName' => $firstName,
                'clientMName' => $middleName !== '' ? $middleName : null,
                'clientLName' => $familyName,
                'confirmationApplication' => $encoded,
            ];
            if (Schema::hasColumn('confirmation', 'applicationCompletedAt')) {
                $headerUpdate['applicationCompletedAt'] = now();
            }
            if (Schema::hasColumn('confirmation', 'workflowStep')) {
                $headerUpdate['workflowStep'] = SacramentRegistrySectionFilter::SECTION_APPLICATION;
            }
            DB::table('confirmation')->where('confirmationId', $id)->update($headerUpdate);
            $this->syncConfirmationDetailsFromApplicationPayload($id, $data);
        } catch (QueryException $e) {
            report($e);

            return response()->json([
                'ok' => false,
                'message' => 'Could not save. Run database migrations and try again.',
            ], 422);
        }

        DocumentationApplicationReportWriter::syncConfirmation($id, $data);

        return response()->json([
            'ok' => true,
            'message' => 'Confirmation application saved.',
            'created' => $created,
            'data' => [
                'confirmation_id' => $id,
                'reference_code' => $referenceCode,
            ],
        ]);
    }

    public function confirmationArancelDetails(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'confirmation_id' => ['required', 'integer', 'min:1'],
        ]);
        $id = (int) $validated['confirmation_id'];
        $row = DB::table('confirmation')->where('confirmationId', $id)->first();
        if ($row === null) {
            return response()->json(['message' => 'Confirmation record not found.'], 404);
        }

        $details = $this->latestConfirmationDetailsRow($id);
        $data = $this->mapConfirmationDetailsRowToArancelForm($details);
        $this->overlayNonEmptyJsonFields($data, $this->decodeConfirmationJsonColumn($row, 'confirmationArancel'));

        return response()->json([
            'ok' => true,
            'data' => $data,
        ]);
    }

    public function confirmationArancelSave(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'confirmation_id' => ['required', 'integer', 'min:1'],
        ]);
        $id = (int) $validated['confirmation_id'];
        $existing = DB::table('confirmation')->where('confirmationId', $id)->first();
        if ($existing === null) {
            return response()->json(['message' => 'Confirmation record not found.'], 404);
        }

        $data = $request->json() ? $request->json()->all() : $request->all();
        if (! is_array($data)) {
            $data = [];
        }
        unset($data['confirmation_id'], $data['_token']);
        $encoded = json_encode($data, JSON_UNESCAPED_UNICODE);
        if ($encoded === false) {
            return response()->json(['ok' => false, 'message' => 'Could not encode the arancel.'], 422);
        }

        try {
            DB::table('confirmation')->where('confirmationId', $id)->update([
                'confirmationArancel' => $encoded,
            ]);
            $this->syncConfirmationDetailsFromArancelPayload($id, $data);
        } catch (QueryException $e) {
            report($e);

            return response()->json([
                'ok' => false,
                'message' => 'Could not save. Run database migrations and try again.',
            ], 422);
        }

        return response()->json(['ok' => true, 'message' => 'Arancel record saved.']);
    }

    public function confirmationCertificationDetails(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'confirmation_id' => ['required', 'integer', 'min:1'],
        ]);
        $id = (int) $validated['confirmation_id'];
        $row = DB::table('confirmation')->where('confirmationId', $id)->first();
        if ($row === null) {
            return response()->json(['message' => 'Confirmation record not found.'], 404);
        }

        if (! SacramentApplicationGate::confirmationIsPaymentComplete($id)) {
            return SacramentApplicationGate::paymentDenyResponse();
        }

        $details = $this->latestConfirmationDetailsRow($id);
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
            if (trim((string) ($details->firstName ?? '')) !== '') {
                $data['first_name'] = trim((string) ($details->firstName ?? ''));
            }
            if (trim((string) ($details->middleName ?? '')) !== '') {
                $data['middle_name'] = trim((string) ($details->middleName ?? ''));
            }
            if (trim((string) ($details->familyName ?? '')) !== '') {
                $data['family_name'] = trim((string) ($details->familyName ?? ''));
            }

            $data['date_of_birth'] = $this->dateForForm($details->dateOfBirth ?? null);
            $data['place_of_birth'] = (string) ($details->placeOfBirth ?? '');

            $father = $this->splitFullNameThreeParts($details->fatherName ?? '');
            $data['father_first_name'] = $father['first'];
            $data['father_middle_name'] = $father['middle'];
            $data['father_last_name'] = $father['last'];

            $mother = $this->splitFullNameThreeParts($details->motherMaiden ?? '');
            $data['mother_first_name'] = $mother['first'];
            $data['mother_middle_name'] = $mother['middle'];
            $data['mother_last_name'] = $mother['last'];

            $addrBits = array_values(array_filter(array_map('trim', explode(',', (string) ($details->address ?? ''))), fn ($s) => $s !== ''));
            if (isset($addrBits[0])) {
                $data['barangay'] = $addrBits[0];
            }
            if (isset($addrBits[1])) {
                $data['municipality'] = $addrBits[1];
            }
            if (count($addrBits) > 2) {
                $data['province'] = implode(', ', array_slice($addrBits, 2));
            }

            $data['book_no'] = (string) ($details->bookNo ?? '');
            $data['register_no'] = (string) ($details->registryNo ?? '');
            $data['page_no'] = (string) ($details->pageNo ?? '');
            $data['priest'] = ClientNameDisplay::formatPriestName((string) ($details->confirmationMinister ?? ''));

            $sponsors = array_values(array_filter([
                trim((string) ($details->godparent1 ?? '')),
                trim((string) ($details->godparent2 ?? '')),
                trim((string) ($details->godparent3 ?? '')),
                trim((string) ($details->godparent4 ?? '')),
            ], fn ($s) => $s !== ''));
            $data['sponsors'] = implode('; ', $sponsors);
        }

        return response()->json([
            'ok' => true,
            'data' => $data,
        ]);
    }

    private function decodeConfirmationJsonColumn(object $row, string $column): array
    {
        $raw = $row->{$column} ?? null;
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

    private function applyChristeningScheduleToConfirmationApplicationData(object $confirmation, array &$data): void
    {
        $christening = $this->findChristeningRowMatchingConfirmationClient($confirmation);
        if ($christening === null || empty($christening->scheduleRequested)) {
            return;
        }
        $data['baptism_date'] = $this->dateForForm($christening->scheduleRequested);
    }

    private function findChristeningRowMatchingConfirmationClient(object $confirmation): ?object
    {
        $fn = mb_strtolower(trim((string) ($confirmation->clientFName ?? '')));
        $ln = mb_strtolower(trim((string) ($confirmation->clientLName ?? '')));
        if ($fn === '' || $ln === '') {
            return null;
        }
        $mn = mb_strtolower(trim((string) ($confirmation->clientMName ?? '')));

        $q = DB::table('christening')
            ->whereRaw('LOWER(TRIM(COALESCE(clientFName, ?))) = ?', ['', $fn])
            ->whereRaw('LOWER(TRIM(COALESCE(clientLName, ?))) = ?', ['', $ln]);

        if ($mn !== '') {
            $q->where(function ($sub) use ($mn) {
                $sub->whereRaw('LOWER(TRIM(COALESCE(clientMName, ?))) = ?', ['', $mn])
                    ->orWhereRaw('TRIM(COALESCE(clientMName, ?)) = ?', ['', '']);
            });
        }

        return $q->whereNotNull('scheduleRequested')
            ->orderByDesc('christeningId')
            ->first();
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

    private function confirmationDetailsTableExists(): bool
    {
        return Schema::hasTable('confirmation_details');
    }

    private function latestConfirmationDetailsRow(int $confirmationId): ?object
    {
        if (! $this->confirmationDetailsTableExists()) {
            return null;
        }

        return DB::table('confirmation_details')
            ->where('confirmationId', $confirmationId)
            ->orderByDesc('confirmationDetailsId')
            ->first();
    }

    private function mapConfirmationDetailsRowToApplicationForm(?object $row): array
    {
        $defaults = [
            'first_name' => '',
            'middle_name' => '',
            'family_name' => '',
            'date_of_birth' => '',
            'place_of_birth' => '',
            'father_name' => '',
            'mother_maiden' => '',
            'address' => '',
            'baptism_date' => '',
            'baptism_place' => '',
            'minister_baptism' => '',
            'book_no' => '',
            'page_no' => '',
            'registry_no' => '',
            'confirmation_date' => '',
            'confirmation_minister' => '',
            'godparent_1' => '',
            'godparent_2' => '',
            'godparent_3' => '',
            'godparent_4' => '',
        ];
        if ($row === null) {
            return $defaults;
        }

        $defaults['first_name'] = (string) ($row->firstName ?? '');
        $defaults['middle_name'] = (string) ($row->middleName ?? '');
        $defaults['family_name'] = (string) ($row->familyName ?? '');
        $defaults['date_of_birth'] = $this->dateForForm($row->dateOfBirth ?? null);
        $defaults['place_of_birth'] = (string) ($row->placeOfBirth ?? '');
        $defaults['father_name'] = (string) ($row->fatherName ?? '');
        $defaults['mother_maiden'] = (string) ($row->motherMaiden ?? '');
        $defaults['address'] = ClientNameDisplay::formatAddress((string) ($row->address ?? ''));
        $defaults['baptism_date'] = $this->dateForForm($row->baptismDate ?? null);
        $defaults['baptism_place'] = (string) ($row->baptismPlace ?? '');
        $defaults['minister_baptism'] = ClientNameDisplay::formatPriestName((string) ($row->ministerBaptism ?? ''));
        $defaults['book_no'] = (string) ($row->bookNo ?? '');
        $defaults['page_no'] = (string) ($row->pageNo ?? '');
        $defaults['registry_no'] = (string) ($row->registryNo ?? '');
        $defaults['confirmation_date'] = $this->dateForForm($row->confirmationDate ?? null);
        $defaults['confirmation_minister'] = ClientNameDisplay::formatPriestName((string) ($row->confirmationMinister ?? ''));
        $defaults['godparent_1'] = (string) ($row->godparent1 ?? '');
        $defaults['godparent_2'] = (string) ($row->godparent2 ?? '');
        $defaults['godparent_3'] = (string) ($row->godparent3 ?? '');
        $defaults['godparent_4'] = (string) ($row->godparent4 ?? '');

        return ClientNameDisplay::normalizeApplicationNameFields($defaults);
    }

    private function mapConfirmationDetailsRowToArancelForm(?object $row): array
    {
        $defaults = [
            'amt_arancel' => '',
            'amt_candle' => '',
            'amt_godparents' => '',
            'other_label_1' => '',
            'other_label_2' => '',
            'other_label_3' => '',
            'amt_other_1' => '',
            'amt_other_2' => '',
            'amt_other_3' => '',
            'total_payment' => '',
            'sig_bpc_chairman' => '',
            'sig_parish_secretary' => '',
            'sig_presacramental_instructor' => '',
            'sig_parish_priest' => '',
        ];
        if ($row === null) {
            return $defaults;
        }

        $defaults['amt_arancel'] = $this->decimalForForm($row->feeArancel ?? null);
        $defaults['amt_candle'] = $this->decimalForForm($row->feeCandle ?? null);
        $defaults['amt_godparents'] = $this->decimalForForm($row->feeGodparents ?? null);
        $defaults['other_label_1'] = (string) ($row->otherFeeLabel1 ?? '');
        $defaults['other_label_2'] = (string) ($row->otherFeeLabel2 ?? '');
        $defaults['other_label_3'] = (string) ($row->otherFeeLabel3 ?? '');
        $defaults['amt_other_1'] = $this->decimalForForm($row->otherFeeAmount1 ?? null);
        $defaults['amt_other_2'] = $this->decimalForForm($row->otherFeeAmount2 ?? null);
        $defaults['amt_other_3'] = $this->decimalForForm($row->otherFeeAmount3 ?? null);
        $defaults['total_payment'] = $this->decimalForForm($row->feeTotal ?? null);
        $defaults['sig_bpc_chairman'] = (string) ($row->approvedByBpcChairman ?? '');
        $defaults['sig_parish_secretary'] = (string) ($row->approvedByParishSecretary ?? '');
        $defaults['sig_presacramental_instructor'] = (string) ($row->approvedByPresacramentalInstructor ?? '');
        $defaults['sig_parish_priest'] = (string) ($row->approvedByParishPriest ?? '');

        return $defaults;
    }

    private function overlayNonEmptyJsonFields(array &$target, array $json): void
    {
        foreach ($json as $k => $v) {
            if ($v === null) {
                continue;
            }
            if (is_string($v) && trim($v) === '') {
                continue;
            }
            if (is_array($v)) {
                continue;
            }
            $target[$k] = $v;
        }
    }

    private function applyRegistryClientNamesToConfirmationApplicationData(object $confirmation, array &$data): void
    {
        if (trim((string) ($data['first_name'] ?? '')) === '') {
            $data['first_name'] = trim((string) ($confirmation->clientFName ?? ''));
        }
        if (trim((string) ($data['middle_name'] ?? '')) === '') {
            $data['middle_name'] = trim((string) ($confirmation->clientMName ?? ''));
        }
        if (trim((string) ($data['family_name'] ?? '')) === '') {
            $data['family_name'] = trim((string) ($confirmation->clientLName ?? ''));
        }
    }

    private function syncConfirmationDetailsFromApplicationPayload(int $confirmationId, array $payload): void
    {
        if (! $this->confirmationDetailsTableExists()) {
            return;
        }

        $row = [];
        if (array_key_exists('first_name', $payload)) {
            $row['firstName'] = $this->nullableText($payload['first_name']);
        }
        if (array_key_exists('middle_name', $payload)) {
            $row['middleName'] = $this->nullableText($payload['middle_name']);
        }
        if (array_key_exists('family_name', $payload)) {
            $row['familyName'] = $this->nullableText($payload['family_name']);
        }
        if (array_key_exists('date_of_birth', $payload)) {
            $row['dateOfBirth'] = $this->nullableDateFromForm($payload['date_of_birth']);
        }
        if (array_key_exists('place_of_birth', $payload)) {
            $row['placeOfBirth'] = $this->nullableText($payload['place_of_birth']);
        }
        if (array_key_exists('father_name', $payload)) {
            $row['fatherName'] = $this->nullableText($payload['father_name']);
        }
        if (array_key_exists('mother_maiden', $payload)) {
            $row['motherMaiden'] = $this->nullableText($payload['mother_maiden']);
        }
        if (array_key_exists('address', $payload)) {
            $row['address'] = ClientNameDisplay::nullableFormattedAddress($payload['address']);
        }
        if (array_key_exists('baptism_date', $payload)) {
            $row['baptismDate'] = $this->nullableDateFromForm($payload['baptism_date']);
        }
        if (array_key_exists('baptism_place', $payload)) {
            $row['baptismPlace'] = $this->nullableText($payload['baptism_place']);
        }
        if (array_key_exists('minister_baptism', $payload)) {
            $row['ministerBaptism'] = ClientNameDisplay::nullableFormattedPriest($payload['minister_baptism']);
        }
        if (array_key_exists('book_no', $payload)) {
            $row['bookNo'] = $this->nullableText($payload['book_no']);
        }
        if (array_key_exists('page_no', $payload)) {
            $row['pageNo'] = $this->nullableText($payload['page_no']);
        }
        if (array_key_exists('registry_no', $payload)) {
            $row['registryNo'] = $this->nullableText($payload['registry_no']);
        }
        if (array_key_exists('confirmation_date', $payload)) {
            $row['confirmationDate'] = $this->nullableDateFromForm($payload['confirmation_date']);
        }
        if (array_key_exists('confirmation_minister', $payload)) {
            $row['confirmationMinister'] = ClientNameDisplay::nullableFormattedPriest($payload['confirmation_minister']);
        }
        if (array_key_exists('godparent_1', $payload)) {
            $row['godparent1'] = $this->nullableText($payload['godparent_1']);
        }
        if (array_key_exists('godparent_2', $payload)) {
            $row['godparent2'] = $this->nullableText($payload['godparent_2']);
        }
        if (array_key_exists('godparent_3', $payload)) {
            $row['godparent3'] = $this->nullableText($payload['godparent_3']);
        }
        if (array_key_exists('godparent_4', $payload)) {
            $row['godparent4'] = $this->nullableText($payload['godparent_4']);
        }

        if ($row === []) {
            return;
        }

        $row['updated_at'] = now();
        $existing = DB::table('confirmation_details')->where('confirmationId', $confirmationId)->first();
        if ($existing !== null) {
            DB::table('confirmation_details')
                ->where('confirmationDetailsId', $existing->confirmationDetailsId)
                ->update($row);

            return;
        }

        $row['confirmationId'] = $confirmationId;
        $row['created_at'] = now();
        DB::table('confirmation_details')->insert($row);
    }

    private function syncConfirmationDetailsFromArancelPayload(int $confirmationId, array $payload): void
    {
        if (! $this->confirmationDetailsTableExists()) {
            return;
        }

        $row = [];
        if (array_key_exists('amt_arancel', $payload)) {
            $row['feeArancel'] = $this->nullableDecimalFromForm($payload['amt_arancel']);
        }
        if (array_key_exists('amt_candle', $payload)) {
            $row['feeCandle'] = $this->nullableDecimalFromForm($payload['amt_candle']);
        }
        if (array_key_exists('amt_godparents', $payload)) {
            $row['feeGodparents'] = $this->nullableDecimalFromForm($payload['amt_godparents']);
        }
        if (array_key_exists('other_label_1', $payload)) {
            $row['otherFeeLabel1'] = $this->nullableText($payload['other_label_1']);
        }
        if (array_key_exists('other_label_2', $payload)) {
            $row['otherFeeLabel2'] = $this->nullableText($payload['other_label_2']);
        }
        if (array_key_exists('other_label_3', $payload)) {
            $row['otherFeeLabel3'] = $this->nullableText($payload['other_label_3']);
        }
        if (array_key_exists('amt_other_1', $payload)) {
            $row['otherFeeAmount1'] = $this->nullableDecimalFromForm($payload['amt_other_1']);
        }
        if (array_key_exists('amt_other_2', $payload)) {
            $row['otherFeeAmount2'] = $this->nullableDecimalFromForm($payload['amt_other_2']);
        }
        if (array_key_exists('amt_other_3', $payload)) {
            $row['otherFeeAmount3'] = $this->nullableDecimalFromForm($payload['amt_other_3']);
        }
        if (array_key_exists('total_payment', $payload)) {
            $row['feeTotal'] = $this->nullableDecimalFromForm($payload['total_payment']);
        }
        if (array_key_exists('sig_bpc_chairman', $payload)) {
            $row['approvedByBpcChairman'] = $this->nullableText($payload['sig_bpc_chairman']);
        }
        if (array_key_exists('sig_parish_secretary', $payload)) {
            $row['approvedByParishSecretary'] = $this->nullableText($payload['sig_parish_secretary']);
        }
        if (array_key_exists('sig_presacramental_instructor', $payload)) {
            $row['approvedByPresacramentalInstructor'] = $this->nullableText($payload['sig_presacramental_instructor']);
        }
        if (array_key_exists('sig_parish_priest', $payload)) {
            $row['approvedByParishPriest'] = ClientNameDisplay::nullableFormattedPriest($payload['sig_parish_priest']);
        }

        if ($row === []) {
            return;
        }

        $row['updated_at'] = now();
        $existing = DB::table('confirmation_details')->where('confirmationId', $confirmationId)->first();
        if ($existing !== null) {
            DB::table('confirmation_details')
                ->where('confirmationDetailsId', $existing->confirmationDetailsId)
                ->update($row);

            return;
        }

        $row['confirmationId'] = $confirmationId;
        $row['created_at'] = now();
        DB::table('confirmation_details')->insert($row);
    }

    private function nullableDateFromForm(mixed $value): ?string
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

    private function splitFullNameThreeParts(mixed $raw): array
    {
        $raw = trim((string) ($raw ?? ''));
        if ($raw === '') {
            return ['first' => '', 'middle' => '', 'last' => ''];
        }
        $parts = preg_split('/\s+/', $raw) ?: [];
        if ($parts === []) {
            return ['first' => '', 'middle' => '', 'last' => ''];
        }
        if (count($parts) === 1) {
            return ['first' => $parts[0], 'middle' => '', 'last' => ''];
        }
        if (count($parts) === 2) {
            return ['first' => $parts[0], 'middle' => '', 'last' => $parts[1]];
        }

        $first = array_shift($parts);
        $last = array_pop($parts);

        return [
            'first' => (string) $first,
            'middle' => implode(' ', $parts),
            'last' => (string) $last,
        ];
    }

    private function nullableDecimalFromForm(mixed $value): ?float
    {
        $s = str_replace(',', '', trim((string) ($value ?? '')));
        if ($s === '') {
            return null;
        }
        if (! is_numeric($s)) {
            return null;
        }

        return round((float) $s, 2);
    }

    private function defaultConfirmationPaymentFeeRows(): array
    {
        return [
            ['label' => 'Arancel (By Appointment)', 'paid' => false, 'date_paid' => null],
            ['label' => 'Candle', 'paid' => false, 'date_paid' => null],
            ['label' => 'Maninoy kag Maninay', 'paid' => false, 'date_paid' => null],
            ['label' => 'Seminar', 'paid' => false, 'date_paid' => null],
            ['label' => 'Others:', 'paid' => false, 'date_paid' => null],
        ];
    }

    private function mapConfirmationRowToPaymentFormFields(object $row): array
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
            $feeRows = $this->defaultConfirmationPaymentFeeRows();
        } else {
            $feeRows = $this->normalizeConfirmationPaymentFeeRowsFromStorage($feeRows);
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

    private function normalizeConfirmationPaymentFeeRowsFromStorage(array $rows): array
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

        return $out === [] ? $this->defaultConfirmationPaymentFeeRows() : $out;
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
     * @return array{0:int,1:string}
     */
    private function createConfirmationRegistryFromApplicationNames(
        string $firstName,
        ?string $middleName,
        string $familyName,
        mixed $address = null
    ): array {
        return DB::transaction(function () use ($firstName, $middleName, $familyName, $address) {
            $user = Auth::user();
            $customerId = DB::table('customer')->insertGetId(array_filter([
                'customerFName' => $firstName,
                'customerMName' => $middleName,
                'customerLName' => $familyName,
                'updatedAt' => now(),
                'createdBy' => $user?->userName ?? $user?->userfName ?? null,
                'userId' => $user?->getAuthIdentifier(),
            ], fn ($v) => $v !== null));

            $ref = $this->generateUniqueConfirmationReferenceCode();
            $insertData = [
                'referenceCode' => $ref,
                'clientFName' => $firstName,
                'clientMName' => $middleName,
                'clientLName' => $familyName,
                'address' => ClientNameDisplay::nullableFormattedAddress($address),
                'paymentStatus' => 'Unpaid',
                'dateCreated' => now(),
                'customerId' => $customerId,
            ];
            if (Schema::hasColumn('confirmation', 'workflowStep')) {
                $insertData['workflowStep'] = 'application';
            }
            $insertData = array_filter($insertData, fn ($v) => $v !== null);
            $id = (int) DB::table('confirmation')->insertGetId($insertData);

            return [$id, $ref];
        });
    }

    private function ensureConfirmationReferenceCode(int $confirmationId): string
    {
        return SacramentReferenceCode::ensureOnRegistryRow(
            'confirmation',
            'confirmationId',
            $confirmationId,
            fn () => $this->generateUniqueConfirmationReferenceCode()
        );
    }
}
