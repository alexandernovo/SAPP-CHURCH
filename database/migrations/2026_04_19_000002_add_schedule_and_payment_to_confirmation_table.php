<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('confirmation', function (Blueprint $table) {
            if (! Schema::hasColumn('confirmation', 'scheduleRequested')) {
                $table->dateTime('scheduleRequested')->nullable();
            }
            if (! Schema::hasColumn('confirmation', 'paymentStatus')) {
                $table->string('paymentStatus', 50)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('confirmation', function (Blueprint $table) {
            if (Schema::hasColumn('confirmation', 'scheduleRequested')) {
                $table->dropColumn('scheduleRequested');
            }
            if (Schema::hasColumn('confirmation', 'paymentStatus')) {
                $table->dropColumn('paymentStatus');
            }
        });
    }
};
