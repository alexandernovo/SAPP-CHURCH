<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('documentation_application_reports');

        Schema::create('documentation_application_reports', function (Blueprint $table) {
            $table->increments('id');

            $table->text('service_type');
            $table->integer('registry_id');

            $table->text('reference_code')->nullable();
            $table->text('client_name')->nullable();
            $table->text('address')->nullable();
            $table->text('sex')->nullable();
            $table->text('contact_number')->nullable();

            $table->text('report_month');

            $table->timestamp('reported_at')->nullable();

            $table->timestamps();
        });

        /** TEXT columns need prefix length for MySQL indexes */
        DB::statement('ALTER TABLE `documentation_application_reports` ADD UNIQUE `doc_app_rpt_svc_reg_uq` (`service_type`(64), `registry_id`)');
        DB::statement('ALTER TABLE `documentation_application_reports` ADD INDEX `doc_app_rpt_svc_mo_ix` (`service_type`(32), `report_month`(10))');
    }

    public function down(): void
    {
        Schema::dropIfExists('documentation_application_reports');
    }
};
