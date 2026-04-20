<?php
$file = 'resources/views/reimbursements/index.blade.php';
$content = file_get_contents($file);
$content = str_replace(
    '{{ $allIdentities->contains(fn($identity) => $identity->isAdmin() || $identity->isAdminView()) ? \'Todos los Reembolsos (Global)\' : \'Historial Global (Rechazados)\' }}',
    '{{ ($user->isAdmin() || $user->isAdminView()) ? \'Todos los Reembolsos (Global)\' : \'Historial Global (Rechazados)\' }}',
    $content
);
file_put_contents($file, $content);
echo "Blade view line 58 reverted.\n";
