<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('confirmation_details', function (Blueprint $table) {
            $table->bigIncrements('confirmationDetailsId');

            $table->unsignedBigInteger('confirmationId')->nullable()->unique()->index();

            $table->text('firstName')->nullable();
            $table->text('middleName')->nullable();
            $table->text('familyName')->nullable();
            $table->date('dateOfBirth')->nullable();
            $table->text('placeOfBirth')->nullable();
            $table->text('fatherName')->nullable();
            $table->text('motherMaiden')->nullable();
            $table->text('address')->nullable();

            $table->date('baptismDate')->nullable();
            $table->text('baptismPlace')->nullable();
            $table->text('ministerBaptism')->nullable();

            $table->text('bookNo')->nullable();
            $table->text('pageNo')->nullable();
            $table->text('registryNo')->nullable();

            $table->date('confirmationDate')->nullable();
            $table->text('confirmationMinister')->nullable();

            $table->text('godparent1')->nullable();
            $table->text('godparent2')->nullable();
            $table->text('godparent3')->nullable();
            $table->text('godparent4')->nullable();

            $table->decimal('feeArancel', 10, 2)->nullable();
            $table->decimal('feeCandle', 10, 2)->nullable();
            $table->decimal('feeGodparents', 10, 2)->nullable();
            $table->text('otherFeeLabel1')->nullable();
            $table->text('otherFeeLabel2')->nullable();
            $table->text('otherFeeLabel3')->nullable();
            $table->decimal('otherFeeAmount1', 10, 2)->nullable();
            $table->decimal('otherFeeAmount2', 10, 2)->nullable();
            $table->decimal('otherFeeAmount3', 10, 2)->nullable();
            $table->decimal('feeTotal', 10, 2)->nullable();

            $table->text('approvedByBpcChairman')->nullable();
            $table->text('approvedByParishSecretary')->nullable();
            $table->text('approvedByPresacramentalInstructor')->nullable();
            $table->text('approvedByParishPriest')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('confirmation_details');
    }
};
