<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationBatch extends Model
{
    //
    protected $fillable = [
        'user_id',
        'reimbursement_ids',
        'send_at',
    ];

    protected $casts = [
        'reimbursement_ids' => 'array',
        'send_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
