<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Seeder;

class RBACSeeder extends Seeder
{
    public function run(): void
    {
        // Define Modules and Permissions
        $modules = [
            'reimbursements' => ['view', 'create', 'create_special', 'edit', 'delete', 'approve', 'bulk_approve', 'export'],
            'users' => ['view', 'create', 'edit', 'delete'],
            'cost_centers' => ['view', 'create', 'edit', 'delete'],
            'travel_events' => ['view', 'create', 'edit', 'delete', 'close'],
            'profiles' => ['view', 'create', 'edit', 'delete'],
        ];

        $allPermissions = [];
        foreach ($modules as $module => $actions) {
            foreach ($actions as $action) {
                $permissionName = "{$module}.{$action}";
                $displayName = ucfirst($action) . " " . str_replace('_', ' ', $module);
                if ($action === 'create_special') {
                    $displayName = 'Create Fondo Fijo/Viajes';
                }
                if ($action === 'bulk_approve') {
                    $displayName = 'Aprobación Masiva (CSV)';
                }
                
                $allPermissions[$permissionName] = Permission::updateOrCreate(
                    ['name' => $permissionName],
                    ['display_name' => $displayName, 'module' => $module]
                );
            }
        }

        // Define Default Profiles
        $profilesData = [
            'admin' => [
                'display_name' => 'Administrador (Full)',
                'permissions' => array_keys($allPermissions),
            ],
            'admin_view' => [
                'display_name' => 'Administrador (Lectura)',
                'permissions' => ['reimbursements.view', 'users.view', 'cost_centers.view', 'travel_events.view', 'profiles.view'],
            ],
            'director' => [
                'display_name' => 'Director N1',
                'permissions' => ['reimbursements.view', 'reimbursements.approve'],
            ],
            'control_obra' => [
                'display_name' => 'Control de Obra N2',
                'permissions' => ['reimbursements.view', 'reimbursements.approve'],
            ],
            'director_ejecutivo' => [
                'display_name' => 'Director Ejecutivo N3',
                'permissions' => ['reimbursements.view', 'reimbursements.approve', 'users.view', 'cost_centers.view'],
            ],
            'accountant' => [
                'display_name' => 'Cuentas por Pagar N4',
                'permissions' => ['reimbursements.view', 'reimbursements.approve', 'reimbursements.bulk_approve', 'reimbursements.export', 'users.view', 'cost_centers.view'],
            ],
            'direccion' => [
                'display_name' => 'Subdirección N5',
                'permissions' => ['reimbursements.view', 'reimbursements.approve', 'users.view', 'cost_centers.view'],
            ],
            'tesoreria' => [
                'display_name' => 'Dirección N6',
                'permissions' => ['reimbursements.view', 'reimbursements.approve', 'reimbursements.bulk_approve', 'reimbursements.export'],
            ],
        ];

        foreach ($profilesData as $name => $data) {
            $profile = Profile::updateOrCreate(
                ['name' => $name],
                ['display_name' => $data['display_name']]
            );

            $permissionIds = [];
            foreach ($data['permissions'] as $pName) {
                if (isset($allPermissions[$pName])) {
                    $permissionIds[] = $allPermissions[$pName]->id;
                }
            }
            $profile->permissions()->sync($permissionIds);
        }

        // Migrate existing users to profiles based on their 'role' column
        User::all()->each(function ($user) {
            $profile = Profile::where('name', $user->role)->first();
            if ($profile) {
                $user->update(['profile_id' => $profile->id]);
            }
        });
    }
}
