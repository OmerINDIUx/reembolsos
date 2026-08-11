<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReimbursementApproval extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new \LogicException('El historial de aprobaciones es inmutable y no puede editarse.');
        });

        static::deleting(function (): never {
            throw new \LogicException('El historial de aprobaciones es inmutable y no puede eliminarse.');
        });
    }

    protected $fillable = [
        'reimbursement_id',
        'user_id',
        'step_name',
        'action',
        'comment',
        'is_bulk',
        'substituted_user_id',
    ];

    public function reimbursement()
    {
        return $this->belongsTo(Reimbursement::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function substitutedUser()
    {
        return $this->belongsTo(User::class, 'substituted_user_id')->withTrashed();
    }
}
