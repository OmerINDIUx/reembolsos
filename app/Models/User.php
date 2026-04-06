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
        'must_change_password',
    ];

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

    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function isAdminView()
    {
        return $this->role === 'admin_view';
    }

    public function isDirector()
    {
        return $this->role === 'director';
    }

    public function isCxp()
    {
        return $this->role === 'accountant';
    }

    public function isTreasury()
    {
        return $this->role === 'tesoreria';
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
        return in_array($this->role, $roles);
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
            'password' => 'hashed',
        ];
    }

    public function getRoleNameAttribute()
    {
        return match($this->role) {
            'admin' => 'Administrador (Full)',
            'admin_view' => 'Administrador (Lectura)',
            'director' => 'Director N1',
            'control_obra' => 'Control de Obra N2',
            'director_ejecutivo' => 'Director Ejecutivo N3',
            'accountant' => 'Cuentas por Pagar N4',
            'direccion' => 'Subdirección N5',
            'tesoreria' => 'Dirección N6',
            default => 'Usuario Estándar',
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
}
