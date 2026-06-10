<?php

use App\Models\Permission;
use App\Models\Profile;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $ownPermission = Permission::updateOrCreate(
            ['name' => 'dashboard.view_own'],
            [
                'display_name' => 'Puede ver panel propio',
                'module' => 'dashboard',
                'description' => 'Permite acceder al panel con datos personales y registros asignados al usuario.',
            ]
        );

        $globalPermission = Permission::updateOrCreate(
            ['name' => 'dashboard.view_global'],
            [
                'display_name' => 'Puede ver panel general',
                'module' => 'dashboard',
                'description' => 'Permite ver métricas generales de toda la operación en el panel.',
            ]
        );

        $oldPermission = Permission::where('name', 'dashboard.view')->first();
        $profilesWithOldPermission = $oldPermission
            ? $oldPermission->profiles()->pluck('profiles.id')
            : Profile::query()->pluck('id');

        Profile::whereIn('id', $profilesWithOldPermission)->each(function (Profile $profile) use ($ownPermission) {
            $profile->permissions()->syncWithoutDetaching([$ownPermission->id]);
        });

        Profile::whereIn('name', ['admin', 'admin_view', 'accountant', 'direccion', 'tesoreria'])
            ->each(function (Profile $profile) use ($globalPermission) {
                $profile->permissions()->syncWithoutDetaching([$globalPermission->id]);
            });

        if ($oldPermission) {
            $oldPermission->profiles()->detach();
            $oldPermission->delete();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $oldPermission = Permission::updateOrCreate(
            ['name' => 'dashboard.view'],
            [
                'display_name' => 'Ver Panel',
                'module' => 'dashboard',
                'description' => 'Permite acceder al panel principal de métricas y resumen.',
            ]
        );

        Profile::query()->each(function (Profile $profile) use ($oldPermission) {
            $profile->permissions()->syncWithoutDetaching([$oldPermission->id]);
        });

        foreach (['dashboard.view_own', 'dashboard.view_global'] as $permissionName) {
            $permission = Permission::where('name', $permissionName)->first();

            if ($permission) {
                $permission->profiles()->detach();
                $permission->delete();
            }
        }
    }
};
