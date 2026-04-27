<?php
require dirname(__DIR__).'/vendor/autoload.php';
$app = require_once dirname(__DIR__).'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::where('email', 'luise.armendariz@grupoindi.com')->first();
if (!$user) {
    echo "User not found\n";
    exit;
}

echo "User: {$user->name} (ID: {$user->id})\n";
echo "Role: {$user->role}\n";
echo "Substituting for: " . $user->substitutingFor()->pluck('original_user_id')->implode(', ') . "\n";

// Find the reimbursement
$reimb = App\Models\Reimbursement::where('nombre_emisor', 'like', '%CONSORCIO GASOLINERO PLUS%')->first();
if (!$reimb) {
    echo "Reimbursement not found\n";
    exit;
}

echo "Reimbursement ID: {$reimb->id}\n";
echo "Status: {$reimb->status}\n";
echo "Current Step User ID: " . ($reimb->currentStep ? $reimb->currentStep->user_id : 'N/A') . "\n";
echo "Cost Center: " . ($reimb->costCenter ? $reimb->costCenter->name : 'N/A') . "\n";

$identityIds = $user->substitutingFor()->pluck('original_user_id')->push($user->id)->unique()->toArray();
echo "Identity IDs: " . implode(', ', $identityIds) . "\n";

// Check visibility logic from applyTabScope
$tab = 'management';
$inManagement = false;

if ($reimb->currentStep && in_array($reimb->currentStep->user_id, $identityIds) && !in_array($reimb->status, ['aprobado', 'rechazado', 'borrador', 'pendiente_pago'])) {
    $inManagement = true;
    echo "Visibility Reason: Direct or Substitute Approver\n";
}

if ($reimb->status === 'pendiente_pago' && ($user->isCxp() || $user->isTreasury())) {
    $inManagement = true;
    echo "Visibility Reason: CXP/Treasury Funnel\n";
}

if (!in_array($reimb->status, ['aprobado', 'rechazado', 'borrador']) && ($user->isAdmin() || $user->isAdminView() || $user->isDireccion())) {
    $inManagement = true;
    echo "Visibility Reason: Admin/Elevated Role\n";
}

if (!$inManagement) {
    echo "Should NOT be in management tab.\n";
} else {
    echo "IS in management tab.\n";
}
