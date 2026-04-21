<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Confirmation extends Model
{
    protected $table = 'confirmation';

    protected $primaryKey = 'confirmationId';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = [
        'referenceCode',
        'clientFName',
        'clientLName',
        'clientMName',
        'address',
        'sex',
        'contactNum',
        'scheduleRequested',
        'paymentStatus',
        'paymentFeeRows',
        'dateCreated',
        'customerId',
    ];

    protected function casts(): array
    {
        return [
            'dateCreated' => 'datetime',
            'scheduleRequested' => 'datetime',
            'paymentFeeRows' => 'array',
        ];
    }

    public function documentTypeLabel(): string
    {
        return 'Confirmation';
    }
}
