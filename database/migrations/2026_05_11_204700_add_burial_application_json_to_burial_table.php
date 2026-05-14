<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('burial')) {
            return;
        }

        Schema::table('burial', function (Blueprint $table) {
            if (! Schema::hasColumn('burial', 'burialApplication')) {
                $table->json('burialApplication')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('burial')) {
            return;
        }

        Schema::table('burial', function (Blueprint $table) {
            if (Schema::hasColumn('burial', 'burialApplication')) {
                $table->dropColumn('burialApplication');
            }
        });
    }
};
