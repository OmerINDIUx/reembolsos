<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reimbursement extends Model
{
    //

    protected $fillable = [
        'type',
        'cost_center_id',
        'week',
        'category',
        'uuid',
        'rfc_emisor',
        'nombre_emisor',
        'rfc_receptor',
        'nombre_receptor',
        'folio',
        'fecha',
        'total',
        'subtotal',
        'impuestos',
        'moneda',
        'tipo_comprobante',
        'xml_path',
        'pdf_path',
        'status',
        'observaciones',
        'attendees_count',
        'attendees_names',
        'location',
        'trip_nights',
        'trip_type',
        'trip_destination',
        'trip_start_date',
        'trip_end_date',
        'title',
        'parent_id',
        'company_confirmed',
        'validation_data',
        'user_id',
    ];

    protected $casts = [
        'fecha' => 'datetime',
        'trip_start_date' => 'date',
        'trip_end_date' => 'date',
        'validation_data' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function costCenter()
    {
        return $this->belongsTo(CostCenter::class);
    }

    public function files()
    {
        return $this->hasMany(ReimbursementFile::class);
    }

    public function parent()
    {
        return $this->belongsTo(Reimbursement::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Reimbursement::class, 'parent_id');
    }
}
