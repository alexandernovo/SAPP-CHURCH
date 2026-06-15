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

    /**
     * @return array<string, string> ISO date (Y-m-d) => "Service · 10:00 AM" label(s)
     */
    public static function forMonth(int $year, int $month): array
    {
        /** @var array<string, list<string>> $grouped */
        $grouped = [];

        foreach (self::SERVICE_TABLES as $table => $label) {
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
                $entry = $label.' · '.$timeLabel;

                if (! isset($grouped[$iso])) {
                    $grouped[$iso] = [];
                }
                if (! in_array($entry, $grouped[$iso], true)) {
                    $grouped[$iso][] = $entry;
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
