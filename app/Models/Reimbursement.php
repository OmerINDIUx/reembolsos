<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reimbursement extends Model
{
    //

    protected $fillable = [
        'type',
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
    ];

    protected $casts = [
        'fecha' => 'datetime',
    ];
}
