<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Traits\HasTimeFilters;

class Reimbursement extends Model
{
    use HasTimeFilters;

    protected static function booted()
    {
        static::created(function ($reimbursement) {
            // Sync folio with the composite format once ID is available
            $reimbursement->folio = $reimbursement->true_folio;
            $reimbursement->saveQuietly();
        });

        static::updating(function ($reimbursement) {
            // Re-sync folio if categories or week change
            $reimbursement->folio = $reimbursement->true_folio;
        });
    }

    protected $fillable = [
        'type',
        'cost_center_id',
        'travel_event_id',
        'week',
        'category',
        'uuid',
        'rfc_emisor',
        'nombre_emisor',
        'rfc_receptor',
        'nombre_receptor',
        'folio',
        'fecha',
        'total',
        'subtotal',
        'impuestos',
        'moneda',
        'tipo_comprobante',
        'metodo_pago',
        'forma_pago',
        'uso_cfdi',
        'lugar_expedicion',
        'regimen_fiscal_emisor',
        'xml_path',
        'pdf_path',
        'ticket_path',
        'status',
        'current_step_id',
        'observaciones',
        'attendees_count',
        'attendees_names',
        'location',
        'trip_nights',
        'trip_type',
        'trip_destination',
        'trip_start_date',
        'trip_end_date',
        'title',
        'parent_id',
        'company_confirmed',
        'validation_data',
        'user_id',
        'payee_id',
        'approved_by_director_id',
        'approved_by_director_at',
        'approved_by_control_id',
        'approved_by_control_at',
        'approved_by_executive_id',
        'approved_by_executive_at',
        'approved_by_cxp_id',
        'approved_by_cxp_at',
        'approved_by_direccion_id',
        'approved_by_direccion_at',
        'approved_by_treasury_id',
        'approved_by_treasury_at',
    ];

    protected $casts = [
        'fecha' => 'datetime',
        'trip_start_date' => 'date',
        'trip_end_date' => 'date',
        'validation_data' => 'array',
        'approved_by_director_at' => 'datetime',
        'approved_by_control_at' => 'datetime',
        'approved_by_executive_at' => 'datetime',
        'approved_by_cxp_at' => 'datetime',
        'approved_by_direccion_at' => 'datetime',
        'approved_by_treasury_at' => 'datetime',
    ];

    public function directorApprover()
    {
        return $this->belongsTo(User::class, 'approved_by_director_id');
    }

    public function controlApprover()
    {
        return $this->belongsTo(User::class, 'approved_by_control_id');
    }

    public function executiveApprover()
    {
        return $this->belongsTo(User::class, 'approved_by_executive_id');
    }

    public function cxpApprover()
    {
        return $this->belongsTo(User::class, 'approved_by_cxp_id');
    }

    public function direccionApprover()
    {
        return $this->belongsTo(User::class, 'approved_by_direccion_id');
    }

    public function treasuryApprover()
    {
        return $this->belongsTo(User::class, 'approved_by_treasury_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function payee()
    {
        return $this->belongsTo(User::class, 'payee_id');
    }

    public function costCenter()
    {
        return $this->belongsTo(CostCenter::class);
    }

    public function travelEvent()
    {
        return $this->belongsTo(TravelEvent::class);
    }

    public function currentStep()
    {
        return $this->belongsTo(ApprovalStep::class, 'current_step_id');
    }

    public function files()
    {
        return $this->hasMany(ReimbursementFile::class);
    }

    public function parent()
    {
        return $this->belongsTo(Reimbursement::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Reimbursement::class, 'parent_id');
    }

    /**
     * Get all approval history logs.
     */
    public function approvals()
    {
        return $this->hasMany(ReimbursementApproval::class)->orderBy('created_at', 'asc');
    }

    /**
     * Check if a specific user is authorized to approve the current step.
     */
    public function canBeApprovedBy(User $user)
    {
        $allIdentities = collect([$user])->concat($user->substitutingFor()->with('originalUser')->get()->pluck('originalUser')->filter());
        if ($allIdentities->contains(fn($identity) => $identity->isAdmin())) return true;

        // Shared Funnel for Accounts Payable (CXP) and Treasury
        if ($this->status === 'pendiente_pago') {
            return $allIdentities->contains(fn($identity) => $identity->isCxp() || $identity->isTreasury());
        }
        
        $currentStep = $this->currentStep;
        if (!$currentStep) return false;
        
        // Direct assignment
        if ($currentStep->user_id === $user->id) return true;

        // Substitute check
        return $user->substitutingFor()->where('original_user_id', $currentStep->user_id)->exists();
    }

    /**
     * Get the universally formatted composite Folio (e.g. INDILAB-REE-2026-15-2026-008)
     */
    public function getTrueFolioAttribute()
    {
        $typeAbbr = strtoupper(substr($this->type ?? 'REE', 0, 3));
        $ccAbbr = $this->costCenter ? ($this->costCenter->abbreviation ?? 'SCC') : 'SCC';
        $year = $this->fecha ? $this->fecha->format('Y') : ($this->created_at ? $this->created_at->format('Y') : date('Y'));
        
        // Extract only the week number if it contains a year (format W-Y)
        $week = $this->week ?? '00';
        if (str_contains($week, '-')) {
            $week = explode('-', $week)[0];
        }

        return "{$ccAbbr}-{$typeAbbr}-{$year}-{$week}-" . str_pad($this->id, 3, '0', STR_PAD_LEFT);
    }
}