<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReimbursementFile extends Model
{
    protected $fillable = [
        'reimbursement_id',
        'file_path',
        'original_name',
        'mime_type',
    ];

    public function reimbursement()
    {
        return $this->belongsTo(Reimbursement::class);
    }
}
