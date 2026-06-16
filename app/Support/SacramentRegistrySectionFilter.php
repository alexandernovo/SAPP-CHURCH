<?php

namespace App\Support;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Each registry workflow page shows only rows completed at that step.
 * Application save does not appear in schedule/payment/cert tables until that step is saved via its modal.
 */
final class SacramentRegistrySectionFilter
{
    public const SECTION_APPLICATION = 'application';

    public const SECTION_SCHEDULE = 'schedule';

    public const SECTION_PAYMENT = 'payment';

    public const SECTION_CERTIFICATION = 'certification';

    public static function apply(Builder $query, string $registryTable, string $section): void
    {
        $section = strtolower(trim($section));
        if ($section === '') {
            return;
        }

        match ($registryTable) {
            'christening' => self::applyChristening($query, $section),
            'confirmation' => self::applyConfirmation($query, $section),
            'wedding' => self::applyWedding($query, $section),
            'burial' => self::applyBurial($query, $section),
            default => null,
        };
    }

    private static function applyChristening(Builder $query, string $section): void
    {
        match ($section) {
            self::SECTION_APPLICATION => $query->whereExists(function (Builder $sub) {
                $sub->select(DB::raw('1'))
                    ->from('christening_details as cd')
                    ->whereColumn('cd.christeningId', 'christening.christeningId')
                    ->whereRaw("TRIM(COALESCE(cd.firstName, '')) <> ''")
                    ->whereRaw("TRIM(COALESCE(cd.familyName, '')) <> ''");
            }),
            self::SECTION_SCHEDULE => $query->whereNotNull('scheduleRequested')
                ->whereRaw("TRIM(COALESCE(scheduleRequested, '')) <> ''"),
            self::SECTION_PAYMENT => self::wherePaymentFeeSaved($query, 'christening'),
            self::SECTION_CERTIFICATION => self::whereCertificationDetailsSaved(
                $query,
                'christening',
                'christeningId',
                'Christening'
            ),
            default => null,
        };
    }

    private static function applyConfirmation(Builder $query, string $section): void
    {
        match ($section) {
            self::SECTION_APPLICATION => $query->where(function (Builder $w) {
                if (Schema::hasTable('confirmation_details')) {
                    $w->whereExists(function (Builder $sub) {
                        $sub->select(DB::raw('1'))
                            ->from('confirmation_details as cd')
                            ->whereColumn('cd.confirmationId', 'confirmation.confirmationId')
                            ->whereRaw("TRIM(COALESCE(cd.firstName, '')) <> ''")
                            ->whereRaw("TRIM(COALESCE(cd.familyName, '')) <> ''");
                    });
                }
                $w->orWhere(function (Builder $w2) {
                    $w2->whereNotNull('confirmationApplication')
                        ->whereRaw("TRIM(COALESCE(confirmationApplication, '')) <> ''")
                        ->whereRaw("TRIM(COALESCE(confirmationApplication, '')) <> '[]'");
                });
            }),
            self::SECTION_SCHEDULE => $query->whereNotNull('scheduleRequested')
                ->whereRaw("TRIM(COALESCE(scheduleRequested, '')) <> ''"),
            self::SECTION_PAYMENT => self::wherePaymentFeeSaved($query, 'confirmation'),
            self::SECTION_CERTIFICATION => self::whereCertificationDetailsSaved(
                $query,
                'confirmation',
                'confirmationId',
                'Confirmation'
            ),
            default => null,
        };
    }

    private static function applyWedding(Builder $query, string $section): void
    {
        match ($section) {
            self::SECTION_APPLICATION => $query->whereNotNull('marriageApplication')
                ->whereRaw("TRIM(COALESCE(marriageApplication, '')) <> ''")
                ->whereRaw("TRIM(COALESCE(marriageApplication, '')) <> '[]'"),
            self::SECTION_SCHEDULE => $query->whereNotNull('scheduleRequested')
                ->whereRaw("TRIM(COALESCE(scheduleRequested, '')) <> ''"),
            self::SECTION_PAYMENT => self::wherePaymentFeeSaved($query, 'wedding'),
            self::SECTION_CERTIFICATION => self::whereCertificationDetailsSaved(
                $query,
                'wedding',
                'weddingId',
                'Wedding'
            ),
            default => null,
        };
    }

    private static function applyBurial(Builder $query, string $section): void
    {
        match ($section) {
            self::SECTION_APPLICATION => $query->where(function (Builder $w) {
                if (Schema::hasTable('burial_details')) {
                    $w->whereExists(function (Builder $sub) {
                        $sub->select(DB::raw('1'))
                            ->from('burial_details as bd')
                            ->whereColumn('bd.burialId', 'burial.burialId')
                            ->whereRaw("TRIM(COALESCE(bd.deceasedName, '')) <> ''");
                    });
                } else {
                    $w->whereRaw('1 = 0');
                }
            }),
            self::SECTION_SCHEDULE => $query->whereNotNull('scheduleRequested')
                ->whereRaw("TRIM(COALESCE(scheduleRequested, '')) <> ''"),
            self::SECTION_PAYMENT => self::wherePaymentFeeSaved($query, 'burial'),
            self::SECTION_CERTIFICATION => self::whereCertificationDetailsSaved(
                $query,
                'burial',
                'burialId',
                'Burial'
            ),
            default => null,
        };
    }

    private static function whereCertificationDetailsSaved(
        Builder $query,
        string $table,
        string $primaryKey,
        string $registryType
    ): void {
        if (! Schema::hasTable('certification_details')) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->whereExists(function (Builder $sub) use ($table, $primaryKey, $registryType) {
            $sub->select(DB::raw('1'))
                ->from('certification_details as cd')
                ->where(function (Builder $match) use ($table, $primaryKey, $registryType) {
                    CertificationRegistryMatch::applyMatch($match, $table, $primaryKey, $registryType);
                });
        });
    }

    private static function wherePaymentFeeSaved(Builder $query, string $table): void
    {
        if (! Schema::hasColumn($table, 'paymentFeeRows')) {
            $query->whereRaw('LOWER(TRIM(COALESCE(paymentStatus, ?))) = ?', ['', 'paid']);

            return;
        }

        $query->whereNotNull('paymentFeeRows')
            ->whereRaw("TRIM(COALESCE(paymentFeeRows, '')) <> ''")
            ->whereRaw("TRIM(COALESCE(paymentFeeRows, '')) <> '[]'");
    }
}
