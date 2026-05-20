<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Permission;

return new class extends Migration
{
    public function up(): void
    {
        Permission::updateOrCreate(
            ['name' => 'reimbursements.create_all_cost_centers'],
            [
                'display_name' => 'Reembolsar en todos los centros de costos',
                'module' => 'reimbursements',
                'description' => 'Permite crear reembolsos en cualquier centro de costos activo, sin necesidad de estar asignado al centro.',
            ]
        );
    }

    public function down(): void
    {
        $permission = Permission::where('name', 'reimbursements.create_all_cost_centers')->first();
        if ($permission) {
            $permission->profiles()->detach();
            $permission->delete();
        }
    }
};
