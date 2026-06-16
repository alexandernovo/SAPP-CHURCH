<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $columnsByTable = [
            'christening' => [
                'referenceCode' => 'VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL',
            ],
            'confirmation' => [
                'referenceCode' => 'VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL',
            ],
            'wedding' => [
                'referenceCode' => 'VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL',
            ],
            'burial' => [
                'referenceCode' => 'VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL',
            ],
            'certification_details' => [
                'referenceCode' => 'VARCHAR(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL',
                'registryType' => 'VARCHAR(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL',
            ],
        ];

        foreach ($columnsByTable as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($columns as $column => $definition) {
                if (! Schema::hasColumn($table, $column)) {
                    continue;
                }

                DB::statement(
                    "ALTER TABLE `{$table}` MODIFY `{$column}` {$definition}"
                );
            }
        }
    }

    public function down(): void
    {
        // Collation normalization is safe to keep; no rollback required.
    }
};
