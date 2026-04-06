<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BudgetRenewal extends Model
{
    protected $fillable = [
        'cost_center_id',
        'amount',
        'description',
        'renewal_date',
        'user_id',
    ];

    public function costCenter()
    {
        return $this->belongsTo(CostCenter::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
