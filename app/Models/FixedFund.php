<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FixedFund extends Model
{
    protected $fillable = ['cost_center_id', 'user_id', 'name', 'budget', 'is_active'];

    protected $casts = ['budget' => 'decimal:2', 'is_active' => 'boolean'];

    public function costCenter() { return $this->belongsTo(CostCenter::class); }
    public function user() { return $this->belongsTo(User::class); }
    public function reimbursements() { return $this->hasMany(Reimbursement::class); }
    public function budgetRenewals() { return $this->hasMany(BudgetRenewal::class); }

    public function completedRenewalCycles(float $replenishedAmount): int
    {
        $capital = (float) $this->budget;

        return $capital > 0 ? (int) floor($replenishedAmount / $capital) : 0;
    }

    public function renewalCycleProgress(float $replenishedAmount): float
    {
        $capital = (float) $this->budget;

        return $capital > 0 ? fmod($replenishedAmount, $capital) : 0;
    }
}
