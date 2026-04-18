<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('christening_certification', function (Blueprint $table) {
            $table->bigIncrements('christeningCertificationId');
            $table->unsignedBigInteger('christeningId')->unique();

            $table->text('childFirstName')->nullable();
            $table->text('childMiddleName')->nullable();
            $table->text('childFamilyName')->nullable();
            $table->date('dateOfBirth')->nullable();
            $table->text('placeOfBirth')->nullable();

            $table->text('fatherFirstName')->nullable();
            $table->text('fatherMiddleName')->nullable();
            $table->text('fatherLastName')->nullable();
            $table->text('motherFirstName')->nullable();
            $table->text('motherMiddleName')->nullable();
            $table->text('motherLastName')->nullable();

            $table->text('addressBarangay')->nullable();
            $table->text('addressMunicipality')->nullable();
            $table->text('addressProvince')->nullable();

            $table->date('certDateReceived')->nullable();
            $table->date('certDateIssued')->nullable();
            $table->text('priest')->nullable();
            $table->text('certSponsors')->nullable();
            $table->text('certPurpose')->nullable();
            $table->text('certBookNo')->nullable();
            $table->text('certRegisterNo')->nullable();
            $table->text('certPageNo')->nullable();

            $table->timestamps();

            $table->index('christeningId');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('christening_certification');
    }
};
