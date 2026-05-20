<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Permission;
use App\Models\Profile;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Create the new permission
        $permission = Permission::updateOrCreate(
            ['name' => 'reimbursements.global_history'],
            [
                'display_name' => 'Ver Historial Global',
                'module' => 'reimbursements',
                'description' => 'Permite visualizar la pestaña de Historial Global en el listado de reembolsos.'
            ]
        );

        // 2. Associate the permission with all profiles except 'user'
        $profiles = Profile::where('name', '!=', 'user')->get();
        foreach ($profiles as $profile) {
            $profile->permissions()->syncWithoutDetaching([$permission->id]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $permission = Permission::where('name', 'reimbursements.global_history')->first();
        if ($permission) {
            // Detach from all profiles
            $permission->profiles()->detach();
            // Delete the permission
            $permission->delete();
        }
    }
};
