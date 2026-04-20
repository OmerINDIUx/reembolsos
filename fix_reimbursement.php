<?php
$file = 'app/Models/Reimbursement.php';
$content = file_get_contents($file);
$good_part = explode('    public function approvals()', $content)[0];

$new_methods = <<<'PHP'
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
PHP;

file_put_contents($file, $good_part . $new_methods);
echo "Reimbursement.php fixed.\n";
