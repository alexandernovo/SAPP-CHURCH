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
            if (! Schema::hasColumn('christening', 'paymentFeeRows')) {
                $table->json('paymentFeeRows')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('christening')) {
            return;
        }
        Schema::table('christening', function (Blueprint $table) {
            if (Schema::hasColumn('christening', 'paymentFeeRows')) {
                $table->dropColumn('paymentFeeRows');
            }
        });
    }
};
