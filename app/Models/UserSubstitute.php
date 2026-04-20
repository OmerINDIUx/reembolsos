<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserSubstitute extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'original_user_id',
        'is_active',
    ];

    /**
     * Get the user who acts as a substitute.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the original user who is being substituted.
     */
    public function originalUser()
    {
        return $this->belongsTo(User::class, 'original_user_id');
    }
}
