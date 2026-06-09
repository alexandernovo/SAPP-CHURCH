<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const WORKFLOW_APPLICATION = 'application';

    private const WORKFLOW_PAYMENT = 'payment';

    private const WORKFLOW_CERTIFICATION = 'certification';

    private const WORKFLOW_SCHEDULE = 'schedule';

    private const WORKFLOW_COMPLETED = 'completed';

    public function up(): void
    {
        foreach (['christening', 'wedding', 'burial', 'confirmation'] as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                if (! Schema::hasColumn($table, 'workflowStep')) {
                    $blueprint->string('workflowStep', 32)->default(self::WORKFLOW_APPLICATION)->index();
                }
                if (! Schema::hasColumn($table, 'applicationCompletedAt')) {
                    $blueprint->timestamp('applicationCompletedAt')->nullable();
                }
                if (! Schema::hasColumn($table, 'paymentCompletedAt')) {
                    $blueprint->timestamp('paymentCompletedAt')->nullable();
                }
                if (! Schema::hasColumn($table, 'certificationCompletedAt')) {
                    $blueprint->timestamp('certificationCompletedAt')->nullable();
                }
                if (! Schema::hasColumn($table, 'scheduleCompletedAt')) {
                    $blueprint->timestamp('scheduleCompletedAt')->nullable();
                }
            });
        }

        $this->backfillChristeningWorkflow();
        $this->backfillWeddingWorkflow();
        $this->backfillBurialWorkflow();
        $this->backfillConfirmationWorkflow();
    }

    public function down(): void
    {
        foreach (['christening', 'wedding', 'burial', 'confirmation'] as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                foreach ([
                    'workflowStep',
                    'applicationCompletedAt',
                    'paymentCompletedAt',
                    'certificationCompletedAt',
                    'scheduleCompletedAt',
                ] as $column) {
                    if (Schema::hasColumn($table, $column)) {
                        $blueprint->dropColumn($column);
                    }
                }
            });
        }
    }

    private function backfillChristeningWorkflow(): void
    {
        if (! Schema::hasTable('christening')) {
            return;
        }

        $rows = DB::table('christening')->select('christeningId', 'paymentStatus', 'scheduleRequested', 'dateCreated')->get();
        foreach ($rows as $row) {
            $id = (int) $row->christeningId;
            $applicationAt = $this->christeningApplicationTimestamp($id);
            $paymentAt = $this->paymentTimestamp($row);
            $certAt = Schema::hasTable('christening_certification')
                && DB::table('christening_certification')->where('christeningId', $id)->exists()
                ? $this->certificationTimestamp('christening_certification', 'christeningId', $id)
                : null;
            $scheduleAt = ! empty($row->scheduleRequested) ? $row->scheduleRequested : null;

            $this->updateWorkflowRow('christening', 'christeningId', $id, $applicationAt, $paymentAt, $certAt, $scheduleAt, true);
        }
    }

    private function backfillWeddingWorkflow(): void
    {
        if (! Schema::hasTable('wedding')) {
            return;
        }

        $rows = DB::table('wedding')->select('weddingId', 'paymentStatus', 'scheduleRequested', 'marriageApplication', 'dateCreated')->get();
        foreach ($rows as $row) {
            $id = (int) $row->weddingId;
            $applicationAt = $this->weddingApplicationTimestamp($row);
            $paymentAt = $this->paymentTimestamp($row);
            $certAt = Schema::hasTable('wedding_certification')
                && DB::table('wedding_certification')->where('weddingId', $id)->exists()
                ? $this->certificationTimestamp('wedding_certification', 'weddingId', $id)
                : null;
            $scheduleAt = ! empty($row->scheduleRequested) ? $row->scheduleRequested : null;

            $this->updateWorkflowRow('wedding', 'weddingId', $id, $applicationAt, $paymentAt, $certAt, $scheduleAt, true);
        }
    }

    private function backfillBurialWorkflow(): void
    {
        if (! Schema::hasTable('burial')) {
            return;
        }

        $rows = DB::table('burial')->select('burialId', 'paymentStatus', 'scheduleRequested', 'dateCreated')->get();
        foreach ($rows as $row) {
            $id = (int) $row->burialId;
            $applicationAt = $this->burialApplicationTimestamp($id);
            $paymentAt = $this->paymentTimestamp($row);
            $scheduleAt = ! empty($row->scheduleRequested) ? $row->scheduleRequested : null;

            $this->updateWorkflowRow('burial', 'burialId', $id, $applicationAt, $paymentAt, null, $scheduleAt, false);
        }
    }

    private function backfillConfirmationWorkflow(): void
    {
        if (! Schema::hasTable('confirmation')) {
            return;
        }

        $rows = DB::table('confirmation')->select('confirmationId', 'paymentStatus', 'scheduleRequested', 'confirmationApplication', 'dateCreated')->get();
        foreach ($rows as $row) {
            $id = (int) $row->confirmationId;
            $applicationAt = $this->confirmationApplicationTimestamp($id, $row);
            $paymentAt = $this->paymentTimestamp($row);
            $scheduleAt = ! empty($row->scheduleRequested) ? $row->scheduleRequested : null;

            $this->updateWorkflowRow('confirmation', 'confirmationId', $id, $applicationAt, $paymentAt, null, $scheduleAt, false);
        }
    }

    private function updateWorkflowRow(
        string $table,
        string $pk,
        int $id,
        mixed $applicationAt,
        mixed $paymentAt,
        mixed $certAt,
        mixed $scheduleAt,
        bool $hasCertificationStep,
    ): void {
        $step = self::WORKFLOW_APPLICATION;

        if ($applicationAt !== null) {
            $step = self::WORKFLOW_PAYMENT;
        }
        if ($paymentAt !== null) {
            $step = $hasCertificationStep ? self::WORKFLOW_CERTIFICATION : self::WORKFLOW_SCHEDULE;
        }
        if ($hasCertificationStep && $certAt !== null) {
            $step = self::WORKFLOW_SCHEDULE;
        }
        if ($scheduleAt !== null) {
            $step = self::WORKFLOW_COMPLETED;
        }

        DB::table($table)->where($pk, $id)->update([
            'workflowStep' => $step,
            'applicationCompletedAt' => $applicationAt,
            'paymentCompletedAt' => $paymentAt,
            'certificationCompletedAt' => $certAt,
            'scheduleCompletedAt' => $scheduleAt,
        ]);
    }

    private function christeningApplicationTimestamp(int $christeningId): mixed
    {
        if (! Schema::hasTable('christening_details')) {
            return null;
        }

        $details = DB::table('christening_details')
            ->where('christeningId', $christeningId)
            ->orderByDesc('christeningDetailsId')
            ->first();

        if ($details === null) {
            return null;
        }

        if (trim((string) ($details->firstName ?? '')) === '' || trim((string) ($details->familyName ?? '')) === '') {
            return null;
        }

        return $details->updated_at ?? $details->created_at ?? now();
    }

    private function weddingApplicationTimestamp(object $row): mixed
    {
        $raw = $row->marriageApplication ?? null;
        if ($raw === null || $raw === '') {
            return null;
        }

        $app = is_string($raw) ? json_decode($raw, true) : (is_array($raw) ? $raw : []);
        if (! is_array($app)) {
            return null;
        }

        $groomFirst = trim((string) ($app['first_name'] ?? ''));
        $groomLast = trim((string) ($app['family_name'] ?? ''));
        $bride = is_array($app['bride'] ?? null) ? $app['bride'] : [];
        $brideFirst = trim((string) ($bride['first_name'] ?? ''));
        $brideLast = trim((string) ($bride['family_name'] ?? ''));

        if ($groomFirst === '' || $groomLast === '' || $brideFirst === '' || $brideLast === '') {
            return null;
        }

        return now();
    }

    private function burialApplicationTimestamp(int $burialId): mixed
    {
        if (! Schema::hasTable('burial_details')) {
            return null;
        }

        $details = DB::table('burial_details')
            ->where('burialId', $burialId)
            ->orderByDesc('burialDetailsId')
            ->first();

        if ($details === null || trim((string) ($details->deceasedName ?? '')) === '') {
            return null;
        }

        return $details->updated_at ?? $details->created_at ?? now();
    }

    private function confirmationApplicationTimestamp(int $confirmationId, object $row): mixed
    {
        if (Schema::hasTable('confirmation_details')) {
            $details = DB::table('confirmation_details')
                ->where('confirmationId', $confirmationId)
                ->orderByDesc('confirmationDetailsId')
                ->first();

            if ($details !== null
                && trim((string) ($details->firstName ?? '')) !== ''
                && trim((string) ($details->familyName ?? '')) !== '') {
                return $details->updated_at ?? $details->created_at ?? now();
            }
        }

        $raw = $row->confirmationApplication ?? null;
        if ($raw === null || $raw === '') {
            return null;
        }

        $app = is_string($raw) ? json_decode($raw, true) : (is_array($raw) ? $raw : []);
        if (! is_array($app)) {
            return null;
        }

        if (trim((string) ($app['first_name'] ?? '')) === '' || trim((string) ($app['family_name'] ?? '')) === '') {
            return null;
        }

        return now();
    }

    private function certificationTimestamp(string $table, string $fk, int $id): mixed
    {
        $cert = DB::table($table)->where($fk, $id)->first();
        if ($cert === null) {
            return null;
        }

        return $cert->updated_at ?? $cert->created_at ?? now();
    }

    private function isPaidStatus(mixed $status): bool
    {
        return strtolower(trim((string) $status)) === 'paid';
    }

    private function paymentTimestamp(object $row): mixed
    {
        if (! $this->isPaidStatus($row->paymentStatus ?? null)) {
            return null;
        }

        return $row->dateCreated ?? now();
    }
};
