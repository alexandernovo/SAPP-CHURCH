<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wedding', function (Blueprint $table) {
            if (! Schema::hasColumn('wedding', 'scheduleRequested')) {
                $table->dateTime('scheduleRequested')->nullable();
            }
            if (! Schema::hasColumn('wedding', 'paymentStatus')) {
                $table->string('paymentStatus', 50)->nullable();
            }
            if (! Schema::hasColumn('wedding', 'paymentFeeRows')) {
                $table->json('paymentFeeRows')->nullable();
            }
        });

        Schema::table('burial', function (Blueprint $table) {
            if (! Schema::hasColumn('burial', 'scheduleRequested')) {
                $table->dateTime('scheduleRequested')->nullable();
            }
            if (! Schema::hasColumn('burial', 'paymentFeeRows')) {
                $table->json('paymentFeeRows')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('wedding', function (Blueprint $table) {
            if (Schema::hasColumn('wedding', 'scheduleRequested')) {
                $table->dropColumn('scheduleRequested');
            }
            if (Schema::hasColumn('wedding', 'paymentStatus')) {
                $table->dropColumn('paymentStatus');
            }
            if (Schema::hasColumn('wedding', 'paymentFeeRows')) {
                $table->dropColumn('paymentFeeRows');
            }
        });

        Schema::table('burial', function (Blueprint $table) {
            if (Schema::hasColumn('burial', 'scheduleRequested')) {
                $table->dropColumn('scheduleRequested');
            }
            if (Schema::hasColumn('burial', 'paymentFeeRows')) {
                $table->dropColumn('paymentFeeRows');
            }
        });
    }
};
