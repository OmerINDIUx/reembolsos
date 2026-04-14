<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TravelEvent extends Model
{
    protected $fillable = [
        'cost_center_id',
        'name',
        'code',
        'user_id',
        'director_id',
        'location',
        'start_date',
        'end_date',
        'description',
        'status',
        'approval_evidence_path',
        'trip_type',
    ];

    public function costCenter()
    {
        return $this->belongsTo(CostCenter::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function director()
    {
        return $this->belongsTo(User::class, 'director_id');
    }

    public function reimbursements()
    {
        return $this->hasMany(Reimbursement::class);
    }

    public function participants()
    {
        return $this->belongsToMany(User::class, 'travel_event_user');
    }
}
