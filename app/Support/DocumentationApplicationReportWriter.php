<?php

namespace App\Support;

use App\Models\DocumentationApplicationReport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class DocumentationApplicationReportWriter
{
    public static function syncChristening(int $christeningId, ?string $firstName, ?string $middleName, ?string $familyName): void
    {
        if (! self::tableReady()) {
            return;
        }

        $parent = DB::table('christening')->where('christeningId', $christeningId)->first();
        if ($parent === null) {
            return;
        }

        $clientName = ClientNameDisplay::fullDisplayName($firstName, $middleName, $familyName);
        if ($clientName === '') {
            $clientName = ClientNameDisplay::fullDisplayName(
                $parent->clientFName ?? null,
                $parent->clientMName ?? null,
                $parent->clientLName ?? null
            );
        }

        self::upsertFromParent(
            DocumentationApplicationReport::SERVICE_CHRISTENING,
            $christeningId,
            $parent,
            $clientName !== '' ? $clientName : null
        );
    }

    public static function syncBurial(int $burialId, array $applicationPayload): void
    {
        if (! self::tableReady()) {
            return;
        }

        $parent = DB::table('burial')->where('burialId', $burialId)->first();
        if ($parent === null) {
            return;
        }

        $clientName = trim((string) ($applicationPayload['deceased_name'] ?? ''));
        if ($clientName === '') {
            $clientName = ClientNameDisplay::fullDisplayName(
                $parent->clientFName ?? null,
                $parent->clientMName ?? null,
                $parent->clientLName ?? null
            );
        }

        self::upsertFromParent(
            DocumentationApplicationReport::SERVICE_BURIAL,
            $burialId,
            $parent,
            $clientName !== '' ? $clientName : null
        );
    }

    public static function syncConfirmation(int $confirmationId, array $applicationPayload): void
    {
        if (! self::tableReady()) {
            return;
        }

        $parent = DB::table('confirmation')->where('confirmationId', $confirmationId)->first();
        if ($parent === null) {
            return;
        }

        $first = self::stringFromPayload($applicationPayload, 'first_name');
        $middle = self::stringFromPayload($applicationPayload, 'middle_name');
        $family = self::stringFromPayload($applicationPayload, 'family_name');
        $clientName = ClientNameDisplay::fullDisplayName($first, $middle, $family);
        if ($clientName === '') {
            $clientName = ClientNameDisplay::fullDisplayName(
                $parent->clientFName ?? null,
                $parent->clientMName ?? null,
                $parent->clientLName ?? null
            );
        }

        self::upsertFromParent(
            DocumentationApplicationReport::SERVICE_CONFIRMATION,
            $confirmationId,
            $parent,
            $clientName !== '' ? $clientName : null
        );
    }

    public static function syncWedding(int $weddingId, array $applicationPayload): void
    {
        if (! self::tableReady()) {
            return;
        }

        $parent = DB::table('wedding')->where('weddingId', $weddingId)->first();
        if ($parent === null) {
            return;
        }

        $groom = trim((string) ($applicationPayload['groom_full_name'] ?? ''));
        $bride = trim((string) ($applicationPayload['bride_full_name'] ?? ''));
        $parts = array_filter([$groom, $bride], fn ($s) => $s !== '');
        $clientName = implode(' & ', $parts);
        if ($clientName === '') {
            $clientName = ClientNameDisplay::fullDisplayName(
                $parent->clientFName ?? null,
                $parent->clientMName ?? null,
                $parent->clientLName ?? null
            );
        }

        self::upsertFromParent(
            DocumentationApplicationReport::SERVICE_WEDDING,
            $weddingId,
            $parent,
            $clientName !== '' ? $clientName : null
        );
    }

    public static function deleteFor(string $serviceType, int $registryId): void
    {
        if (! self::tableReady()) {
            return;
        }

        DocumentationApplicationReport::query()
            ->where('service_type', $serviceType)
            ->where('registry_id', $registryId)
            ->delete();
    }

    /**
     * @param  object  $parent  christening|burial|confirmation|wedding row
     */
    private static function upsertFromParent(string $serviceType, int $registryId, object $parent, ?string $clientName): void
    {
        $now = now();

        DocumentationApplicationReport::query()->updateOrCreate(
            [
                'service_type' => $serviceType,
                'registry_id' => $registryId,
            ],
            [
                'reference_code' => self::nullableString($parent->referenceCode ?? null),
                'client_name' => $clientName,
                'address' => self::nullableString($parent->address ?? null),
                'sex' => self::nullableString($parent->sex ?? null),
                'contact_number' => self::nullableString($parent->contactNum ?? null),
                'report_month' => $now->format('Y-m'),
                'reported_at' => $now,
            ]
        );
    }

    private static function stringFromPayload(array $payload, string $key): ?string
    {
        if (! array_key_exists($key, $payload)) {
            return null;
        }
        $s = trim((string) $payload[$key]);

        return $s === '' ? null : $s;
    }

    private static function nullableString(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $s = trim($value);

        return $s === '' ? null : $s;
    }

    private static function tableReady(): bool
    {
        return Schema::hasTable('documentation_application_reports');
    }
}
