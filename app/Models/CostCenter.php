<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CostCenter extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'director_id',
        'description',
    ];

    public function director()
    {
        return $this->belongsTo(User::class, 'director_id');
    }
}
