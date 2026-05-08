<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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
    }


    public function down(): void
    {
        Schema::dropIfExists('burial_details');
    }
};
