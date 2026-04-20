<?php
$file = 'app/Http/Controllers/ReimbursementController.php';
$content = file_get_contents($file);

$find = <<<'PHP'
    private function applyTabScope($query, $tab, $user)
    {
        if ($tab === 'active') {
            // Strictly Personal: Pending
            $query->where('user_id', $user->id)
                  ->whereNotIn('status', ['aprobado', 'rechazado', 'borrador']);

        } elseif ($tab === 'history') {
            // Strictly Personal: Finished
            $query->where('user_id', $user->id)
                  ->whereIn('status', ['aprobado', 'rechazado']);

        } elseif ($tab === 'management') {
            // Approvals & Oversight for designated roles
            if ($user->isAdmin() || $user->isAdminView()) {
                $query->whereNotIn('status', ['aprobado', 'rechazado', 'en_evento', 'borrador']);
            } else {
                // DYNAMIC VISIBILITY: User sees it if they are the assigned approver OR if they are CXP and status is pending payment
                $query->where(function($q) use ($user) {
                    $q->whereHas('currentStep', function($sq) use ($user) {
                        $sq->where('user_id', $user->id)
                          ->orWhereIn('user_id', $user->substitutingFor()->pluck('original_user_id'));
                    });
                    
                    if ($user->isCxp() || $user->isTreasury()) {
                        $q->orWhere('status', 'pendiente_pago');
                    }
                });
            }

        } elseif ($tab === 'global_history') {
            // History for elevated roles or personal history
            if ($user->isAdmin() || $user->isAdminView() || $user->isTreasury() || $user->isCxp() || $user->isDireccion()) {
                $query->whereIn('status', ['aprobado', 'rechazado']);
            } elseif ($user->isDirector() || $user->isControlObra() || $user->isExecutiveDirector()) {
                $query->whereHas('costCenter', function($q) use ($user) {
                    if ($user->isDirector()) $q->where('director_id', $user->id);
                    if ($user->isControlObra()) $q->where('control_obra_id', $user->id);
                    if ($user->isExecutiveDirector()) $q->where('director_ejecutivo_id', $user->id);
                })->whereIn('status', ['aprobado', 'rechazado']);
            } else {
                $query->where('user_id', $user->id)
                      ->whereIn('status', ['aprobado', 'rechazado']);
            }
        } else {
            // DEFAULT FALLBACK: Personal Scope (mostly for weekly_summary if needed)
            $query->where('user_id', $user->id);
        }
    }
PHP;

$replace = <<<'PHP'
    private function applyTabScope($query, $tab, $user)
    {
        $allIdentities = collect([$user])->concat($user->substitutingFor()->with('originalUser')->get()->pluck('originalUser')->filter());
        $isAdmin = $allIdentities->contains(fn($i) => $i->isAdmin() || $i->isAdminView());
        $isCxpOrTreasury = $allIdentities->contains(fn($i) => $i->isCxp() || $i->isTreasury());
        $allUserIds = $allIdentities->pluck('id')->toArray();

        if ($tab === 'active') {
            // Strictly Personal: Pending
            $query->whereIn('user_id', $allUserIds)
                  ->whereNotIn('status', ['aprobado', 'rechazado', 'borrador']);

        } elseif ($tab === 'history') {
            // Strictly Personal: Finished
            $query->whereIn('user_id', $allUserIds)
                  ->whereIn('status', ['aprobado', 'rechazado']);

        } elseif ($tab === 'management') {
            // Approvals & Oversight for designated roles
            if ($isAdmin) {
                $query->whereNotIn('status', ['aprobado', 'rechazado', 'en_evento', 'borrador']);
            } else {
                // DYNAMIC VISIBILITY
                $query->where(function($q) use ($allUserIds, $isCxpOrTreasury) {
                    $q->whereHas('currentStep', function($sq) use ($allUserIds) {
                        $sq->whereIn('user_id', $allUserIds);
                    });
                    
                    if ($isCxpOrTreasury) {
                        $q->orWhere('status', 'pendiente_pago');
                    }
                });
            }

        } elseif ($tab === 'global_history') {
            // History for elevated roles or personal history
            if ($isAdmin || $isCxpOrTreasury || $allIdentities->contains(fn($i) => $i->isDireccion())) {
                $query->whereIn('status', ['aprobado', 'rechazado']);
            } elseif ($allIdentities->contains(fn($i) => $i->isDirector() || $i->isControlObra() || $i->isExecutiveDirector())) {
                $query->whereHas('costCenter', function($q) use ($allIdentities) {
                    $q->where(function($subQ) use ($allIdentities) {
                        $directorIds = $allIdentities->filter(fn($i) => $i->isDirector())->pluck('id')->toArray();
                        $controlIds = $allIdentities->filter(fn($i) => $i->isControlObra())->pluck('id')->toArray();
                        $execIds = $allIdentities->filter(fn($i) => $i->isExecutiveDirector())->pluck('id')->toArray();
                        
                        if (!empty($directorIds)) $subQ->orWhereIn('director_id', $directorIds);
                        if (!empty($controlIds)) $subQ->orWhereIn('control_obra_id', $controlIds);
                        if (!empty($execIds)) $subQ->orWhereIn('director_ejecutivo_id', $execIds);
                    });
                })->whereIn('status', ['aprobado', 'rechazado']);
            } else {
                $query->whereIn('user_id', $allUserIds)
                      ->whereIn('status', ['aprobado', 'rechazado']);
            }
        } else {
            // DEFAULT FALLBACK
            $query->whereIn('user_id', $allUserIds);
        }
    }
PHP;

if (strpos($content, "    private function applyTabScope(\$query, \$tab, \$user)") !== false) {
    $content = str_replace($find, $replace, $content);
    file_put_contents($file, $content);
    echo "applyTabScope replaced.\n";
} else {
    echo "applyTabScope not found.\n";
}
