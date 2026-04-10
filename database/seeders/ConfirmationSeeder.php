<?php

namespace Database\Seeders;

use App\Models\Confirmation;
use Illuminate\Database\Seeder;

class ConfirmationSeeder extends Seeder
{
    public function run(): void
    {
        Confirmation::query()->firstOrCreate(
            ['referenceCode' => '2026-EJDHIEQ-T'],
            [
                'clientFName' => 'Rex',
                'clientMName' => 'P.',
                'clientLName' => 'Bernesto',
                'address' => 'Gua, Barbaza, Antique',
                'sex' => 'Male',
                'contactNum' => '09679050621',
                'dateCreated' => now(),
                'customerId' => null,
            ]
        );
    }
}
