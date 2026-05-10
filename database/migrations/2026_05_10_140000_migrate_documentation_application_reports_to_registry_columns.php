<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Upgrades an existing documentation_application_reports table from the
 * applicant_name / application_recorded_at shape to client_name, address,
 * sex, contact_number, and reported_at (text + int + timestamps only).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('documentation_application_reports')) {
            return;
        }

        if (Schema::hasColumn('documentation_application_reports', 'client_name')) {
            return;
        }

        foreach (['doc_app_rpt_svc_reg_uq', 'doc_app_rpt_svc_mo_ix'] as $indexName) {
            try {
                DB::statement('ALTER TABLE `documentation_application_reports` DROP INDEX `'.$indexName.'`');
            } catch (\Throwable) {
                
            }
        }

        Schema::table('documentation_application_reports', function (Blueprint $table) {
            $table->text('client_name')->nullable()->after('reference_code');
            $table->text('address')->nullable()->after('client_name');
            $table->text('sex')->nullable()->after('address');
            $table->text('contact_number')->nullable()->after('sex');
            $table->timestamp('reported_at')->nullable()->after('report_month');
        });

        if (Schema::hasColumn('documentation_application_reports', 'applicant_name')) {
            DB::statement('UPDATE `documentation_application_reports` SET `client_name` = `applicant_name` WHERE `client_name` IS NULL');
        }
        if (Schema::hasColumn('documentation_application_reports', 'application_recorded_at')) {
            DB::statement('UPDATE `documentation_application_reports` SET `reported_at` = `application_recorded_at` WHERE `reported_at` IS NULL');
        }

        Schema::table('documentation_application_reports', function (Blueprint $table) {
            if (Schema::hasColumn('documentation_application_reports', 'applicant_name')) {
                $table->dropColumn('applicant_name');
            }
            if (Schema::hasColumn('documentation_application_reports', 'application_recorded_at')) {
                $table->dropColumn('application_recorded_at');
            }
        });

        try {
            DB::statement('ALTER TABLE `documentation_application_reports` ADD UNIQUE `doc_app_rpt_svc_reg_uq` (`service_type`(64), `registry_id`)');
        } catch (\Throwable) {
            //
        }
        try {
            DB::statement('ALTER TABLE `documentation_application_reports` ADD INDEX `doc_app_rpt_svc_mo_ix` (`service_type`(32), `report_month`(10))');
        } catch (\Throwable) {
            //
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('documentation_application_reports')) {
            return;
        }

        if (! Schema::hasColumn('documentation_application_reports', 'client_name')) {
            return;
        }

        foreach (['doc_app_rpt_svc_reg_uq', 'doc_app_rpt_svc_mo_ix'] as $indexName) {
            try {
                DB::statement('ALTER TABLE `documentation_application_reports` DROP INDEX `'.$indexName.'`');
            } catch (\Throwable) {
                
            }
        }

        Schema::table('documentation_application_reports', function (Blueprint $table) {
            $table->text('applicant_name')->nullable()->after('registry_id');
            $table->timestamp('application_recorded_at')->nullable()->after('report_month');
        });

        DB::statement('UPDATE `documentation_application_reports` SET `applicant_name` = `client_name`');
        DB::statement('UPDATE `documentation_application_reports` SET `application_recorded_at` = `reported_at`');

        Schema::table('documentation_application_reports', function (Blueprint $table) {
            $table->dropColumn(['client_name', 'address', 'sex', 'contact_number', 'reported_at']);
        });

        try {
            DB::statement('ALTER TABLE `documentation_application_reports` ADD UNIQUE `doc_app_rpt_svc_reg_uq` (`service_type`(64), `registry_id`)');
        } catch (\Throwable) {
            //
        }
        try {
            DB::statement('ALTER TABLE `documentation_application_reports` ADD INDEX `doc_app_rpt_svc_mo_ix` (`service_type`(32), `report_month`(10))');
        } catch (\Throwable) {
            //
        }
    }
};
