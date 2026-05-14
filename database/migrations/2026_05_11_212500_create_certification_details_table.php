<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certification_details', function (Blueprint $table) {
            $table->bigIncrements('certificationDetailsId');

            $table->string('referenceCode', 100)->nullable();
            $table->text('client')->nullable();
            $table->text('address')->nullable();
            $table->text('sex')->nullable();
            $table->text('contactNumber')->nullable();
            $table->date('date')->nullable()->index();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certification_details');
    }
};
