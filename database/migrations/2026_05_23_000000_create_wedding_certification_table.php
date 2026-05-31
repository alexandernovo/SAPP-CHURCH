<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wedding_certification', function (Blueprint $table) {
            $table->bigIncrements('weddingCertificationId');
            $table->unsignedBigInteger('weddingId')->unique();

            $table->text('groomFirstName')->nullable();
            $table->text('groomMiddleName')->nullable();
            $table->text('groomFamilyName')->nullable();
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

            $table->index('weddingId');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wedding_certification');
    }
};
