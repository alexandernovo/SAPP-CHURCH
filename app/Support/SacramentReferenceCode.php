<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

final class SacramentReferenceCode
{
    /**
     * Return the registry row reference code, generating and persisting one when missing.
     */
    public static function ensureOnRegistryRow(
        string $table,
        string $primaryKey,
        int $recordId,
        callable $generateUnique
    ): string {
        if ($recordId <= 0) {
            return '';
        }

        $row = DB::table($table)->where($primaryKey, $recordId)->first();
        if ($row === null) {
            return '';
        }

        $existing = trim((string) ($row->referenceCode ?? ''));
        if ($existing !== '') {
            return $existing;
        }

        $attempts = 0;
        do {
            $code = trim((string) $generateUnique());
            if ($code === '') {
                break;
            }
            $duplicate = DB::table($table)->where('referenceCode', $code)->exists();
            $attempts++;
        } while ($duplicate && $attempts < 12);

        if ($code === '' || $duplicate) {
            return '';
        }

        DB::table($table)->where($primaryKey, $recordId)->update([
            'referenceCode' => $code,
        ]);

        return $code;
    }
}
