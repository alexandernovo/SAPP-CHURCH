<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;

final class ClientNameDisplay
{
    private const DISPLAY_TIMEZONE = 'Asia/Taipei';
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
        $mi = self::middleInitial($middle);

        return trim(implode(' ', array_filter([
            $first,
            $mi !== '' ? $mi : null,
            $last,
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
}
