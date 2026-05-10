<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentationApplicationReport extends Model
{
    public const SERVICE_CHRISTENING = 'christening';

    public const SERVICE_BURIAL = 'burial';

    public const SERVICE_CONFIRMATION = 'confirmation';

    public const SERVICE_WEDDING = 'wedding';

    protected $table = 'documentation_application_reports';

    protected $fillable = [
        'service_type',
        'registry_id',
        'reference_code',
        'client_name',
        'address',
        'sex',
        'contact_number',
        'report_month',
        'reported_at',
    ];

    protected function casts(): array
    {
        return [
            'registry_id' => 'integer',
            'reported_at' => 'datetime',
        ];
    }
}
