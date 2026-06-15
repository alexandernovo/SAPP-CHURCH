<?php

namespace App\Http\Controllers;

use App\Models\DocumentationApplicationReport;
use App\Support\ClientNameDisplay;
use App\Support\DocumentationApplicationReportWriter;
use App\Support\SacramentRegistrySectionFilter;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Illuminate\Database\QueryException;

class DashboardController extends Controller
{

    private const PER_PAGE_OPTIONS = [10, 25, 50, 100];


    private const LETTER_FILTER_END = 'Z';

    private static function letterFilterOptions(): array
    {
        return range('A', self::LETTER_FILTER_END);
    }

    private static function searchLikePattern(string $term): string
    {
        $t = mb_strtolower(trim($term));
        if ($t === '') {
            return '%%';
        }
        $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $t);

        return '%'.$escaped.'%';
    }


    public function index(Request $request): View
    {
        $rawPerPage = (int) $request->input('per_page', 10);
        $perPage = in_array($rawPerPage, self::PER_PAGE_OPTIONS, true) ? $rawPerPage : self::PER_PAGE_OPTIONS[0];

        $records = $this->filteredRegistryBaseQuery($request)
            ->paginate($perPage)
            ->withQueryString()
            ->through(function ($row) {
                $r = (object) (array) $row;
                $r->displayClient = ClientNameDisplay::fullDisplayName(
                    $r->clientFName ?? null,
                    $r->clientMName ?? null,
                    $r->clientLName ?? null
                ) ?: '—';
                $r->displayDateCreated = ClientNameDisplay::formatDateCreated($r->dateCreated ?? null);

                return $r;
            });

        $statsYear = (int) date('Y');
        $yearStart = max(2000, $statsYear - 10);
        $yearEnd = $statsYear + 1;
        $statsYearOptions = range($yearStart, $yearEnd);
        rsort($statsYearOptions);

        return view('dashboard.view.sappcDashboard', [
            'records' => $records,
            'initialTablePayload' => $this->tablePayloadFromPaginator($records),
            'statsYear' => $statsYear,
            'statsYearOptions' => $statsYearOptions,
            'perPageOptions' => self::PER_PAGE_OPTIONS,
            'letterOptions' => self::letterFilterOptions(),
            'stats' => [
                'christening' => (int) DB::table('christening')->whereYear('dateCreated', $statsYear)->count(),
                'confirmation' => (int) DB::table('confirmation')->whereYear('dateCreated', $statsYear)->count(),
                'wedding' => (int) DB::table('wedding')->whereYear('dateCreated', $statsYear)->count(),
                'burial' => (int) DB::table('burial')->whereYear('dateCreated', $statsYear)->count(),
            ],
        ]);
    }

    public static function perPageOptionsList(): array
    {
        return self::PER_PAGE_OPTIONS;
    }

    public function registryIndexData(Request $request): array
    {
        $rawPerPage = (int) $request->input('per_page', 10);
        $perPage = in_array($rawPerPage, self::PER_PAGE_OPTIONS, true) ? $rawPerPage : self::PER_PAGE_OPTIONS[0];

        $records = $this->filteredRegistryBaseQuery($request)
            ->paginate($perPage)
            ->withQueryString()
            ->through(function ($row) {
                $r = (object) (array) $row;
                $r->displayClient = ClientNameDisplay::fullDisplayName(
                    $r->clientFName ?? null,
                    $r->clientMName ?? null,
                    $r->clientLName ?? null
                ) ?: '—';
                $r->displayDateCreated = ClientNameDisplay::formatDateCreated($r->dateCreated ?? null);

                return $r;
            });

        return [
            'records' => $records,
            'initialTablePayload' => $this->tablePayloadFromPaginator($records),
        ];
    }

    public function monthlyStats(Request $request): JsonResponse
    {
        $type = strtolower((string) $request->query('type', 'all'));
        $registryTables = ['christening', 'confirmation', 'wedding', 'burial'];

        $year = (int) $request->query('year', date('Y'));
        if ($year < 2000 || $year > 2100) {
            return response()->json(['message' => 'Invalid year.'], 422);
        }

        $short = ['JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC'];

        if ($type === 'all' || $type === '') {
            $byMonth = array_fill(1, 12, 0);
            foreach ($registryTables as $table) {
                $chunk = DB::table($table)
                    ->whereYear('dateCreated', $year)
                    ->selectRaw('MONTH(dateCreated) as m, COUNT(*) as c')
                    ->groupBy('m')
                    ->orderBy('m')
                    ->pluck('c', 'm')
                    ->all();
                for ($i = 1; $i <= 12; $i++) {
                    $key = (string) $i;
                    $byMonth[$i] += (int) ($chunk[$i] ?? $chunk[$key] ?? 0);
                }
            }
            $type = 'all';
        } else {
            $table = match ($type) {
                'christening' => 'christening',
                'confirmation' => 'confirmation',
                'wedding' => 'wedding',
                'burial' => 'burial',
                default => null,
            };

            if ($table === null) {
                return response()->json(['message' => 'Invalid document type.'], 422);
            }

            $chunk = DB::table($table)
                ->whereYear('dateCreated', $year)
                ->selectRaw('MONTH(dateCreated) as m, COUNT(*) as c')
                ->groupBy('m')
                ->orderBy('m')
                ->pluck('c', 'm')
                ->all();

            $byMonth = array_fill(1, 12, 0);
            for ($i = 1; $i <= 12; $i++) {
                $key = (string) $i;
                $byMonth[$i] = (int) ($chunk[$i] ?? $chunk[$key] ?? 0);
            }
        }

        $months = [];
        $total = 0;
        for ($i = 1; $i <= 12; $i++) {
            $c = (int) ($byMonth[$i] ?? 0);
            $total += $c;
            $months[] = [
                'month' => $i,
                'label' => $short[$i - 1],
                'count' => $c,
            ];
        }

        return response()->json([
            'type' => $type,
            'year' => $year,
            'total' => $total,
            'months' => $months,
        ]);
    }

    public function records(Request $request): JsonResponse
    {
        return $this->paginatedRegistryTableJson($request);
    }

    public function searchSAPPCData(Request $request): JsonResponse
    {
        return $this->paginatedRegistryTableJson($request);
    }

    private function paginatedRegistryTableJson(Request $request): JsonResponse
    {
        $rawPerPage = (int) $request->input('per_page', 10);
        $perPage = in_array($rawPerPage, self::PER_PAGE_OPTIONS, true) ? $rawPerPage : self::PER_PAGE_OPTIONS[0];
        $page = max(1, (int) $request->input('page', 1));

        $paginator = $this->filteredRegistryBaseQuery($request)
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json($this->tablePayloadFromPaginator($paginator));
    }

    private function registryRowToTableArray(object $row, int $rowNumber): array
    {
        $client = property_exists($row, 'displayClient') && $row->displayClient !== null && $row->displayClient !== ''
            ? $row->displayClient
            : (ClientNameDisplay::fullDisplayName(
                $row->clientFName ?? null,
                $row->clientMName ?? null,
                $row->clientLName ?? null
            ) ?: '—');

        $dateCreated = property_exists($row, 'cert_created_at') && $row->cert_created_at !== null && $row->cert_created_at !== ''
            ? ClientNameDisplay::formatDateTimeCreated($row->cert_created_at)
            : (property_exists($row, 'displayDateCreated') && $row->displayDateCreated !== null && $row->displayDateCreated !== ''
                ? $row->displayDateCreated
                : ClientNameDisplay::formatDateCreated($row->dateCreated ?? null));

        return [
            'rowNumber' => $rowNumber,
            'recordId' => $row->record_id,
            'documentType' => $row->document_type,
            'referenceCode' => $row->referenceCode ?? '',
            'client' => $client,
            'address' => ($row->address ?? '') !== '' ? ClientNameDisplay::formatAddress((string) $row->address) : '—',
            'sex' => ($row->sex ?? '') !== '' ? $row->sex : '—',
            'contactNum' => ($row->contactNum ?? '') !== '' ? $row->contactNum : '—',
            'paymentStatus' => ($row->paymentStatus ?? '') !== '' ? $row->paymentStatus : '—',
            'dateCreated' => ($dateCreated !== null && $dateCreated !== '') ? $dateCreated : '—',
        ];
    }

    private function tablePayloadFromPaginator(LengthAwarePaginator $paginator): array
    {
        $from = $paginator->firstItem();
        $offset = $from ? $from - 1 : 0;

        $data = $paginator->getCollection()->values()->map(function ($row, int $i) use ($offset) {
            return $this->registryRowToTableArray((object) (array) $row, $offset + $i + 1);
        })->all();

        return [
            'data' => $data,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ];
    }


    private function registryTableQuery(string $table): Builder
    {
        $shared = 'referenceCode, clientFName, clientMName, clientLName, address, sex, contactNum, dateCreated, customerId';

        return match ($table) {
            'christening' => DB::table('christening')->selectRaw(
                "christeningId AS record_id, 'Christening' AS document_type, {$shared}, scheduleRequested, paymentStatus"
            ),
            'confirmation' => DB::table('confirmation')->selectRaw(
                "confirmationId AS record_id, 'Confirmation' AS document_type, {$shared}, scheduleRequested, paymentStatus"
            ),
            'wedding' => DB::table('wedding')->selectRaw(
                "weddingId AS record_id, 'Wedding' AS document_type, {$shared}, scheduleRequested, paymentStatus"
            ),
            'burial' => DB::table('burial')->selectRaw(
                "burialId AS record_id, 'Burial' AS document_type, {$shared}, scheduleRequested, paymentStatus"
            ),
            default => DB::query()->fromSub($this->registryUnionQuery(), 'registry'),
        };
    }

    private function registryUnionQuery(): Builder
    {
        return $this->registryTableQuery('confirmation')
            ->unionAll($this->registryTableQuery('christening'))
            ->unionAll($this->registryTableQuery('wedding'))
            ->unionAll($this->registryTableQuery('burial'));
    }

    private function filteredRegistryBaseQuery(Request $request): Builder
    {
        $registryType = strtolower(trim((string) $request->input('registry_type', '')));
        $registrySection = strtolower(trim((string) $request->input('registry_section', '')));

        $singleTable = match ($registryType) {
            'christening' => 'christening',
            'confirmation' => 'confirmation',
            'wedding' => 'wedding',
            'burial' => 'burial',
            default => null,
        };

        if ($singleTable !== null) {
            $query = $this->registryTableQuery($singleTable);
            SacramentRegistrySectionFilter::apply($query, $singleTable, $registrySection);
            if ($registrySection === SacramentRegistrySectionFilter::SECTION_CERTIFICATION) {
                $this->applyCertificationCreatedAtJoin($query, $singleTable);
            }
        } else {
            $query = DB::query()->fromSub($this->registryUnionQuery(), 'registry');
            $registryTypeMap = [
                'christening' => 'Christening',
                'confirmation' => 'Confirmation',
                'wedding' => 'Wedding',
                'burial' => 'Burial',
            ];
            if ($registryType !== '' && isset($registryTypeMap[$registryType])) {
                $query->where('document_type', $registryTypeMap[$registryType]);
            }
        }

        $searchTerm = trim((string) $request->input('search', ''));
        if ($searchTerm !== '') {
            $like = self::searchLikePattern($searchTerm);
            $query->where(function (Builder $w) use ($like) {
                $w->whereRaw('LOWER(TRIM(COALESCE(referenceCode, ?))) LIKE ?', ['', $like])
                    ->orWhereRaw('LOWER(TRIM(COALESCE(clientFName, ?))) LIKE ?', ['', $like])
                    ->orWhereRaw('LOWER(TRIM(COALESCE(clientMName, ?))) LIKE ?', ['', $like])
                    ->orWhereRaw('LOWER(TRIM(COALESCE(clientLName, ?))) LIKE ?', ['', $like])
                    ->orWhereRaw('LOWER(TRIM(COALESCE(address, ?))) LIKE ?', ['', $like])
                    ->orWhereRaw('LOWER(TRIM(COALESCE(contactNum, ?))) LIKE ?', ['', $like])
                    ->orWhereRaw('LOWER(TRIM(COALESCE(sex, ?))) LIKE ?', ['', $like])
                    ->orWhereRaw('LOWER(TRIM(COALESCE(document_type, ?))) LIKE ?', ['', $like]);
            });
        }

        $letter = $request->input('letter');
        if ($letter !== null && $letter !== '') {
            $L = strtoupper(substr((string) $letter, 0, 1));
            $allowed = self::letterFilterOptions();
            if ($L !== '' && in_array($L, $allowed, true)) {
                $prefix = strtolower($L).'%';
                $query->where(function (Builder $w) use ($prefix) {
                    $w->whereRaw('LOWER(TRIM(COALESCE(clientLName, ?))) LIKE ?', ['', $prefix])
                        ->orWhere(function (Builder $w2) use ($prefix) {
                            $w2->whereRaw('TRIM(COALESCE(clientLName, ?)) = ?', ['', ''])
                                ->whereRaw(
                                    'LOWER(TRIM(SUBSTRING_INDEX(TRIM(COALESCE(clientFName, ?)), ?, -1))) LIKE ?',
                                    ['', ' ', $prefix]
                                );
                        });
                });
            }
        }

        $usesCertCreatedAt = $singleTable !== null
            && $registrySection === SacramentRegistrySectionFilter::SECTION_CERTIFICATION
            && Schema::hasTable('certification_details');

        if ($request->filled('date_from')) {
            $dateColumn = $usesCertCreatedAt ? 'cert_created_at' : 'dateCreated';
            $query->whereDate($dateColumn, '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $dateColumn = $usesCertCreatedAt ? 'cert_created_at' : 'dateCreated';
            $query->whereDate($dateColumn, '<=', $request->input('date_to'));
        }

        $sortOrder = strtolower(trim((string) $request->input('sort_order', 'desc')));
        if ($usesCertCreatedAt) {
            if ($sortOrder === 'asc') {
                return $query->orderBy('cert_created_at')->orderBy('record_id');
            }

            return $query->orderByDesc('cert_created_at')->orderByDesc('record_id');
        }

        if ($sortOrder === 'asc') {
            return $query->orderBy('dateCreated')->orderBy('record_id');
        }

        return $query->orderByDesc('dateCreated')->orderByDesc('record_id');
    }

    private function applyCertificationCreatedAtJoin(Builder $query, string $table): void
    {
        if (! Schema::hasTable('certification_details')) {
            return;
        }

        $primaryKey = match ($table) {
            'christening' => 'christeningId',
            'confirmation' => 'confirmationId',
            'wedding' => 'weddingId',
            'burial' => 'burialId',
            default => null,
        };

        $registryType = match ($table) {
            'christening' => 'Christening',
            'confirmation' => 'Confirmation',
            'wedding' => 'Wedding',
            'burial' => 'Burial',
            default => null,
        };

        if ($primaryKey === null || $registryType === null) {
            return;
        }

        $query->selectSub(function (Builder $sub) use ($registryType, $table, $primaryKey) {
            $sub->from('certification_details as cd')
                ->selectRaw('MAX(cd.created_at)')
                ->where(function (Builder $match) use ($registryType, $table, $primaryKey) {
                    $match->where(function (Builder $linked) use ($registryType, $table, $primaryKey) {
                        $linked->where('cd.registryType', $registryType)
                            ->whereColumn('cd.registryRecordId', "{$table}.{$primaryKey}");
                    })->orWhereColumn('cd.referenceCode', "{$table}.referenceCode");
                });
        }, 'cert_created_at');
    }

    public function deleteRecord(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'record_id' => ['required', 'integer', 'min:1'],
            'document_type' => ['required', 'string', 'in:Confirmation,Christening,Wedding,Burial'],
        ]);

        $recordId = (int) $validated['record_id'];
        $documentType = $validated['document_type'];

        $exists = match ($documentType) {
            'Confirmation' => DB::table('confirmation')->where('confirmationId', $recordId)->exists(),
            'Christening' => DB::table('christening')->where('christeningId', $recordId)->exists(),
            'Wedding' => DB::table('wedding')->where('weddingId', $recordId)->exists(),
            'Burial' => DB::table('burial')->where('burialId', $recordId)->exists(),
        };

        if (! $exists) {
            return response()->json([
                'status' => 'error',
                'message' => 'Record not found.',
            ], 404);
        }

        try {
            DB::transaction(function () use ($documentType, $recordId) {
                match ($documentType) {
                    'Confirmation' => $this->deleteConfirmationRegistryRow($recordId),
                    'Christening' => $this->deleteChristeningRegistryRow($recordId),
                    'Wedding' => $this->deleteWeddingRegistryRow($recordId),
                    'Burial' => $this->deleteBurialRegistryRow($recordId),
                };
            });
        } catch (QueryException $e) {
            report($e);

            return response()->json([
                'status' => 'error',
                'message' => 'Could not delete this record. If this persists, run database migrations and try again.',
            ], 422);
        }

        return response()->json([
            'status' => 'success',
            'ok' => true,
            'message' => 'Record deleted.',
        ]);
    }

    public function deleteChristeningRegistryRow(int $christeningId): void
    {
        $row = DB::table('christening')->where('christeningId', $christeningId)->first();
        if ($row === null) {
            return;
        }

        DocumentationApplicationReportWriter::deleteFor(DocumentationApplicationReport::SERVICE_CHRISTENING, $christeningId);

        DB::table('christening_certification')->where('christeningId', $christeningId)->delete();
        DB::table('christening_details')->where('christeningId', $christeningId)->delete();
        DB::table('christening')->where('christeningId', $christeningId)->delete();

        $this->deleteCustomerIfOrphaned($row->customerId ?? null);
    }

    public function deleteConfirmationRegistryRow(int $confirmationId): void
    {
        $row = DB::table('confirmation')->where('confirmationId', $confirmationId)->first();
        if ($row === null) {
            return;
        }

        DocumentationApplicationReportWriter::deleteFor(DocumentationApplicationReport::SERVICE_CONFIRMATION, $confirmationId);

        if (DB::getSchemaBuilder()->hasTable('confirmation_details')) {
            DB::table('confirmation_details')->where('confirmationId', $confirmationId)->delete();
        }
        DB::table('confirmation')->where('confirmationId', $confirmationId)->delete();
        $this->deleteCustomerIfOrphaned($row->customerId ?? null);
    }

    public function deleteWeddingRegistryRow(int $weddingId): void
    {
        $row = DB::table('wedding')->where('weddingId', $weddingId)->first();
        if ($row === null) {
            return;
        }

        DocumentationApplicationReportWriter::deleteFor(DocumentationApplicationReport::SERVICE_WEDDING, $weddingId);

        if (DB::getSchemaBuilder()->hasTable('wedding_certification')) {
            DB::table('wedding_certification')->where('weddingId', $weddingId)->delete();
        }
        if (DB::getSchemaBuilder()->hasTable('wedding_details')) {
            DB::table('wedding_details')->where('weddingId', $weddingId)->delete();
        }
        DB::table('wedding')->where('weddingId', $weddingId)->delete();
        $this->deleteCustomerIfOrphaned($row->customerId ?? null);
    }

    public function deleteBurialRegistryRow(int $burialId): void
    {
        $row = DB::table('burial')->where('burialId', $burialId)->first();
        if ($row === null) {
            return;
        }

        DocumentationApplicationReportWriter::deleteFor(DocumentationApplicationReport::SERVICE_BURIAL, $burialId);

        DB::table('burial')->where('burialId', $burialId)->delete();
        $this->deleteCustomerIfOrphaned($row->customerId ?? null);
    }

    private function deleteCustomerIfOrphaned(mixed $customerId): void
    {
        if ($customerId === null || $customerId === '') {
            return;
        }
        $cid = (int) $customerId;
        if ($cid < 1) {
            return;
        }
        if (DB::table('christening')->where('customerId', $cid)->exists()) {
            return;
        }
        if (DB::table('confirmation')->where('customerId', $cid)->exists()) {
            return;
        }
        if (DB::table('wedding')->where('customerId', $cid)->exists()) {
            return;
        }
        if (DB::table('burial')->where('customerId', $cid)->exists()) {
            return;
        }
        if (DB::getSchemaBuilder()->hasTable('customer')) {
            DB::table('customer')->where('customerId', $cid)->delete();
        }
    }
}

