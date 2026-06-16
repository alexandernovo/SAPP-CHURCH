<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SacramentScheduleReservedDates
{
    /** @var array<string, string> */
    private const SERVICE_TABLES = [
        'christening' => 'Christening',
        'confirmation' => 'Confirmation',
        'wedding' => 'Wedding',
        'burial' => 'Burial',
    ];

    public static function forMonth(int $year, int $month, ?string $service = null): array
    {
        $tables = self::SERVICE_TABLES;

        if ($service !== null) {
            if (! isset($tables[$service])) {
                return [];
            }
            $tables = [$service => $tables[$service]];
        }

        $grouped = [];

        foreach (array_keys($tables) as $table) {
            $rows = DB::table($table)
                ->whereNotNull('scheduleRequested')
                ->whereYear('scheduleRequested', $year)
                ->whereMonth('scheduleRequested', $month)
                ->select('scheduleRequested')
                ->orderBy('scheduleRequested')
                ->get();

            foreach ($rows as $row) {
                if ($row->scheduleRequested === null || $row->scheduleRequested === '') {
                    continue;
                }

                $dt = Carbon::parse($row->scheduleRequested);
                $iso = $dt->format('Y-m-d');
                $timeLabel = $dt->format('g:i A');

                if (! isset($grouped[$iso])) {
                    $grouped[$iso] = [];
                }
                if (! in_array($timeLabel, $grouped[$iso], true)) {
                    $grouped[$iso][] = $timeLabel;
                }
            }
        }

        ksort($grouped);

        $result = [];
        foreach ($grouped as $iso => $entries) {
            $result[$iso] = implode(' / ', $entries);
        }

        return $result;
    }
}
