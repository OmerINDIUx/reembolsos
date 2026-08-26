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
        'fixed_fund_id',
        'travel_event_id',
        'week',
        'payment_week',
        'category',
        'uuid',
        'rfc_emisor',
        'nombre_emisor',
        'rfc_receptor',
        'nombre_receptor',
        'folio',
        'folio_interno_proveedor',
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
        'retencion_iva',
        'monto_iva',
        'monto_isr',
        'cfdi_conceptos',
        'impuestos_locales',
        'xml_path',
        'original_xml_name',
        'pdf_path',
        'original_pdf_name',
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
        'created_by_id',
        'payee_id',
        'propina',
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
        'propina' => 'decimal:2',
        'retencion_iva' => 'decimal:2',
        'monto_iva' => 'decimal:2',
        'monto_isr' => 'decimal:2',
        'cfdi_conceptos' => 'array',
        'impuestos_locales' => 'array',
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
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id')->withTrashed();
    }

    public function payee()
    {
        return $this->belongsTo(User::class, 'payee_id')->withTrashed();
    }

    public function costCenter()
    {
        return $this->belongsTo(CostCenter::class);
    }

    public function fixedFund()
    {
        return $this->belongsTo(FixedFund::class);
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

    public function isManagedByRequester(User $user): bool
    {
        if ((int) $this->user_id === (int) $user->id) {
            return true;
        }

        if ($this->created_by_id && (int) $this->created_by_id === (int) $user->id) {
            return true;
        }

        if (!$user->canPerform('reimbursements.create_on_behalf')) {
            return false;
        }

        return $this->approvals()
            ->where('user_id', $user->id)
            ->where('step_name', 'Solicitante')
            ->where('action', 'enviado')
            ->where('comment', 'like', 'REGISTRO POR TERCEROS%')
            ->exists();
    }

    /**
     * Get all approval history logs.
     */
    public function approvals()
    {
        return $this->hasMany(ReimbursementApproval::class)->orderBy('created_at', 'asc');
    }

    /**
     * Return the immutable audit entry that completed a configured approval step.
     */
    public function approvedLogForStep(?string $stepName): ?ReimbursementApproval
    {
        if (!$stepName) {
            return null;
        }

        $approvals = $this->relationLoaded('approvals')
            ? $this->approvals
            : $this->approvals()->with(['user', 'substitutedUser'])->get();

        return $approvals
            ->where('step_name', $stepName)
            ->where('action', 'aprobado')
            ->last();
    }

    /**
     * Return the first configured step that has no immutable approval entry.
     */
    public function firstPendingConfiguredApprovalStep(): ?ApprovalStep
    {
        $this->loadMissing(['costCenter.approvalSteps', 'approvals']);

        if (!$this->costCenter) {
            return null;
        }

        $approvedStepNames = $this->approvals
            ->where('action', 'aprobado')
            ->pluck('step_name');

        return $this->costCenter->approvalSteps
            ->first(fn (ApprovalStep $step) => !$approvedStepNames->contains($step->name));
    }

    public function configuredApprovalFlowIsComplete(): bool
    {
        return $this->firstPendingConfiguredApprovalStep() === null;
    }

    public function scopeWithCompletedConfiguredApprovalFlow($query)
    {
        return $query->whereNotExists(function ($steps) {
            $steps->selectRaw('1')
                ->from('approval_steps')
                ->whereColumn('approval_steps.cost_center_id', 'reimbursements.cost_center_id')
                ->whereNotExists(function ($approvals) {
                    $approvals->selectRaw('1')
                        ->from('reimbursement_approvals')
                        ->whereColumn('reimbursement_approvals.reimbursement_id', 'reimbursements.id')
                        ->whereColumn('reimbursement_approvals.step_name', 'approval_steps.name')
                        ->where('reimbursement_approvals.action', 'aprobado');
                });
        });
    }

    /**
     * Check if a specific user is authorized to approve the current step.
     */
    public function canBeApprovedBy(User $user)
    {
        $allIdentities = collect([$user])->concat($user->substitutingFor()->with('originalUser')->get()->pluck('originalUser')->filter());

        // Pagadores jamás pueden autorizar un trámite que no haya sido revisado por CXP.
        if (
            $this->status === 'pendiente_pago'
            && ($this->approved_by_cxp_id === null || $this->approved_by_cxp_at === null)
        ) {
            return false;
        }

        // CXP can only act after every configured step has a real audit entry.
        if ($this->status === 'pendiente_revision_cxp' && !$this->configuredApprovalFlowIsComplete()) {
            return false;
        }

        if ($allIdentities->contains(fn($identity) => $identity->isAdmin())) return true;

        // Accounts Payable is split into review first, payment second.
        if ($this->status === 'pendiente_revision_cxp') {
            return $allIdentities->contains(fn($identity) => $identity->isCxp());
        }

        if ($this->status === 'pendiente_pago' && $this->approved_by_treasury_at === null) {
            return $allIdentities->contains(fn($identity) => $identity->isTreasury());
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
