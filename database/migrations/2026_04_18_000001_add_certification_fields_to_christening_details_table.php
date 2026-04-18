<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('christening_details')) {
            return;
        }
        Schema::table('christening_details', function (Blueprint $table) {
            if (! Schema::hasColumn('christening_details', 'addressBarangay')) {
                $table->text('addressBarangay')->nullable();
            }
            if (! Schema::hasColumn('christening_details', 'addressMunicipality')) {
                $table->text('addressMunicipality')->nullable();
            }
            if (! Schema::hasColumn('christening_details', 'addressProvince')) {
                $table->text('addressProvince')->nullable();
            }
            if (! Schema::hasColumn('christening_details', 'fatherFirstName')) {
                $table->text('fatherFirstName')->nullable();
            }
            if (! Schema::hasColumn('christening_details', 'fatherMiddleName')) {
                $table->text('fatherMiddleName')->nullable();
            }
            if (! Schema::hasColumn('christening_details', 'fatherLastName')) {
                $table->text('fatherLastName')->nullable();
            }
            if (! Schema::hasColumn('christening_details', 'motherFirstName')) {
                $table->text('motherFirstName')->nullable();
            }
            if (! Schema::hasColumn('christening_details', 'motherMiddleName')) {
                $table->text('motherMiddleName')->nullable();
            }
            if (! Schema::hasColumn('christening_details', 'motherLastName')) {
                $table->text('motherLastName')->nullable();
            }
            if (! Schema::hasColumn('christening_details', 'certDateReceived')) {
                $table->date('certDateReceived')->nullable();
            }
            if (! Schema::hasColumn('christening_details', 'certDateIssued')) {
                $table->date('certDateIssued')->nullable();
            }
            if (! Schema::hasColumn('christening_details', 'certBookNo')) {
                $table->text('certBookNo')->nullable();
            }
            if (! Schema::hasColumn('christening_details', 'certRegisterNo')) {
                $table->text('certRegisterNo')->nullable();
            }
            if (! Schema::hasColumn('christening_details', 'certPageNo')) {
                $table->text('certPageNo')->nullable();
            }
            if (! Schema::hasColumn('christening_details', 'certSponsors')) {
                $table->text('certSponsors')->nullable();
            }
            if (! Schema::hasColumn('christening_details', 'certPurpose')) {
                $table->text('certPurpose')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('christening_details')) {
            return;
        }
        Schema::table('christening_details', function (Blueprint $table) {
            foreach ([
                'certPurpose', 'certSponsors', 'certPageNo', 'certRegisterNo', 'certBookNo',
                'certDateIssued', 'certDateReceived', 'motherLastName', 'motherMiddleName', 'motherFirstName',
                'fatherLastName', 'fatherMiddleName', 'fatherFirstName', 'addressProvince', 'addressMunicipality',
                'addressBarangay',
            ] as $col) {
                if (Schema::hasColumn('christening_details', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
