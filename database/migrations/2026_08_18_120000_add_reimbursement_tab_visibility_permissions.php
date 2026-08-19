<?php

use App\Models\Permission;
use App\Models\Profile;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $definitions = [
            'reimbursements.view_management' => ['Ver Módulo de Gestión', 'Permite visualizar la pestaña Módulo de Gestión.'],
            'reimbursements.view_rejections' => ['Ver Módulo de Rechazos', 'Permite visualizar la pestaña Módulo de Rechazos.'],
            'reimbursements.view_payment' => ['Ver Módulo de Pago', 'Permite visualizar la pestaña Módulo de Pago.'],
            'reimbursements.view_own' => ['Ver Mis Reembolsos', 'Permite visualizar la pestaña Mis Reembolsos.'],
            'reimbursements.view_own_history' => ['Ver Mis Pagados/Rechazados', 'Permite visualizar la pestaña Mis Pagados/Rechazados.'],
        ];

        $permissions = collect($definitions)->mapWithKeys(function (array $definition, string $name) {
            $permission = Permission::updateOrCreate(
                ['name' => $name],
                ['display_name' => $definition[0], 'module' => 'reimbursements', 'description' => $definition[1]]
            );

            return [$name => $permission];
        });

        Profile::query()->with('permissions')->get()->each(function (Profile $profile) use ($permissions): void {
            $currentNames = $profile->permissions->pluck('name');
            $grants = ['reimbursements.view_own', 'reimbursements.view_own_history'];

            if ($currentNames->intersect(['reimbursements.approve', 'users.view', 'reimbursements.global_history'])->isNotEmpty()) {
                $grants[] = 'reimbursements.view_management';
                $grants[] = 'reimbursements.view_rejections';
            }

            if (in_array($profile->name, ['admin', 'admin_view', 'accountant', 'tesoreria'], true)) {
                $grants[] = 'reimbursements.view_payment';
            }

            $profile->permissions()->syncWithoutDetaching(
                $permissions->only(array_unique($grants))->pluck('id')->all()
            );
        });
    }

    public function down(): void
    {
        Permission::query()
            ->whereIn('name', [
                'reimbursements.view_management',
                'reimbursements.view_rejections',
                'reimbursements.view_payment',
                'reimbursements.view_own',
                'reimbursements.view_own_history',
            ])
            ->get()
            ->each(function (Permission $permission): void {
                $permission->profiles()->detach();
                $permission->delete();
            });
    }
};
