<?php

namespace App\Http\Controllers;

use App\Support\ClientNameDisplay;
use App\Support\DocumentationApplicationReportWriter;
use App\Support\SacramentApplicationGate;
use App\Support\SacramentReferenceCode;
use App\Support\SacramentRegistrySectionFilter;
use App\Support\SacramentScheduleReservedDates;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class ChristeningController extends Controller
{
    private const CHRISTENING_GODPARENT_FORM_ROWS = 26;

    private const CHRISTENING_FIXED_BAPTISM_PLACE = 'Saint Anthony of Padua Parish Church';

    private const CHRISTENING_REFERENCE_SUFFIX = 'B';

    private const CHRISTENING_REFERENCE_CHARSET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    private const DEFAULT_CERT_PURPOSE = 'For all legal purposes';

    public function index(Request $request)
    {
        return redirect()->route('admin.christening.application', $request->query());
    }

    public function scheduleIndex(Request $request): View
    {
        return view('christening.view.schedule', $this->christeningSectionViewData($request, SacramentRegistrySectionFilter::SECTION_SCHEDULE));
    }

    public function certificationIndex(Request $request): View
    {
        return view('christening.view.certification', $this->christeningSectionViewData($request, SacramentRegistrySectionFilter::SECTION_CERTIFICATION));
    }

    public function paymentIndex(Request $request): View
    {
        return view('christening.view.payment', $this->christeningSectionViewData($request, SacramentRegistrySectionFilter::SECTION_PAYMENT));
    }

    public function applicationIndex(Request $request): View
    {
        return view('christening.view.application-form', $this->christeningSectionViewData($request, SacramentRegistrySectionFilter::SECTION_APPLICATION));
    }

    public function nextChristeningReferenceCode(): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'reference_code' => $this->generateUniqueChristeningReferenceCode(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function christeningSectionViewData(Request $request, string $section): array
    {
        $request->merge([
            'registry_type' => 'christening',
            'registry_section' => $section,
        ]);

        $dashboard = app(DashboardController::class);
        $data = $dashboard->registryIndexData($request);

        return [
            'records' => $data['records'],
            'initialTablePayload' => $data['initialTablePayload'],
            'perPageOptions' => DashboardController::perPageOptionsList(),
            'letterOptions' => range('A', 'Z'),
            'generatedReferenceCode' => $this->generateUniqueChristeningReferenceCode(),
            'defaultPaymentFeeRows' => $this->defaultChristeningPaymentFeeRows(),
        ];
    }

    public function certificationPage(Request $request): View
    {
        $certReportMonth = $this->resolveCertificationReportMonth($request->input('month'));
        $certReportLabel = ClientNameDisplay::formatMonthYearLabel($certReportMonth);

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
        $reportLabel = ClientNameDisplay::formatMonthYearLabel($month);

        return view('certification.view.certificationReportWindow', [
            'reportType' => $reportType,
            'reportMonth' => $month,
            'reportLabel' => $reportLabel,
            'serviceHeading' => $reportType === 'wedding' ? 'WEDDING' : 'CHRISTENING',
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
        $reportLabel = ClientNameDisplay::formatMonthYearLabel($month);
        $serviceHeading = $reportType === 'wedding' ? 'WEDDING' : 'CHRISTENING';

        return response()->json([
            'ok' => true,
            'rows' => $out,
            'report_type' => $reportType,
            'month' => $month,
            'report_label' => $reportLabel,
            'service_heading' => $serviceHeading,
        ]);
    }

    private function buildCertificationRowsFromDetailsTable(string $reportType, ?string $monthYm = null): array
    {
        $rowsQuery = DB::table('certification_details')
            ->orderByDesc('created_at')
            ->orderByDesc('certificationDetailsId');

        if ($reportType !== '') {
            $registryType = match ($reportType) {
                'christening' => 'Christening',
                'wedding' => 'Wedding',
                default => null,
            };
            $suffixMap = [
                'christening' => '-'.self::CHRISTENING_REFERENCE_SUFFIX,
                'wedding' => '-W',
            ];
            $suffix = $suffixMap[$reportType] ?? '';

            $rowsQuery->where(function (Builder $match) use ($registryType, $suffix) {
                if ($registryType !== null) {
                    $match->where('registryType', $registryType);
                }
                if ($suffix !== '') {
                    $match->orWhere('referenceCode', 'like', '%'.$suffix);
                }
            });
        }

        $resolvedMonth = $monthYm !== null && $monthYm !== '' ? $this->resolveCertificationReportMonth($monthYm) : null;
        if ($resolvedMonth !== null) {
            $bounds = ClientNameDisplay::monthBoundsUtcForDisplayTimezone($resolvedMonth);
            if ($bounds !== null) {
                [$startUtc, $endUtc] = $bounds;
                $rowsQuery->whereBetween('created_at', [
                    $startUtc->format('Y-m-d H:i:s'),
                    $endUtc->format('Y-m-d H:i:s'),
                ]);
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
                'date' => ClientNameDisplay::formatDateTimeCreated($row->created_at ?? $row->date ?? null),
            ];
        }

        return $out;
    }

    private function resolveCertificationReportMonth(?string $value): string
    {
        $tz = 'Asia/Taipei';

        if ($value === null || trim((string) $value) === '') {
            return now($tz)->format('Y-m');
        }

        $value = trim((string) $value);
        if (preg_match('/^(\d{4})-(\d{1,2})$/', $value, $parts)) {
            return sprintf('%04d-%02d', (int) $parts[1], (int) $parts[2]);
        }

        try {
            return Carbon::createFromFormat('Y-m', $value, $tz)->format('Y-m');
        } catch (\Throwable) {
            return now($tz)->format('Y-m');
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
                    if (Schema::hasColumn('christening', 'scheduleCompletedAt')) {
                        $insertData['scheduleCompletedAt'] = now();
                    }
                    if (Schema::hasColumn('christening', 'workflowStep')) {
                        $insertData['workflowStep'] = SacramentRegistrySectionFilter::SECTION_SCHEDULE;
                    }
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

        $existingChristeningId = (int) $existing->christeningId;
        if (! SacramentApplicationGate::christeningIsCertificationSaved($existingChristeningId)) {
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
        if (Schema::hasColumn('christening', 'scheduleCompletedAt')) {
            $updateData['scheduleCompletedAt'] = now();
        }
        if (Schema::hasColumn('christening', 'workflowStep')) {
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

        if (! SacramentApplicationGate::christeningIsCertificationSaved($christeningId)) {
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
                'christening_id' => $christeningId,
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

    public function christeningApplicationForm(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'christening_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $firstName = ClientNameDisplay::capitalizeNamePart((string) $request->input('first_name', ''));
        $familyName = ClientNameDisplay::titleCaseNamePart((string) $request->input('family_name', ''));
        if ($firstName === '' || $familyName === '') {
            return response()->json([
                'ok' => false,
                'message' => 'Please enter the child\'s first name and last name on the application form.',
                'errors' => [
                    'first_name' => ['First and last name are required.'],
                ],
            ], 422);
        }

        $middleName = ClientNameDisplay::capitalizeNamePart((string) $request->input('middle_name', ''));
        $christeningId = isset($validated['christening_id']) ? (int) $validated['christening_id'] : 0;
        $created = false;
        $referenceCode = null;

        if ($christeningId <= 0) {
            try {
                [$christeningId, $referenceCode] = DB::transaction(function () use ($request, $firstName, $middleName, $familyName) {
                    $user = Auth::user();
                    $customerRow = [
                        'customerFName' => $firstName,
                        'customerMName' => $middleName !== '' ? $middleName : null,
                        'customerLName' => $familyName,
                        'updatedAt' => now(),
                        'createdBy' => $user?->userName ?? $user?->userfName ?? null,
                        'userId' => $user?->getAuthIdentifier(),
                    ];
                    $customerRow = array_filter($customerRow, fn ($v) => $v !== null);
                    $customerId = DB::table('customer')->insertGetId($customerRow);

                    $ref = $this->generateUniqueChristeningReferenceCode();
                    $insertData = [
                        'referenceCode' => $ref,
                        'clientFName' => $firstName,
                        'clientMName' => $middleName !== '' ? $middleName : null,
                        'clientLName' => $familyName,
                        'contactNum' => $this->nullableText($request->input('guardian_contact')),
                        'address' => ClientNameDisplay::nullableFormattedAddress($request->input('parent_address')),
                        'paymentStatus' => 'Unpaid',
                        'dateCreated' => now(),
                        'customerId' => $customerId,
                    ];
                    if (Schema::hasColumn('christening', 'workflowStep')) {
                        $insertData['workflowStep'] = 'application';
                    }
                    $insertData = array_filter($insertData, fn ($v) => $v !== null);

                    $newId = (int) DB::table('christening')->insertGetId($insertData);

                    return [$newId, $ref];
                });
                $created = true;
            } catch (QueryException $e) {
                report($e);

                return response()->json([
                    'ok' => false,
                    'message' => 'Could not create the christening record. Please try again.',
                ], 422);
            }
        } elseif (! DB::table('christening')->where('christeningId', $christeningId)->exists()) {
            return response()->json(['message' => 'Christening record not found.'], 404);
        }

        $row = $this->mapApplicationRequestToDetailsRow($request);

        try {
            $detailsId = DB::transaction(function () use ($christeningId, $row, $firstName, $middleName, $familyName, $request) {
                try {
                    $existing = DB::table('christening_details')
                        ->where('christeningId', $christeningId)
                        ->orderByDesc('christeningDetailsId')
                        ->first();
                } catch (QueryException $e) {
                    if ($this->isCorruptedChristeningDetailsIndex($e)) {
                        throw new \RuntimeException('corrupted_christening_details_index');
                    }

                    throw $e;
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

                $headerUpdate = [
                    'clientFName' => $firstName,
                    'clientMName' => $middleName !== '' ? $middleName : null,
                    'clientLName' => $familyName,
                    'contactNum' => $this->nullableText($request->input('guardian_contact')),
                    'address' => ClientNameDisplay::nullableFormattedAddress($request->input('parent_address')),
                ];
                if (Schema::hasColumn('christening', 'applicationCompletedAt')) {
                    $headerUpdate['applicationCompletedAt'] = now();
                }
                if (Schema::hasColumn('christening', 'workflowStep')) {
                    $headerUpdate['workflowStep'] = SacramentRegistrySectionFilter::SECTION_APPLICATION;
                }
                $headerUpdate = array_filter($headerUpdate, fn ($v) => $v !== null);
                DB::table('christening')->where('christeningId', $christeningId)->update($headerUpdate);

                return $detailsId;
            });
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'corrupted_christening_details_index') {
                return response()->json([
                    'ok' => false,
                    'message' => 'The christening details index appears corrupted. Please repair the table and try again.',
                ], 422);
            }

            throw $e;
        } catch (QueryException $e) {
            report($e);

            return response()->json([
                'ok' => false,
                'message' => 'Could not save the application. Please try again.',
            ], 422);
        }

        DocumentationApplicationReportWriter::syncChristening(
            $christeningId,
            $row['firstName'] ?? null,
            $row['middleName'] ?? null,
            $row['familyName'] ?? null,
        );

        if ($referenceCode === null) {
            $header = DB::table('christening')->where('christeningId', $christeningId)->first();
            $referenceCode = $header?->referenceCode ?? null;
        }

        return response()->json([
            'ok' => true,
            'message' => $created ? 'New christening record and application saved.' : 'Application saved.',
            'created' => $created,
            'data' => [
                'christening_id' => $christeningId,
                'christening_details_id' => $detailsId,
                'reference_code' => $referenceCode,
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
            'application_saved' => SacramentApplicationGate::christeningIsSaved($christeningId),
            'data' => ClientNameDisplay::normalizeApplicationNameFields($data),
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
            'address' => ClientNameDisplay::formatAddress((string) ($row->address ?? '')),
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

        $this->ensureChristeningReferenceCode($christeningId);
        $row = DB::table('christening')->where('christeningId', $christeningId)->first();

        return response()->json([
            'ok' => true,
            'payment_complete' => SacramentApplicationGate::christeningIsPaymentComplete($christeningId),
            'data' => $this->mapChristeningRowToPaymentFormFields($row),
        ]);
    }

    public function christeningPaymentSave(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'christening_id' => ['nullable', 'integer', 'min:1'],
            'reference_code' => ['nullable', 'string', 'max:255'],
            'client' => ['nullable', 'string', 'max:255'],
            'contact_number' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'fee_rows' => ['nullable', 'array'],
            'fee_rows.*.label' => ['nullable', 'string', 'max:500'],
            'fee_rows.*.paid' => ['nullable', 'boolean'],
            'fee_rows.*.date_paid' => ['nullable', 'string', 'max:50'],
        ]);

        $christeningId = ! empty($validated['christening_id']) ? (int) $validated['christening_id'] : 0;
        $ref = trim((string) ($validated['reference_code'] ?? ''));
        if ($ref === '' && $christeningId <= 0) {
            $ref = $this->generateUniqueChristeningReferenceCode();
        }

        $existing = null;
        if ($christeningId > 0) {
            $existing = DB::table('christening')->where('christeningId', $christeningId)->first();
            if ($existing === null) {
                return response()->json(['message' => 'Christening record not found.'], 404);
            }
        } elseif ($ref !== '') {
            $existing = DB::table('christening')->where('referenceCode', $ref)->first();
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
        if (Schema::hasColumn('christening', 'paymentCompletedAt')) {
            $update['paymentCompletedAt'] = now();
        }
        if (Schema::hasColumn('christening', 'workflowStep')) {
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
                $christeningId = DB::transaction(function () use ($ref, $first, $middle, $last, $validated, $update) {
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
                    if (Schema::hasColumn('christening', 'workflowStep')) {
                        $insertData['workflowStep'] = SacramentRegistrySectionFilter::SECTION_PAYMENT;
                    }
                    if (Schema::hasColumn('christening', 'paymentCompletedAt')) {
                        $insertData['paymentCompletedAt'] = now();
                    }
                    $insertData = array_filter($insertData, fn ($v) => $v !== null);

                    return (int) DB::table('christening')->insertGetId($insertData);
                });
            } catch (QueryException $e) {
                report($e);

                return response()->json([
                    'ok' => false,
                    'message' => 'Could not save payment details. If this persists, run database migrations and try again.',
                ], 422);
            }
        } else {
            $christeningId = (int) $existing->christeningId;
            if ($ref === '') {
                $ref = (string) ($existing->referenceCode ?? '');
            }

            try {
                DB::table('christening')->where('christeningId', $christeningId)->update($update);
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
                'christening_id' => $christeningId,
                'reference_code' => $ref,
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
        $byDate = SacramentScheduleReservedDates::forMonth($year, $month, 'christening');

        return response()->json([
            'ok' => true,
            'by_date' => $byDate,
            'dates' => array_keys($byDate),
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
            'marriage_date' => '',
            'marriage_place' => '',
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
        [$out['marriage_date'], $out['marriage_place']] = $this->marriageDatePlaceForForm($row);
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

        return ClientNameDisplay::normalizeApplicationNameFields($out);
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
        $marriageFields = $this->mapMarriageDatePlaceToDetailsColumns(
            $parentStatus,
            $request->input('marriage_date'),
            $request->input('marriage_place')
        );

        $godparents = [];
        for ($i = 1; $i <= self::CHRISTENING_GODPARENT_FORM_ROWS; $i++) {
            $a = $request->input("godparent_{$i}a");
            $b = $request->input("godparent_{$i}b");
            $a = ClientNameDisplay::nullableFormattedFamilyName(is_string($a) ? trim($a) : '');
            $b = ClientNameDisplay::nullableFormattedFamilyName(is_string($b) ? trim($b) : '');
            if ($a !== null || $b !== null) {
                $godparents[] = ['maninoy' => $a ?? '', 'maninay' => $b ?? ''];
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
            'firstName' => ClientNameDisplay::nullableFormattedNamePart($request->input('first_name')),
            'middleName' => ClientNameDisplay::nullableFormattedNamePart($request->input('middle_name')),
            'familyName' => ClientNameDisplay::nullableFormattedFamilyName($request->input('family_name')),
            'dateOfBirth' => $dob,
            'birthRegistryNumber' => $this->nullableText($request->input('registry_number')),
            'placeOfBirth' => $this->nullableText($request->input('place_of_birth')),
            'fatherName' => ClientNameDisplay::nullableFormattedFamilyName($request->input('father_name')),
            'motherMaidenName' => ClientNameDisplay::nullableFormattedFamilyName($request->input('mother_maiden_name')),
            'parentAddress' => ClientNameDisplay::nullableFormattedAddress($request->input('parent_address')),
            'parentStatus' => $parentStatusText !== '' ? $parentStatusText : null,
            'civillyMarriedDate' => $marriageFields['civillyMarriedDate'],
            'civillyMarriedPlace' => $marriageFields['civillyMarriedPlace'],
            'marriedOtherDenominationDate' => $marriageFields['marriedOtherDenominationDate'],
            'marriedOtherDenominationPlace' => $marriageFields['marriedOtherDenominationPlace'],
            'churchMarriageDate' => $marriageFields['churchMarriageDate'],
            'churchMarriagePlace' => $marriageFields['churchMarriagePlace'],
            'marriageContractNumber' => $this->nullableText($request->input('marriage_contract_no')),
            'parentGuardianContact' => $this->nullableText($request->input('guardian_contact')),
            'dateOfBaptism' => $this->parseFlexibleDate($request->input('baptism_date')),
            'placeOfBaptism' => self::CHRISTENING_FIXED_BAPTISM_PLACE,
            'ministerOfSacrament' => ClientNameDisplay::nullableFormattedPriest($request->input('minister')),
            'age' => $age,
            'feeArancel' => $this->nullableDecimal($request->input('fee_arancel')),
            'feeBaptismalSymbols' => $this->nullableDecimal($request->input('fee_symbols')),
            'feeGodparents' => $this->nullableDecimal($request->input('fee_godparents')),
            'feeParentsSeminar' => $this->nullableDecimal($request->input('fee_seminar')),
            'feeOthers' => $this->nullableDecimal($request->input('fee_others')),
            'feeTotal' => $this->nullableDecimal($request->input('fee_total')),
            'godparents' => count($godparents) ? json_encode($godparents, JSON_UNESCAPED_UNICODE) : null,
            'approvedByBpcChairman' => ClientNameDisplay::nullableFormattedFamilyName($request->input('approval_bpc_chairman')),
            'approvedByPreJordanInstructor' => ClientNameDisplay::nullableFormattedFamilyName($request->input('approval_prejordan_instructor')),
            'approvedByParishSecretary' => ClientNameDisplay::nullableFormattedFamilyName($request->input('approval_parish_secretary')),
            'approvedByParishPriest' => ClientNameDisplay::nullableFormattedPriest($request->input('approval_parish_priest')),
        ];
    }

    private function marriageDatePlaceForForm(object $row): array
    {
        $pairs = [
            [$row->civillyMarriedDate ?? null, $row->civillyMarriedPlace ?? null],
            [$row->marriedOtherDenominationDate ?? null, $row->marriedOtherDenominationPlace ?? null],
            [$row->churchMarriageDate ?? null, $row->churchMarriagePlace ?? null],
        ];

        foreach ($pairs as [$date, $place]) {
            $placeText = trim((string) $place);
            if ($date !== null && trim((string) $date) !== '') {
                return [$this->dateForForm($date), $placeText];
            }
            if ($placeText !== '') {
                return ['', $placeText];
            }
        }

        return ['', ''];
    }

    /**
     * @param  array<int, string>  $parentStatus
     * @return array<string, mixed>
     */
    private function mapMarriageDatePlaceToDetailsColumns(array $parentStatus, mixed $dateInput, mixed $placeInput): array
    {
        $empty = [
            'civillyMarriedDate' => null,
            'civillyMarriedPlace' => null,
            'marriedOtherDenominationDate' => null,
            'marriedOtherDenominationPlace' => null,
            'churchMarriageDate' => null,
            'churchMarriagePlace' => null,
        ];

        $marriageDate = $this->parseFlexibleDate($dateInput);
        $marriagePlace = $this->nullableText($placeInput);
        if ($marriageDate === null && $marriagePlace === null) {
            return $empty;
        }

        $statusKeys = array_map('strval', $parentStatus);
        $target = null;
        if (in_array('civilly_married', $statusKeys, true)) {
            $target = 'civilly';
        } elseif (in_array('married_other', $statusKeys, true)) {
            $target = 'other';
        } elseif (in_array('church_marriage', $statusKeys, true)) {
            $target = 'church';
        } else {
            $target = 'civilly';
        }

        return match ($target) {
            'other' => array_merge($empty, [
                'marriedOtherDenominationDate' => $marriageDate,
                'marriedOtherDenominationPlace' => $marriagePlace,
            ]),
            'church' => array_merge($empty, [
                'churchMarriageDate' => $marriageDate,
                'churchMarriagePlace' => $marriagePlace,
            ]),
            default => array_merge($empty, [
                'civillyMarriedDate' => $marriageDate,
                'civillyMarriedPlace' => $marriagePlace,
            ]),
        };
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
            'christening_id' => ['nullable', 'integer', 'min:1'],
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

        $christeningId = ! empty($validated['christening_id']) ? (int) $validated['christening_id'] : 0;
        $ref = trim((string) ($validated['reference_code'] ?? ''));
        if ($ref === '' && $christeningId <= 0) {
            $ref = $this->generateUniqueChristeningReferenceCode();
        }

        $christening = null;
        if ($christeningId > 0) {
            $christening = DB::table('christening')->where('christeningId', $christeningId)->first();
            if ($christening === null) {
                return response()->json(['message' => 'Christening record not found.'], 404);
            }
        } elseif ($ref !== '') {
            $christening = DB::table('christening')->where('referenceCode', $ref)->first();
            if ($christening !== null) {
                $christeningId = (int) $christening->christeningId;
            }
        }

        if ($christening === null) {
            $clientTrim = trim((string) ($validated['client'] ?? ''));
            $first = trim((string) ($validated['child_first_name'] ?? ''));
            $middle = trim((string) ($validated['child_middle_name'] ?? ''));
            $last = trim((string) ($validated['child_last_name'] ?? ''));

            if ($clientTrim !== '') {
                $parts = preg_split('/\s+/', $clientTrim) ?: [];
                $first = $parts[0] ?? $first;
                $last = count($parts) > 1 ? array_pop($parts) : $last;
                $middle = count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : $middle;
            }

            if ($first === '' || $last === '') {
                return response()->json([
                    'ok' => false,
                    'message' => 'Please enter the complete name (first name and last name) before saving certification.',
                    'errors' => [
                        'child_last_name' => ['Enter at least first name and last name.'],
                    ],
                ], 422);
            }

            try {
                $christeningId = DB::transaction(function () use ($ref, $first, $middle, $last, $validated) {
                    $user = Auth::user();
                    $customerRow = [
                        'customerFName' => $first,
                        'customerMName' => $middle !== '' ? $middle : null,
                        'customerLName' => $last,
                        'updatedAt' => now(),
                        'createdBy' => $user?->userName ?? $user?->userfName ?? null,
                        'userId' => $user?->getAuthIdentifier(),
                    ];
                    $customerRow = array_filter($customerRow, fn ($v) => $v !== null);
                    $customerId = DB::table('customer')->insertGetId($customerRow);

                    $insertData = [
                        'referenceCode' => $ref,
                        'clientFName' => $first,
                        'clientMName' => $middle !== '' ? $middle : null,
                        'clientLName' => $last,
                        'contactNum' => $this->nullableText($validated['contact_number'] ?? null),
                        'address' => ClientNameDisplay::nullableFormattedAddress($validated['top_address'] ?? null),
                        'dateCreated' => now(),
                        'customerId' => $customerId,
                    ];
                    if (Schema::hasColumn('christening', 'workflowStep')) {
                        $insertData['workflowStep'] = SacramentRegistrySectionFilter::SECTION_CERTIFICATION;
                    }
                    $insertData = array_filter($insertData, fn ($v) => $v !== null);

                    return (int) DB::table('christening')->insertGetId($insertData);
                });
            } catch (QueryException $e) {
                report($e);

                return response()->json([
                    'ok' => false,
                    'message' => 'Could not create christening record for certification. If this persists, run database migrations and try again.',
                ], 422);
            }

            $christening = DB::table('christening')->where('christeningId', $christeningId)->first();
            if ($christening === null) {
                return response()->json(['message' => 'Christening record not found.'], 404);
            }
        }

        $christeningId = (int) $christening->christeningId;

        $this->ensureChristeningReferenceCode($christeningId);
        $christening = DB::table('christening')->where('christeningId', $christeningId)->first();

        $certRow = $this->mapCertificationRequestToCertificationTableRow($request);
        $certificationDetailsRow = $this->mapCertificationRequestToCertificationDetailsRow($request, $christening);

        try {
            DB::transaction(function () use ($christeningId, $certRow, $certificationDetailsRow, $request) {
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

                $headerUpdate = [
                    'contactNum' => $this->nullableText($request->input('contact_number')),
                    'address' => ClientNameDisplay::nullableFormattedAddress($request->input('top_address')),
                ];
                if (Schema::hasColumn('christening', 'certificationCompletedAt')) {
                    $headerUpdate['certificationCompletedAt'] = now();
                }
                if (Schema::hasColumn('christening', 'workflowStep')) {
                    $headerUpdate['workflowStep'] = SacramentRegistrySectionFilter::SECTION_CERTIFICATION;
                }
                DB::table('christening')->where('christeningId', $christeningId)->update($headerUpdate);
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

        $overlay['purpose'] = trim((string) ($overlay['purpose'] ?? '')) !== ''
            ? trim((string) $overlay['purpose'])
            : self::DEFAULT_CERT_PURPOSE;

        return response()->json([
            'ok' => true,
            'has_saved_cert' => $certRow !== null,
            'certification_saved' => SacramentApplicationGate::christeningIsCertificationSaved($christeningId),
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
            'minister' => ClientNameDisplay::formatPriestName((string) ($app['minister'] ?? '')),
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
            'purpose' => self::DEFAULT_CERT_PURPOSE,
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
            'minister' => ClientNameDisplay::formatPriestName((string) ($row->priest ?? '')),
            'barangay' => ClientNameDisplay::formatAddress((string) ($row->addressBarangay ?? '')),
            'municipality' => ClientNameDisplay::formatAddress((string) ($row->addressMunicipality ?? '')),
            'province' => ClientNameDisplay::formatAddress((string) ($row->addressProvince ?? '')),
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
            'addressBarangay' => ClientNameDisplay::nullableFormattedAddress($request->input('barangay')),
            'addressMunicipality' => ClientNameDisplay::nullableFormattedAddress($request->input('municipality')),
            'addressProvince' => ClientNameDisplay::nullableFormattedAddress($request->input('province')),
            'certDateReceived' => $dateReceived,
            'certDateIssued' => $dateIssued,
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
    private function mapCertificationRequestToCertificationDetailsRow(Request $request, object $christening): array
    {
        $resolvedReferenceCode = trim((string) ($request->input('reference_code') ?? ''));
        if ($resolvedReferenceCode === '') {
            $resolvedReferenceCode = trim((string) ($christening->referenceCode ?? ''));
        }
        if ($resolvedReferenceCode === '') {
            $resolvedReferenceCode = $this->ensureChristeningReferenceCode((int) ($christening->christeningId ?? 0));
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
            'registryType' => 'Christening',
            'registryRecordId' => (int) ($christening->christeningId ?? 0),
            'referenceCode' => $this->nullableText($resolvedReferenceCode),
            'client' => $this->nullableText($resolvedClient),
            'address' => ClientNameDisplay::nullableFormattedAddress($resolvedAddress),
            'sex' => $this->nullableText($resolvedSex),
            'contactNumber' => $this->nullableText($resolvedContact),
            'date' => $resolvedDate,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    private function ensureChristeningReferenceCode(int $christeningId): string
    {
        return SacramentReferenceCode::ensureOnRegistryRow(
            'christening',
            'christeningId',
            $christeningId,
            fn () => $this->generateUniqueChristeningReferenceCode()
        );
    }
}
