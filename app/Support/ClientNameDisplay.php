<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;

final class ClientNameDisplay
{
    private const DISPLAY_TIMEZONE = 'Asia/Taipei';

    public static function capitalizeNamePart(?string $name): string
    {
        $s = trim((string) ($name ?? ''));
        if ($s === '') {
            return '';
        }

        return mb_strtoupper(mb_substr($s, 0, 1)).mb_strtolower(mb_substr($s, 1));
    }

    public static function titleCaseNamePart(?string $name): string
    {
        $s = trim((string) ($name ?? ''));
        if ($s === '') {
            return '';
        }

        $words = preg_split('/\s+/u', $s, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return implode(' ', array_map([self::class, 'capitalizeNamePart'], $words));
    }

    public static function normalizeParts(?string $first, ?string $middle, ?string $last): array
    {
        return [
            'first' => self::capitalizeNamePart($first),
            'middle' => self::capitalizeNamePart($middle),
            'last' => self::titleCaseNamePart($last),
        ];
    }

    public static function formatAddress(?string $value): string
    {
        return self::titleCaseNamePart($value);
    }

    public static function formatPriestName(?string $value): string
    {
        return self::titleCaseNamePart($value);
    }

    public static function nullableFormattedAddress(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $s = self::titleCaseNamePart((string) $value);

        return $s === '' ? null : $s;
    }

    public static function nullableFormattedPriest(mixed $value): ?string
    {
        return self::nullableFormattedAddress($value);
    }

    public static function nullableFormattedNamePart(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $s = self::capitalizeNamePart((string) $value);

        return $s === '' ? null : $s;
    }

    public static function nullableFormattedFamilyName(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $s = self::titleCaseNamePart((string) $value);

        return $s === '' ? null : $s;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function normalizeApplicationNameFields(array $data, ?string $parentKey = null): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = self::normalizeApplicationNameFields($value, (string) $key);

                continue;
            }
            if (! is_scalar($value)) {
                continue;
            }

            $keyStr = (string) $key;
            $parent = strtolower((string) ($parentKey ?? ''));

            if ($parent === 'marriage_sponsors') {
                $formatted = self::nullableFormattedFamilyName($value);
                if ($formatted !== null) {
                    $data[$key] = $formatted;
                }

                continue;
            }

            if ($parent === 'precana' && $keyStr === 'signature') {
                $formatted = self::nullableFormattedFamilyName($value);
                if ($formatted !== null) {
                    $data[$key] = $formatted;
                }

                continue;
            }

            if (self::isApplicationNamePartKey($keyStr)) {
                $formatted = self::nullableFormattedNamePart($value);
                if ($formatted !== null) {
                    $data[$key] = $formatted;
                }

                continue;
            }

            if (self::isApplicationFamilyNameKey($keyStr)) {
                $formatted = self::nullableFormattedFamilyName($value);
                if ($formatted !== null) {
                    $data[$key] = $formatted;
                }

                continue;
            }

            if (self::isApplicationFullPersonNameKey($keyStr)) {
                $formatted = self::nullableFormattedFamilyName($value);
                if ($formatted !== null) {
                    $data[$key] = $formatted;
                }
            }
        }

        return $data;
    }

    private static function isApplicationNamePartKey(string $key): bool
    {
        $k = strtolower($key);

        return (bool) preg_match('/(^|_)(first_name|middle_name)$/', $k)
            || in_array($k, ['maninoy', 'maninay'], true);
    }

    private static function isApplicationFamilyNameKey(string $key): bool
    {
        $k = strtolower($key);

        return (bool) preg_match('/(^|_)(family_name|last_name)$/', $k);
    }

    private static function isApplicationFullPersonNameKey(string $key): bool
    {
        $k = strtolower($key);

        if (self::isApplicationNamePartKey($k) || self::isApplicationFamilyNameKey($k)) {
            return false;
        }

        if (preg_match('/place|address|religion|contact|remark|relation|topic|amount|permit|status|age|date|time|number|bec|selda|kinamatyan|obligation|stewardship|sacrament|ceremony|interment|niche|occupation|label|remarks|ar_number|doc_/', $k)) {
            return false;
        }

        return (bool) preg_match('/father|mother|maiden|godparent|deceased|spouse|claimant|sponsor|full_name|signature|chairman|secretary|fiscal|minister|officiating|maninoy|maninay|approval_|sig_|noted_/', $k);
    }

    public static function middleInitial(?string $m): string
    {
        if ($m === null || trim($m) === '') {
            return '';
        }

        $m = trim($m);

        if (preg_match('/\p{L}/u', $m, $match)) {
            return mb_strtoupper($match[0]).'.';
        }

        $first = mb_substr($m, 0, 1);

        return $first !== '' ? mb_strtoupper($first).'.' : '';
    }

    public static function fullDisplayName(?string $first, ?string $middle, ?string $last): string
    {
        $parts = self::normalizeParts($first, $middle, $last);
        $mi = self::middleInitial($parts['middle']);

        return trim(implode(' ', array_filter([
            $parts['first'],
            $mi !== '' ? $mi : null,
            $parts['last'],
        ], fn ($part) => filled($part))));
    }

    public static function formatDateCreated(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        if ($value instanceof CarbonInterface) {
            return $value->format('F j, Y');
        }

        try {
            return Carbon::parse($value)->format('F j, Y');
        } catch (\Throwable) {
            return '—';
        }
    }

    public static function formatDateTimeCreated(mixed $value, ?string $timezone = self::DISPLAY_TIMEZONE): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        try {
            $dt = $value instanceof CarbonInterface
                ? $value->copy()
                : Carbon::parse($value, config('app.timezone', 'UTC'));

            if ($timezone !== null && $timezone !== '') {
                $dt = $dt->timezone($timezone);
            }

            return $dt->format('F j, Y g:i A');
        } catch (\Throwable) {
            return '—';
        }
    }

    public static function monthBoundsUtcForDisplayTimezone(string $monthYm, ?string $timezone = self::DISPLAY_TIMEZONE): ?array
    {
        try {
            $start = Carbon::createFromFormat('Y-m', $monthYm, $timezone)->startOfMonth()->utc();
            $end = Carbon::createFromFormat('Y-m', $monthYm, $timezone)->endOfMonth()->utc();

            return [$start, $end];
        } catch (\Throwable) {
            return null;
        }
    }

    public static function formatMonthYearLabel(string $monthYm, ?string $timezone = self::DISPLAY_TIMEZONE): string
    {
        try {
            return Carbon::createFromFormat('Y-m', $monthYm, $timezone)->translatedFormat('F Y');
        } catch (\Throwable) {
            return '';
        }
    }
}
