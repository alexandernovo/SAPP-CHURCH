<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('burial_details')) {
            Schema::create('burial_details', function (Blueprint $table) {
                $table->bigIncrements('burialDetailsId');
                $table->unsignedBigInteger('burialId')->nullable()->unique()->index();

                $table->text('deceasedName')->nullable();
                $table->string('deceasedAge', 16)->nullable();
                $table->string('maritalStatus', 32)->nullable();
                $table->text('spouseName')->nullable();
                $table->text('deceasedAddress')->nullable();
                $table->text('kinamatyan')->nullable();
                $table->text('occupation')->nullable();

                $table->text('claimantName')->nullable();
                $table->text('claimantRelation')->nullable();
                $table->text('claimantPlace')->nullable();

                $table->text('churchObligation')->nullable();
                $table->string('parishBec', 16)->nullable();
                $table->text('becSelda')->nullable();
                $table->string('stewardship', 16)->nullable();
                $table->string('baptizedSacrament', 16)->nullable();
                $table->date('baptismDate')->nullable();
                $table->date('deathDate')->nullable();
                $table->date('burialDate')->nullable();
                $table->time('burialTime')->nullable();
                $table->text('burialPermitNo')->nullable();

                $table->text('minorFatherName')->nullable();
                $table->text('minorMotherName')->nullable();

                $table->string('ceremonyType', 32)->nullable();
                $table->string('intermentType', 32)->nullable();
                $table->string('nicheNo', 64)->nullable();

                $table->decimal('arPanteonAmount', 10, 2)->nullable();
                $table->text('arPanteonRemarks')->nullable();
                $table->decimal('arLandAmount', 10, 2)->nullable();
                $table->text('arLandRemarks')->nullable();
                $table->decimal('arKalkalAmount', 10, 2)->nullable();
                $table->text('arKalkalRemarks')->nullable();
                $table->decimal('arCemeteryAmount', 10, 2)->nullable();
                $table->text('arCemeteryRemarks')->nullable();
                $table->decimal('arMassAmount', 10, 2)->nullable();
                $table->text('arMassRemarks')->nullable();
                $table->decimal('arProrogaAmount', 10, 2)->nullable();
                $table->text('arProrogaRemarks')->nullable();
                $table->decimal('arOthersAmount', 10, 2)->nullable();
                $table->text('arOthersRemarks')->nullable();
                $table->decimal('arExtra1Amount', 10, 2)->nullable();
                $table->text('arExtra1Remarks')->nullable();
                $table->decimal('arExtra2Amount', 10, 2)->nullable();
                $table->text('arExtra2Remarks')->nullable();

                $table->text('notedByBpcChairman')->nullable();
                $table->text('notedByParishFiscalSecretary')->nullable();
                $table->text('approvedByParishPriest')->nullable();

                $table->timestamps();
            });

            return;
        }

        Schema::table('burial_details', function (Blueprint $table) {
            if (! Schema::hasColumn('burial_details', 'burialId')) {
                $table->unsignedBigInteger('burialId')->nullable()->unique()->index();
            }

            $textCols = [
                'deceasedName', 'spouseName', 'deceasedAddress', 'kinamatyan', 'occupation',
                'claimantName', 'claimantRelation', 'claimantPlace', 'churchObligation', 'becSelda',
                'burialPermitNo', 'minorFatherName', 'minorMotherName', 'arPanteonRemarks',
                'arLandRemarks', 'arKalkalRemarks', 'arCemeteryRemarks', 'arMassRemarks',
                'arProrogaRemarks', 'arOthersRemarks', 'arExtra1Remarks', 'arExtra2Remarks',
                'notedByBpcChairman', 'notedByParishFiscalSecretary', 'approvedByParishPriest',
            ];
            foreach ($textCols as $col) {
                if (! Schema::hasColumn('burial_details', $col)) {
                    $table->text($col)->nullable();
                }
            }

            if (! Schema::hasColumn('burial_details', 'deceasedAge')) {
                $table->string('deceasedAge', 16)->nullable();
            }
            if (! Schema::hasColumn('burial_details', 'maritalStatus')) {
                $table->string('maritalStatus', 32)->nullable();
            }
            if (! Schema::hasColumn('burial_details', 'parishBec')) {
                $table->string('parishBec', 16)->nullable();
            }
            if (! Schema::hasColumn('burial_details', 'stewardship')) {
                $table->string('stewardship', 16)->nullable();
            }
            if (! Schema::hasColumn('burial_details', 'baptizedSacrament')) {
                $table->string('baptizedSacrament', 16)->nullable();
            }
            if (! Schema::hasColumn('burial_details', 'ceremonyType')) {
                $table->string('ceremonyType', 32)->nullable();
            }
            if (! Schema::hasColumn('burial_details', 'intermentType')) {
                $table->string('intermentType', 32)->nullable();
            }
            if (! Schema::hasColumn('burial_details', 'nicheNo')) {
                $table->string('nicheNo', 64)->nullable();
            }

            if (! Schema::hasColumn('burial_details', 'baptismDate')) {
                $table->date('baptismDate')->nullable();
            }
            if (! Schema::hasColumn('burial_details', 'deathDate')) {
                $table->date('deathDate')->nullable();
            }
            if (! Schema::hasColumn('burial_details', 'burialDate')) {
                $table->date('burialDate')->nullable();
            }
            if (! Schema::hasColumn('burial_details', 'burialTime')) {
                $table->time('burialTime')->nullable();
            }

            $decimalCols = [
                'arPanteonAmount', 'arLandAmount', 'arKalkalAmount', 'arCemeteryAmount',
                'arMassAmount', 'arProrogaAmount', 'arOthersAmount', 'arExtra1Amount', 'arExtra2Amount',
            ];
            foreach ($decimalCols as $col) {
                if (! Schema::hasColumn('burial_details', $col)) {
                    $table->decimal($col, 10, 2)->nullable();
                }
            }

            if (! Schema::hasColumn('burial_details', 'created_at') && ! Schema::hasColumn('burial_details', 'updated_at')) {
                $table->timestamps();
            }
        });
    }

    public function down(): void
    {
        
    }
};
