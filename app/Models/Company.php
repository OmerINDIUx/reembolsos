<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'rfc',
        'account',
    ];

    protected $hidden = [
        'rfc',
        'account',
    ];

    protected function casts(): array
    {
        return [
            'rfc' => 'encrypted',
            'account' => 'encrypted',
        ];
    }

    public function costCenters()
    {
        return $this->hasMany(CostCenter::class);
    }
}
