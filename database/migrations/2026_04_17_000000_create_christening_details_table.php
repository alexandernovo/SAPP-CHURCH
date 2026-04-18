<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('christening_details', function (Blueprint $table) {
            $table->bigIncrements('christeningDetailsId');

            $table->unsignedBigInteger('christeningId')->nullable()->index();

            // Child — FIRST / MIDDLE / FAMILY NAME, DATE & PLACE OF BIRTH, REGISTRY NUMBER
            $table->text('firstName')->nullable();
            $table->text('middleName')->nullable();
            $table->text('familyName')->nullable();
            $table->date('dateOfBirth')->nullable();
            $table->text('birthRegistryNumber')->nullable();
            $table->text('placeOfBirth')->nullable();

            // Parents
            $table->text('fatherName')->nullable();
            $table->text('motherMaidenName')->nullable();
            $table->text('parentAddress')->nullable();
            /** e.g. civilly_married, married_other_denomination, church_marriage, not_yet_married, single_parent */
            $table->text('parentStatus')->nullable();

            $table->date('civillyMarriedDate')->nullable();
            $table->text('civillyMarriedPlace')->nullable();

            $table->date('marriedOtherDenominationDate')->nullable();
            $table->text('marriedOtherDenominationPlace')->nullable();

            $table->date('churchMarriageDate')->nullable();
            $table->text('churchMarriagePlace')->nullable();

            $table->text('marriageContractNumber')->nullable();

            $table->text('parentGuardianContact')->nullable();
            $table->date('dateOfBaptism')->nullable();
            $table->text('placeOfBaptism')->nullable();
            $table->text('ministerOfSacrament')->nullable();

            $table->unsignedTinyInteger('age')->nullable();

            $table->decimal('feeArancel', 10, 2)->nullable();
            $table->decimal('feeBaptismalSymbols', 10, 2)->nullable();
            $table->decimal('feeGodparents', 10, 2)->nullable();
            $table->decimal('feeParentsSeminar', 10, 2)->nullable();
            $table->decimal('feeOthers', 10, 2)->nullable();
            $table->decimal('feeTotal', 10, 2)->nullable();

            $table->json('godparents')->nullable();

            $table->text('approvedByBpcChairman')->nullable();
            $table->text('approvedByPreJordanInstructor')->nullable();
            $table->text('approvedByParishSecretary')->nullable();
            $table->text('approvedByParishPriest')->nullable();

            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('christening_details');
    }
};
