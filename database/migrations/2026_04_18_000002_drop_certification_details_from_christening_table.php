<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('christening')) {
            return;
        }
        Schema::table('christening', function (Blueprint $table) {
            if (Schema::hasColumn('christening', 'certificationDetails')) {
                $table->dropColumn('certificationDetails');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('christening')) {
            return;
        }
        Schema::table('christening', function (Blueprint $table) {
            if (! Schema::hasColumn('christening', 'certificationDetails')) {
                $table->json('certificationDetails')->nullable();
            }
        });
    }
};
