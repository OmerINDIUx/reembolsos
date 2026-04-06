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
        'budget',
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

    public function budgetRenewals()
    {
        return $this->hasMany(BudgetRenewal::class)->orderBy('renewal_date', 'desc');
    }

    public function reimbursements()
    {
        return $this->hasMany(Reimbursement::class);
    }

    /**
     * Get an abbreviation for the cost center.
     * Rule: Use $this->code if present, else intelligent acronym.
     */
    public function getAbbreviationAttribute()
    {
        // Ignore generic "CC-" codes or numeric-only codes
        if (!empty($this->code) && !str_starts_with(strtoupper($this->code), 'CC-')) {
            return strtoupper($this->code);
        }

        if (empty($this->name)) {
            return 'SCC';
        }

        // Clean name: capitalize, remove special chars, but keep spaces and hyphens
        $cleanName = strtoupper($this->name);
        
        // Split by spaces and hyphens
        $words = preg_split('/[\s\-]+/', $cleanName, -1, PREG_SPLIT_NO_EMPTY);
        
        // Filter out short connector words (DE, Y, LA, EL, CON, etc.)
        $stopWords = ['DE', 'Y', 'LA', 'EL', 'LOS', 'LAS', 'CON', 'PARA', 'POR', 'EN'];
        $filteredWords = array_values(array_filter($words, fn($w) => !in_array($w, $stopWords) && strlen($w) > 1));

        if (count($filteredWords) >= 3) {
            // Take initials of the first 3 relevant words
            return substr($filteredWords[0], 0, 1) . 
                   substr($filteredWords[1], 0, 1) . 
                   substr($filteredWords[2], 0, 1);
        } elseif (count($filteredWords) == 2) {
            // First of first word + First TWO of second word (Matches PEO for "Parque Eólico")
            return substr($filteredWords[0], 0, 1) . 
                   substr($filteredWords[1], 0, 2);
        } elseif (count($filteredWords) == 1) {
            // First 3 letters of the only word
            return substr($filteredWords[0], 0, 3);
        }

        // Extreme fallback
        return substr(str_replace(' ', '', $cleanName), 0, 3);
    }
}
