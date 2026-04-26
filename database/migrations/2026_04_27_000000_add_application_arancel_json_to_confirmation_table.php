<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('confirmation', function (Blueprint $table) {
            if (! Schema::hasColumn('confirmation', 'confirmationApplication')) {
                $table->json('confirmationApplication')->nullable();
            }
            if (! Schema::hasColumn('confirmation', 'confirmationArancel')) {
                $table->json('confirmationArancel')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('confirmation', function (Blueprint $table) {
            if (Schema::hasColumn('confirmation', 'confirmationApplication')) {
                $table->dropColumn('confirmationApplication');
            }
            if (Schema::hasColumn('confirmation', 'confirmationArancel')) {
                $table->dropColumn('confirmationArancel');
            }
        });
    }
};
