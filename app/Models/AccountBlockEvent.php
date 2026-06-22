<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountBlockEvent extends Model
{
    protected $fillable = [
        'user_id',
        'actor_id',
        'action',
        'reason_code',
        'reason_message',
        'ip_address',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
