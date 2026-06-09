<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * certification_details is the issued-certificate log used by all sacrament modules.
 * Link each row back to its registry header for reporting and workflow queries.
 */
return new class extends Migration
{
    private const TYPES = [
        'christening' => 'Christening',
        'wedding' => 'Wedding',
        'burial' => 'Burial',
        'confirmation' => 'Confirmation',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('certification_details')) {
            Schema::create('certification_details', function (Blueprint $table) {
                $table->bigIncrements('certificationDetailsId');
                $table->string('registryType', 32)->nullable()->index();
                $table->unsignedBigInteger('registryRecordId')->nullable()->index();
                $table->string('referenceCode', 100)->nullable()->index();
                $table->text('client')->nullable();
                $table->text('address')->nullable();
                $table->text('sex')->nullable();
                $table->text('contactNumber')->nullable();
                $table->date('date')->nullable()->index();
                $table->timestamps();
            });

            return;
        }

        Schema::table('certification_details', function (Blueprint $table) {
            if (! Schema::hasColumn('certification_details', 'registryType')) {
                $table->string('registryType', 32)->nullable()->index();
            }
            if (! Schema::hasColumn('certification_details', 'registryRecordId')) {
                $table->unsignedBigInteger('registryRecordId')->nullable()->index();
            }
        });

        $this->backfillRegistryLinks();
    }

    public function down(): void
    {
        if (! Schema::hasTable('certification_details')) {
            return;
        }

        Schema::table('certification_details', function (Blueprint $table) {
            foreach (['registryType', 'registryRecordId'] as $column) {
                if (Schema::hasColumn('certification_details', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    private function backfillRegistryLinks(): void
    {
        $rows = DB::table('certification_details')
            ->select('certificationDetailsId', 'referenceCode', 'registryType', 'registryRecordId')
            ->where(function ($query) {
                $query->whereNull('registryType')
                    ->orWhereNull('registryRecordId');
            })
            ->get();

        foreach ($rows as $row) {
            $reference = trim((string) ($row->referenceCode ?? ''));
            if ($reference === '') {
                continue;
            }

            foreach (self::TYPES as $table => $typeLabel) {
                if (! Schema::hasTable($table)) {
                    continue;
                }

                $pk = match ($table) {
                    'christening' => 'christeningId',
                    'wedding' => 'weddingId',
                    'burial' => 'burialId',
                    'confirmation' => 'confirmationId',
                };

                $registry = DB::table($table)
                    ->where('referenceCode', $reference)
                    ->orderByDesc($pk)
                    ->first();

                if ($registry === null) {
                    continue;
                }

                DB::table('certification_details')
                    ->where('certificationDetailsId', $row->certificationDetailsId)
                    ->update([
                        'registryType' => $typeLabel,
                        'registryRecordId' => (int) $registry->{$pk},
                    ]);

                break;
            }
        }
    }
};
