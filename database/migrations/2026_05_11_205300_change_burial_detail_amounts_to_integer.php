<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('burial_details')) {
            return;
        }

        $columns = [
            'arPanteonAmount',
            'arLandAmount',
            'arKalkalAmount',
            'arCemeteryAmount',
            'arMassAmount',
            'arProrogaAmount',
            'arOthersAmount',
            'arExtra1Amount',
            'arExtra2Amount',
        ];

        foreach ($columns as $col) {
            if (Schema::hasColumn('burial_details', $col)) {
                DB::statement("ALTER TABLE `burial_details` MODIFY `{$col}` INT NULL");
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('burial_details')) {
            return;
        }

        $columns = [
            'arPanteonAmount',
            'arLandAmount',
            'arKalkalAmount',
            'arCemeteryAmount',
            'arMassAmount',
            'arProrogaAmount',
            'arOthersAmount',
            'arExtra1Amount',
            'arExtra2Amount',
        ];

        foreach ($columns as $col) {
            if (Schema::hasColumn('burial_details', $col)) {
                DB::statement("ALTER TABLE `burial_details` MODIFY `{$col}` DECIMAL(10,2) NULL");
            }
        }
    }
};
