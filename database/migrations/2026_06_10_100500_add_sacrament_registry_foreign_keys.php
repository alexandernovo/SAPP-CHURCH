<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Add referential integrity between registry headers and step tables.
 * Foreign keys are skipped when orphan rows exist so existing data is not blocked.
 */
return new class extends Migration
{
    /** @var list<array{child: string, fk: string, parent: string, pk: string}> */
    private const ONE_TO_ONE_LINKS = [
        ['child' => 'christening_details', 'fk' => 'christeningId', 'parent' => 'christening', 'pk' => 'christeningId'],
        ['child' => 'wedding_details', 'fk' => 'weddingId', 'parent' => 'wedding', 'pk' => 'weddingId'],
        ['child' => 'burial_details', 'fk' => 'burialId', 'parent' => 'burial', 'pk' => 'burialId'],
        ['child' => 'confirmation_details', 'fk' => 'confirmationId', 'parent' => 'confirmation', 'pk' => 'confirmationId'],
        ['child' => 'christening_certification', 'fk' => 'christeningId', 'parent' => 'christening', 'pk' => 'christeningId'],
        ['child' => 'wedding_certification', 'fk' => 'weddingId', 'parent' => 'wedding', 'pk' => 'weddingId'],
        ['child' => 'burial_certification', 'fk' => 'burialId', 'parent' => 'burial', 'pk' => 'burialId'],
    ];

    public function up(): void
    {
        foreach (self::ONE_TO_ONE_LINKS as $link) {
            $this->ensureIndex($link['child'], $link['fk']);
            $this->addForeignKeyIfClean($link['child'], $link['fk'], $link['parent'], $link['pk']);
        }

        foreach (['christening', 'wedding', 'burial', 'confirmation'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'customerId')) {
                $this->ensureIndex($table, 'customerId');
                if (Schema::hasTable('customer')) {
                    $this->addForeignKeyIfClean($table, 'customerId', 'customer', 'customerId');
                }
            }
        }
    }

    public function down(): void
    {
        foreach (array_reverse(self::ONE_TO_ONE_LINKS) as $link) {
            $this->dropForeignKeyIfExists($link['child'], $this->foreignKeyName($link['child'], $link['fk']));
        }

        foreach (['christening', 'wedding', 'burial', 'confirmation'] as $table) {
            $this->dropForeignKeyIfExists($table, $this->foreignKeyName($table, 'customerId'));
        }
    }

    private function ensureIndex(string $table, string $column): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return;
        }

        if ($this->indexExists($table, "{$table}_{$column}_index")) {
            return;
        }

        try {
            Schema::table($table, function (Blueprint $blueprint) use ($column) {
                $blueprint->index($column);
            });
        } catch (\Throwable) {
        }
    }

    private function addForeignKeyIfClean(string $childTable, string $childColumn, string $parentTable, string $parentColumn): void
    {
        if (! Schema::hasTable($childTable) || ! Schema::hasTable($parentTable)) {
            return;
        }
        if (! Schema::hasColumn($childTable, $childColumn) || ! Schema::hasColumn($parentTable, $parentColumn)) {
            return;
        }

        $fkName = $this->foreignKeyName($childTable, $childColumn);
        if ($this->foreignKeyExists($childTable, $fkName)) {
            return;
        }

        $orphans = DB::table($childTable)
            ->whereNotNull($childColumn)
            ->whereNotIn($childColumn, DB::table($parentTable)->select($parentColumn))
            ->count();

        if ($orphans > 0) {
            return;
        }

        try {
            Schema::table($childTable, function (Blueprint $blueprint) use ($childColumn, $parentTable, $parentColumn, $fkName) {
                $blueprint->foreign($childColumn, $fkName)
                    ->references($parentColumn)
                    ->on($parentTable)
                    ->cascadeOnDelete();
            });
        } catch (\Throwable) {
            
        }
    }

    private function foreignKeyName(string $table, string $column): string
    {
        return "{$table}_{$column}_fk";
    }

    private function foreignKeyExists(string $table, string $fkName): bool
    {
        $database = DB::getDatabaseName();

        return DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', $database)
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_NAME', $fkName)
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->exists();
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $database = DB::getDatabaseName();

        return DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', $database)
            ->where('TABLE_NAME', $table)
            ->where('INDEX_NAME', $indexName)
            ->exists();
    }

    private function dropForeignKeyIfExists(string $table, string $fkName): void
    {
        if (! Schema::hasTable($table) || ! $this->foreignKeyExists($table, $fkName)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($fkName) {
            $blueprint->dropForeign($fkName);
        });
    }
};
