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
        'accountant_id',
        'direccion_id',
        'tesoreria_id',
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

    public function accountant()
    {
        return $this->belongsTo(User::class, 'accountant_id');
    }

    public function direccion()
    {
        return $this->belongsTo(User::class, 'direccion_id');
    }

    public function tesoreria()
    {
        return $this->belongsTo(User::class, 'tesoreria_id');
    }

    public function approvalSteps()
    {
        return $this->hasMany(ApprovalStep::class)->orderBy('order');
    }

    public function reimbursements()
    {
        return $this->hasMany(Reimbursement::class);
    }
}
