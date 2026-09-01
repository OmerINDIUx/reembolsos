<?php

use App\Models\Permission;
use App\Models\Profile;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $permission = Permission::updateOrCreate(
            ['name' => 'dashboard.device_audit'],
            [
                'display_name' => 'Auditoría de dispositivos',
                'module' => 'dashboard',
                'description' => 'Permite acceder a la auditoría de accesos, dispositivos y seguridad de cuentas.',
            ]
        );

        Profile::where('name', 'admin')->each(function (Profile $profile) use ($permission): void {
            $profile->permissions()->syncWithoutDetaching([$permission->id]);
        });
    }

    public function down(): void
    {
        $permission = Permission::where('name', 'dashboard.device_audit')->first();

        if ($permission) {
            $permission->profiles()->detach();
            $permission->delete();
        }
    }
};
