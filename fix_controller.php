<?php
$file = 'app/Http/Controllers/ReimbursementController.php';
$content = file_get_contents($file);

// Also handle the case where it might be `can('manage_reimbursements')`
$content = str_replace(
    "\$canManage = \$user->can('manage_reimbursements');",
    "\$allIdentities = collect([\$user])->concat(\$user->substitutingFor()->with('originalUser')->get()->pluck('originalUser')->filter());\n        \$canManage = \$allIdentities->contains(fn(\$identity) => \$identity->isAdmin() || \$identity->isAdminView() || \$identity->isCxp() || \$identity->isTreasury() || \$identity->isDireccion() || \$identity->isDirector() || \$identity->isControlObra() || \$identity->isExecutiveDirector() || \$identity->hasPendingApprovals());",
    $content
);

$content = str_replace(
    "\$canManage = \$user->can('manage_reimbursements') || \$user->isSubstitute();",
    "\$allIdentities = collect([\$user])->concat(\$user->substitutingFor()->with('originalUser')->get()->pluck('originalUser')->filter());\n        \$canManage = \$allIdentities->contains(fn(\$identity) => \$identity->isAdmin() || \$identity->isAdminView() || \$identity->isCxp() || \$identity->isTreasury() || \$identity->isDireccion() || \$identity->isDirector() || \$identity->isControlObra() || \$identity->isExecutiveDirector() || \$identity->hasPendingApprovals());",
    $content
);

file_put_contents($file, $content);
echo "Controller replaced.\n";
