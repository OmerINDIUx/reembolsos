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
        'control_obra_id',
        'director_ejecutivo_id',
        'description',
    ];

    public function director()
    {
        return $this->belongsTo(User::class, 'director_id');
    }

    public function controlObra()
    {
        return $this->belongsTo(User::class, 'control_obra_id');
    }

    public function directorEjecutivo()
    {
        return $this->belongsTo(User::class, 'director_ejecutivo_id');
    }

    public function reimbursements()
    {
        return $this->hasMany(Reimbursement::class);
    }
}
