<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class SacramentApplicationGate
{
    public const MESSAGE = 'Please do or fill the application form first.';

    public const PAYMENT_MESSAGE = 'Complete payment first. All fees must be marked Paid before you can continue to the next step.';

    public const CERTIFICATION_MESSAGE = 'Complete and save the certification first before you can continue to the next step.';

    public static function denyResponse(): JsonResponse
    {
        return response()->json([
            'ok' => false,
            'message' => self::MESSAGE,
        ], 422);
    }

    public static function paymentDenyResponse(): JsonResponse
    {
        return response()->json([
            'ok' => false,
            'message' => self::PAYMENT_MESSAGE,
        ], 422);
    }

    public static function certificationDenyResponse(): JsonResponse
    {
        return response()->json([
            'ok' => false,
            'message' => self::CERTIFICATION_MESSAGE,
        ], 422);
    }

    private static function isPaidStatus(mixed $status): bool
    {
        return strtolower(trim((string) $status)) === 'paid';
    }

    public static function christeningIsSaved(int $christeningId): bool
    {
        $details = DB::table('christening_details')
            ->where('christeningId', $christeningId)
            ->orderByDesc('christeningDetailsId')
            ->first();

        return $details !== null
            && trim((string) ($details->firstName ?? '')) !== ''
            && trim((string) ($details->familyName ?? '')) !== '';
    }

    public static function christeningIsPaymentComplete(int $christeningId): bool
    {
        $row = DB::table('christening')->where('christeningId', $christeningId)->first();

        return $row !== null && self::isPaidStatus($row->paymentStatus ?? null);
    }

    public static function christeningIsCertificationSaved(int $christeningId): bool
    {
        return DB::table('christening_certification')->where('christeningId', $christeningId)->exists();
    }

    public static function weddingIsSaved(int $weddingId): bool
    {
        $row = DB::table('wedding')->where('weddingId', $weddingId)->first();
        if ($row === null) {
            return false;
        }

        $raw = $row->marriageApplication ?? null;
        if ($raw === null || $raw === '') {
            return false;
        }

        $app = is_string($raw) ? json_decode($raw, true) : (is_array($raw) ? $raw : []);
        if (! is_array($app)) {
            return false;
        }

        $groomFirst = trim((string) ($app['first_name'] ?? ''));
        $groomLast = trim((string) ($app['family_name'] ?? ''));
        $bride = is_array($app['bride'] ?? null) ? $app['bride'] : [];
        $brideFirst = trim((string) ($bride['first_name'] ?? ''));
        $brideLast = trim((string) ($bride['family_name'] ?? ''));

        return $groomFirst !== '' && $groomLast !== '' && $brideFirst !== '' && $brideLast !== '';
    }

    public static function weddingIsPaymentComplete(int $weddingId): bool
    {
        $row = DB::table('wedding')->where('weddingId', $weddingId)->first();

        return $row !== null && self::isPaidStatus($row->paymentStatus ?? null);
    }

    public static function weddingIsCertificationSaved(int $weddingId): bool
    {
        if (! Schema::hasTable('wedding_certification')) {
            return false;
        }

        return DB::table('wedding_certification')->where('weddingId', $weddingId)->exists();
    }

    public static function burialIsSaved(int $burialId): bool
    {
        $details = DB::table('burial_details')
            ->where('burialId', $burialId)
            ->orderByDesc('burialDetailsId')
            ->first();

        return $details !== null && trim((string) ($details->deceasedName ?? '')) !== '';
    }

    public static function burialIsPaymentComplete(int $burialId): bool
    {
        $row = DB::table('burial')->where('burialId', $burialId)->first();

        return $row !== null && self::isPaidStatus($row->paymentStatus ?? null);
    }

    public static function confirmationIsSaved(int $confirmationId): bool
    {
        if (Schema::hasTable('confirmation_details')) {
            $details = DB::table('confirmation_details')
                ->where('confirmationId', $confirmationId)
                ->orderByDesc('confirmationDetailsId')
                ->first();

            if ($details !== null
                && trim((string) ($details->firstName ?? '')) !== ''
                && trim((string) ($details->familyName ?? '')) !== '') {
                return true;
            }
        }

        $row = DB::table('confirmation')->where('confirmationId', $confirmationId)->first();
        if ($row === null) {
            return false;
        }

        $raw = $row->confirmationApplication ?? null;
        if ($raw === null || $raw === '') {
            return false;
        }

        $app = is_string($raw) ? json_decode($raw, true) : (is_array($raw) ? $raw : []);
        if (! is_array($app)) {
            return false;
        }

        return trim((string) ($app['first_name'] ?? '')) !== ''
            && trim((string) ($app['family_name'] ?? '')) !== '';
    }

    public static function confirmationIsPaymentComplete(int $confirmationId): bool
    {
        $row = DB::table('confirmation')->where('confirmationId', $confirmationId)->first();

        return $row !== null && self::isPaidStatus($row->paymentStatus ?? null);
    }
}
