<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReimbursementApproval extends Model
{
    use HasFactory;

    protected $fillable = [
        'reimbursement_id',
        'user_id',
        'step_name',
        'action',
        'comment',
        'is_bulk',
    ];

    public function reimbursement()
    {
        return $this->belongsTo(Reimbursement::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
