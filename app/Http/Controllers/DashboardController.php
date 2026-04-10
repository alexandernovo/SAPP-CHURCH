<?php

namespace App\Http\Controllers;

use App\Support\ClientNameDisplay;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{

    private const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    /** Toolbar letters A–Z (scrollable strip); filter uses `clientLName` (with fallback) on the union of all registry tables. */
    private const LETTER_FILTER_END = 'Z';

    private static function letterFilterOptions(): array
    {
        return range('A', self::LETTER_FILTER_END);
    }

    /** Lowercase `LIKE` pattern with `%` / `_` escaped for MySQL. */
    private static function searchLikePattern(string $term): string
    {
        $t = mb_strtolower(trim($term));
        if ($t === '') {
            return '%%';
        }
        $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $t);

        return '%'.$escaped.'%';
    }


    private static function chartMonthLabels(): array
    {
        return ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
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
            'chartMonthLabels' => self::chartMonthLabels(),
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
        $type = strtolower((string) $request->query('type', ''));
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

        $year = (int) $request->query('year', date('Y'));
        if ($year < 2000 || $year > 2100) {
            return response()->json(['message' => 'Invalid year.'], 422);
        }

        $byMonth = DB::table($table)
            ->whereYear('dateCreated', $year)
            ->selectRaw('MONTH(dateCreated) as m, COUNT(*) as c')
            ->groupBy('m')
            ->orderBy('m')
            ->pluck('c', 'm')
            ->all();

        $short = ['JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC'];
        $months = [];
        $total = 0;
        for ($i = 1; $i <= 12; $i++) {
            $key = (string) $i;
            $c = (int) ($byMonth[$i] ?? $byMonth[$key] ?? 0);
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

        $dateCreated = property_exists($row, 'displayDateCreated') && $row->displayDateCreated !== null && $row->displayDateCreated !== ''
            ? $row->displayDateCreated
            : ClientNameDisplay::formatDateCreated($row->dateCreated ?? null);

        return [
            'rowNumber' => $rowNumber,
            'recordId' => $row->record_id,
            'documentType' => $row->document_type,
            'referenceCode' => $row->referenceCode ?? '',
            'client' => $client,
            'address' => ($row->address ?? '') !== '' ? $row->address : '—',
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


    private function registryUnionQuery(): Builder
    {
        $shared = 'referenceCode, clientFName, clientMName, clientLName, address, sex, contactNum, dateCreated, customerId';

        $confirmation = DB::table('confirmation')->selectRaw(
            "confirmationId AS record_id, 'Confirmation' AS document_type, {$shared}, NULL AS scheduleRequested, NULL AS paymentStatus"
        );

        $christening = DB::table('christening')->selectRaw(
            "christeningId AS record_id, 'Christening' AS document_type, {$shared}, scheduleRequested, paymentStatus"
        );

        $wedding = DB::table('wedding')->selectRaw(
            "weddingId AS record_id, 'Wedding' AS document_type, {$shared}, NULL AS scheduleRequested, NULL AS paymentStatus"
        );

        $burial = DB::table('burial')->selectRaw(
            "burialId AS record_id, 'Burial' AS document_type, {$shared}, NULL AS scheduleRequested, paymentStatus"
        );

        return $confirmation->unionAll($christening)->unionAll($wedding)->unionAll($burial);
    }

    private function filteredRegistryBaseQuery(Request $request): Builder
    {
        $query = DB::query()->fromSub($this->registryUnionQuery(), 'registry');

        $registryType = strtolower(trim((string) $request->input('registry_type', '')));
        $registryTypeMap = [
            'christening' => 'Christening',
            'confirmation' => 'Confirmation',
            'wedding' => 'Wedding',
            'burial' => 'Burial',
        ];
        if ($registryType !== '' && isset($registryTypeMap[$registryType])) {
            $query->where('document_type', $registryTypeMap[$registryType]);
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

        if ($request->filled('date_from')) {
            $query->whereDate('dateCreated', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('dateCreated', '<=', $request->input('date_to'));
        }

        return $query->orderByDesc('dateCreated')->orderByDesc('record_id');
    }
}
