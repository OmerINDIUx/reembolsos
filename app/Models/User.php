<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'director_id',
        'invitation_token',
        'invitation_sent_at',
        'bank_name',
        'clabe',
        'profile_id',
    ];

    public function isRegistered()
    {
        return $this->password !== null && $this->invitation_token === null;
    }

    public function director()
    {
        return $this->belongsTo(User::class, 'director_id');
    }

    public function subordinates()
    {
        return $this->hasMany(User::class, 'director_id');
    }

    public function costCenters()
    {
        return $this->hasMany(CostCenter::class, 'director_id');
    }

    public function reimbursements()
    {
        return $this->hasMany(Reimbursement::class);
    }

    public function deviceLogins()
    {
        return $this->hasMany(DeviceLogin::class);
    }

    public function isAdmin()
    {
        return $this->role === 'admin' || $this->profile?->name === 'admin';
    }

    public function isAdminView()
    {
        return $this->role === 'admin_view' || $this->profile?->name === 'admin_view';
    }

    public function isDirector()
    {
        return $this->role === 'director';
    }

    public function isCxp()
    {
        return $this->role === 'accountant' || $this->profile?->name === 'accountant';
    }

    public function isTreasury()
    {
        return $this->role === 'tesoreria' || $this->profile?->name === 'tesoreria';
    }

    public function isControlObra()
    {
        return $this->role === 'control_obra';
    }

    public function isExecutiveDirector()
    {
        return $this->role === 'director_ejecutivo';
    }

    public function isDireccion()
    {
        return $this->role === 'direccion';
    }

    public function hasRole(...$roles)
    {
        return in_array($this->role, $roles, true) || in_array($this->profile?->name, $roles, true);
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'invitation_sent_at' => 'datetime',
            'password' => 'hashed',
            'blocked_at' => 'datetime',
        ];
    }

    public function getRoleNameAttribute()
    {
        if ($this->profile) {
            return $this->profile->display_name;
        }

        return match($this->role) {
            'admin' => 'Administrador (Full)',
            'admin_view' => 'Administrador (Lectura)',
            'director' => 'Director N1',
            'control_obra' => 'Control de Obra N2',
            'director_ejecutivo' => 'Director Ejecutivo N3',
            'accountant' => 'Cuentas por Pagar Revisador',
            'direccion' => 'Subdirección N5',
            'tesoreria' => 'Cuentas por Pagar Pagador',
            default => 'Usuario General',
        };
    }

    public function travelEvents()
    {
        return $this->belongsToMany(TravelEvent::class, 'travel_event_user');
    }

    public function authorizedCostCenters()
    {
        return $this->belongsToMany(CostCenter::class, 'cost_center_user')
                    ->withPivot('can_do_special')
                    ->withTimestamps();
    }

    public function reimbursementApprovals()
    {
        return $this->hasMany(ReimbursementApproval::class);
    }

    /**
     * Check if the user has any reimbursement currently assigned to them for approval.
     */
    public function hasPendingApprovals()
    {
        return Reimbursement::whereHas('currentStep', function($q) {
            $q->where('user_id', $this->id)
              ->orWhereIn('user_id', $this->substitutingFor()->pluck('original_user_id'));
        })->exists();
    }

    public function substitutes()
    {
        return $this->hasMany(UserSubstitute::class, 'original_user_id');
    }

    public function substitutingFor()
    {
        return $this->hasMany(UserSubstitute::class, 'user_id')->where('is_active', true);
    }

    public function profile()
    {
        return $this->belongsTo(Profile::class);
    }

    public function isApproverOrLinked()
    {
        // Check if the user is in any Cost Center's approval steps
        $inApprovalSteps = \App\Models\ApprovalStep::where('user_id', $this->id)->exists();
        if ($inApprovalSteps) {
            return true;
        }

        // Check if they are role-linked to any Cost Center
        return \App\Models\CostCenter::where('director_id', $this->id)
            ->orWhere('control_obra_id', $this->id)
            ->orWhere('director_ejecutivo_id', $this->id)
            ->orWhere('accountant_id', $this->id)
            ->orWhere('direccion_id', $this->id)
            ->orWhere('tesoreria_id', $this->id)
            ->orWhere('beneficiary_id', $this->id)
            ->exists();
    }

    public function canPerform($permission)
    {
        // Admins always have all permissions
        if ($this->isAdmin()) {
            return true;
        }

        // Dynamically allow global_history permission for any approver or role-linked user
        if ($permission === 'reimbursements.global_history' && $this->isApproverOrLinked()) {
            return true;
        }

        if (!$this->profile) {
            return false;
        }

        if ($permission === 'dashboard.view_own' && $this->profile->hasPermission('dashboard.view_global')) {
            return true;
        }

        return $this->profile->hasPermission($permission);
    }

    public function isBlocked(): bool
    {
        return $this->blocked_at !== null;
    }

    public function blockedByUser()
    {
        return $this->belongsTo(User::class, 'blocked_by');
    }

    public function accountBlockEvents()
    {
        return $this->hasMany(AccountBlockEvent::class);
    }
}
