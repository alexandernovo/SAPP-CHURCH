<?php

namespace App\Support;

use Illuminate\Database\Query\Builder;

/**
 * Links sacrament registry rows to certification_details rows.
 * Uses explicit COLLATE so mixed table collations do not break EXISTS / subquery joins.
 */
final class CertificationRegistryMatch
{
    public static function collation(): string
    {
        return (string) config('database.connections.mysql.collation', 'utf8mb4_unicode_ci');
    }

    public static function applyMatch(
        Builder $match,
        string $registryTable,
        string $primaryKey,
        string $registryType
    ): void {
        $collation = self::collation();

        $match->where(function (Builder $linked) use ($registryTable, $primaryKey, $registryType, $collation) {
            $linked->whereRaw("cd.registryType COLLATE {$collation} = ?", [$registryType])
                ->whereColumn('cd.registryRecordId', "{$registryTable}.{$primaryKey}");
        })->orWhereRaw(
            "cd.referenceCode COLLATE {$collation} = `{$registryTable}`.referenceCode COLLATE {$collation}"
        );
    }
}
