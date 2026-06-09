<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Baseline customer table (legacy name: customer).
 * Linked from all sacrament registry header rows via customerId.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('customer')) {
            return;
        }

        Schema::create('customer', function (Blueprint $table) {
            $table->bigIncrements('customerId');
            $table->text('customerFName')->nullable();
            $table->text('customerLName')->nullable();
            $table->text('customerMName')->nullable();
            $table->dateTime('updatedAt')->nullable();
            $table->text('createdBy')->nullable();
            $table->unsignedBigInteger('userId')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer');
    }
};
