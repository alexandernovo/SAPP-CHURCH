<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sample burial rows for development / document report testing.
     * Re-run safe: skipped if any reference code already exists.
     */
    private const REFERENCE_CODES = [
        '2026-EJDHIEQ-D',
        '2026-AK2M9N1-D',
        '2026-PL8X4Y2-D',
        '2026-RQ5K7J3-D',
        '2026-TN1W8V6-D',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('burial')) {
            return;
        }

        if (DB::table('burial')->whereIn('referenceCode', self::REFERENCE_CODES)->exists()) {
            return;
        }

        $rows = [
            [
                'referenceCode' => '2026-EJDHIEQ-D',
                'clientFName' => 'Rex',
                'clientMName' => 'P.',
                'clientLName' => 'Bernesto',
                'address' => 'Gua, Barbaza, Antique',
                'sex' => 'Male',
                'contactNum' => '09123456789',
                'paymentStatus' => 'Unpaid',
                'dateCreated' => '2026-04-22 10:15:00',
                'customerId' => null,
                'scheduleRequested' => '2026-04-25 09:00:00',
                'paymentFeeRows' => null,
            ],
            [
                'referenceCode' => '2026-AK2M9N1-D',
                'clientFName' => 'Maria',
                'clientMName' => 'Clara',
                'clientLName' => 'Santos',
                'address' => 'Poblacion, Barbaza, Antique',
                'sex' => 'Female',
                'contactNum' => '09987654321',
                'paymentStatus' => 'Unpaid',
                'dateCreated' => '2026-04-20 14:30:00',
                'customerId' => null,
                'scheduleRequested' => '2026-04-24 10:00:00',
                'paymentFeeRows' => null,
            ],
            [
                'referenceCode' => '2026-PL8X4Y2-D',
                'clientFName' => 'Juan',
                'clientMName' => 'R.',
                'clientLName' => 'Dela Cruz',
                'address' => 'Baghari, Barbaza, Antique',
                'sex' => 'Male',
                'contactNum' => '09171234567',
                'paymentStatus' => 'Unpaid',
                'dateCreated' => '2026-04-18 11:00:00',
                'customerId' => null,
                'scheduleRequested' => '2026-04-23 15:30:00',
                'paymentFeeRows' => null,
            ],
            [
                'referenceCode' => '2026-RQ5K7J3-D',
                'clientFName' => 'Ana',
                'clientMName' => 'L.',
                'clientLName' => 'Fernandez',
                'address' => 'Esparar, Barbaza, Antique',
                'sex' => 'Female',
                'contactNum' => '09209876543',
                'paymentStatus' => 'Paid',
                'dateCreated' => '2026-04-15 09:20:00',
                'customerId' => null,
                'scheduleRequested' => '2026-04-20 08:00:00',
                'paymentFeeRows' => null,
            ],
            [
                'referenceCode' => '2026-TN1W8V6-D',
                'clientFName' => 'Carlos',
                'clientMName' => 'M.',
                'clientLName' => 'Villarin',
                'address' => 'Gua, Barbaza, Antique',
                'sex' => 'Male',
                'contactNum' => '09345678901',
                'paymentStatus' => 'Unpaid',
                'dateCreated' => '2026-04-12 16:45:00',
                'customerId' => null,
                'scheduleRequested' => '2026-04-19 11:00:00',
                'paymentFeeRows' => null,
            ],
        ];

        DB::table('burial')->insert($rows);
    }

    public function down(): void
    {
        if (! Schema::hasTable('burial')) {
            return;
        }

        DB::table('burial')->whereIn('referenceCode', self::REFERENCE_CODES)->delete();
    }
};
