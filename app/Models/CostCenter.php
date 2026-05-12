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
        'menfis_email',
        'budget',
        'beneficiary_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

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

    public function beneficiary()
    {
        return $this->belongsTo(User::class, 'beneficiary_id');
    }

    public function approvalSteps()
    {
        return $this->hasMany(ApprovalStep::class)->orderBy('order');
    }

    public function budgetRenewals()
    {
        return $this->hasMany(BudgetRenewal::class)->orderBy('renewal_date', 'desc');
    }

    public function reimbursements()
    {
        return $this->hasMany(Reimbursement::class);
    }

    public function authorizedUsers()
    {
        return $this->belongsToMany(User::class, 'cost_center_user')
                    ->withPivot('can_do_special')
                    ->withTimestamps();
    }

    /**
     * Get an abbreviation for the cost center.
     * Rule: 3 letters based on the name. If it collides with an older CC, use 4 letters.
     */
    public function getAbbreviationAttribute()
    {
        // To ensure consistent abbreviations across the app without making DB schemas, 
        // we'll compute it dynamically for all CCs up to this one.
        static $campsites = null;
        if ($campsites === null) {
            $campsites = \App\Models\CostCenter::orderBy('id', 'asc')->get(['id', 'name']);
        }

        $stopWords = ['DE', 'Y', 'LA', 'EL', 'LOS', 'LAS', 'CON', 'PARA', 'POR', 'EN'];
        $seen = [];

        foreach ($campsites as $c) {
            $cleanName = strtoupper($c->name ?? '');
            $words = preg_split('/[\s\-]+/', $cleanName, -1, PREG_SPLIT_NO_EMPTY);
            $filteredWords = array_values(array_filter($words, fn($w) => !in_array($w, $stopWords) && strlen($w) > 1));

            $baseAbbr = 'SCC';
            if (count($filteredWords) >= 3) {
                $baseAbbr = substr($filteredWords[0], 0, 1) . substr($filteredWords[1], 0, 1) . substr($filteredWords[2], 0, 1);
            } elseif (count($filteredWords) == 2) {
                $baseAbbr = substr($filteredWords[0], 0, 1) . substr($filteredWords[1], 0, 2);
            } elseif (count($filteredWords) == 1) {
                $baseAbbr = substr($filteredWords[0], 0, 3);
            }

            if (isset($seen[$baseAbbr])) {
                // Collision! Make it 4 chars
                if (count($filteredWords) >= 4) {
                     $baseAbbr .= substr($filteredWords[3], 0, 1);
                } elseif (count($filteredWords) >= 3) {
                     $baseAbbr .= substr($filteredWords[2], 1, 1);
                } elseif (count($filteredWords) >= 2) {
                     $baseAbbr = substr($filteredWords[0], 0, 2) . substr($filteredWords[1], 0, 2);
                } elseif (count($filteredWords) == 1) {
                     $baseAbbr = substr($filteredWords[0], 0, 4);
                }
            }

            // In case 4-char also collides (rare), just force it
            if (isset($seen[$baseAbbr])) {
                $baseAbbr .= $c->id;
            }

            $seen[$baseAbbr] = true;

            if ($c->id === $this->id) {
                return $baseAbbr;
            }
        }

        return 'SCC';
    }
}
