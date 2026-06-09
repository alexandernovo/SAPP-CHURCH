<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Baseline registry header tables aligned with the dashboard / records table UI.
 *
 * Workflow data lives in step tables (*_details, *_certification) and these headers
 * hold list columns: reference, client, contact, schedule, payment summary.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('christening')) {
            Schema::create('christening', function (Blueprint $table) {
                $table->bigIncrements('christeningId');
                $table->string('referenceCode', 64)->nullable()->unique();
                $table->text('clientFName')->nullable();
                $table->text('clientLName')->nullable();
                $table->text('clientMName')->nullable();
                $table->text('address')->nullable();
                $table->string('sex', 32)->nullable();
                $table->string('contactNum', 50)->nullable();
                $table->dateTime('scheduleRequested')->nullable()->index();
                $table->dateTime('dateCreated')->nullable()->index();
                $table->unsignedBigInteger('customerId')->nullable()->index();
                $table->string('paymentStatus', 32)->nullable()->default('Unpaid');
                $table->json('paymentFeeRows')->nullable();
            });
        }

        if (! Schema::hasTable('wedding')) {
            Schema::create('wedding', function (Blueprint $table) {
                $table->bigIncrements('weddingId');
                $table->string('referenceCode', 64)->nullable()->unique();
                $table->text('clientFName')->nullable();
                $table->text('clientLName')->nullable();
                $table->text('clientMName')->nullable();
                $table->text('address')->nullable();
                $table->string('sex', 32)->nullable();
                $table->string('contactNum', 50)->nullable();
                $table->dateTime('dateCreated')->nullable()->index();
                $table->unsignedBigInteger('customerId')->nullable()->index();
                $table->dateTime('scheduleRequested')->nullable()->index();
                $table->string('paymentStatus', 32)->nullable()->default('Unpaid');
                $table->json('paymentFeeRows')->nullable();
                $table->json('marriageApplication')->nullable();
            });
        }

        if (! Schema::hasTable('burial')) {
            Schema::create('burial', function (Blueprint $table) {
                $table->bigIncrements('burialId');
                $table->string('referenceCode', 64)->nullable()->unique();
                $table->text('clientFName')->nullable();
                $table->text('clientLName')->nullable();
                $table->text('clientMName')->nullable();
                $table->text('address')->nullable();
                $table->string('sex', 32)->nullable();
                $table->string('contactNum', 50)->nullable();
                $table->string('paymentStatus', 32)->nullable()->default('Unpaid');
                $table->dateTime('dateCreated')->nullable()->index();
                $table->unsignedBigInteger('customerId')->nullable()->index();
                $table->dateTime('scheduleRequested')->nullable()->index();
                $table->json('paymentFeeRows')->nullable();
                $table->json('burialApplication')->nullable();
            });
        }

        if (! Schema::hasTable('confirmation')) {
            Schema::create('confirmation', function (Blueprint $table) {
                $table->bigIncrements('confirmationId');
                $table->string('referenceCode', 64)->nullable()->unique();
                $table->text('clientFName')->nullable();
                $table->text('clientLName')->nullable();
                $table->text('clientMName')->nullable();
                $table->text('address')->nullable();
                $table->string('sex', 32)->nullable();
                $table->string('contactNum', 50)->nullable();
                $table->dateTime('dateCreated')->nullable()->index();
                $table->unsignedBigInteger('customerId')->nullable()->index();
                $table->dateTime('scheduleRequested')->nullable()->index();
                $table->string('paymentStatus', 32)->nullable()->default('Unpaid');
                $table->json('paymentFeeRows')->nullable();
                $table->json('confirmationApplication')->nullable();
                $table->json('confirmationArancel')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('confirmation');
        Schema::dropIfExists('burial');
        Schema::dropIfExists('wedding');
        Schema::dropIfExists('christening');
    }
};
