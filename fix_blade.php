<?php
$file = 'resources/views/reimbursements/index.blade.php';
$content = file_get_contents($file);

$find = '<div class="flex flex-col md:flex-row justify-between items-center mb-6 space-y-4 md:space-y-0">';

$replace = <<<'PHP'
                    @php
                        $user = Auth::user();
                        $allIdentities = collect([$user])->concat($user->substitutingFor()->with('originalUser')->get()->pluck('originalUser')->filter());
                        $canManage = $allIdentities->contains(fn($identity) => $identity->isAdmin() || $identity->isAdminView() || $identity->isCxp() || $identity->isTreasury() || $identity->isDireccion() || $identity->isDirector() || $identity->isControlObra() || $identity->isExecutiveDirector() || $identity->hasPendingApprovals());
                        $defaultTab = $canManage ? 'management' : 'active';
                    @endphp
                    <div class="flex flex-col md:flex-row justify-between items-center mb-6 space-y-4 md:space-y-0">
PHP;

$content = str_replace($find, $replace, $content);
file_put_contents($file, $content);
echo "Blade view fixed.\n";
