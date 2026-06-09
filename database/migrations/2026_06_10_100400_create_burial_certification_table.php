<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Burial certification summary (1:1 with burial header).
 * Christening and wedding already use dedicated *_certification tables;
 * burial previously wrote only to certification_details without a parent link.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('burial_certification')) {
            return;
        }

        Schema::create('burial_certification', function (Blueprint $table) {
            $table->bigIncrements('burialCertificationId');
            $table->unsignedBigInteger('burialId')->unique();

            $table->string('referenceCode', 100)->nullable();
            $table->text('client')->nullable();
            $table->text('address')->nullable();
            $table->text('sex')->nullable();
            $table->text('contactNumber')->nullable();
            $table->date('dateIssued')->nullable();

            $table->timestamps();

            $table->index('burialId');
        });

        $this->backfillFromCertificationDetails();
    }

    public function down(): void
    {
        Schema::dropIfExists('burial_certification');
    }

    private function backfillFromCertificationDetails(): void
    {
        if (! Schema::hasTable('certification_details') || ! Schema::hasTable('burial')) {
            return;
        }

        $burials = DB::table('burial')->select('burialId', 'referenceCode', 'sex')->get();
        foreach ($burials as $burial) {
            $burialId = (int) $burial->burialId;

            if (DB::table('burial_certification')->where('burialId', $burialId)->exists()) {
                continue;
            }

            $cert = DB::table('certification_details')
                ->where(function ($query) use ($burialId, $burial) {
                    $query->where(function ($inner) use ($burialId) {
                        $inner->where('registryType', 'Burial')
                            ->where('registryRecordId', $burialId);
                    });

                    $reference = trim((string) ($burial->referenceCode ?? ''));
                    if ($reference !== '') {
                        $query->orWhere('referenceCode', $reference);
                    }
                })
                ->orderByDesc('certificationDetailsId')
                ->first();

            if ($cert === null) {
                continue;
            }

            DB::table('burial_certification')->insert([
                'burialId' => $burialId,
                'referenceCode' => $cert->referenceCode ?? $burial->referenceCode ?? null,
                'client' => $cert->client ?? null,
                'address' => $cert->address ?? null,
                'sex' => $cert->sex ?? $burial->sex ?? null,
                'contactNumber' => $cert->contactNumber ?? null,
                'dateIssued' => $cert->date ?? null,
                'created_at' => $cert->created_at ?? now(),
                'updated_at' => $cert->updated_at ?? now(),
            ]);
        }
    }
};
