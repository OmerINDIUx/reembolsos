<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\CostCenter;
use App\Models\Reimbursement;
use App\Models\ApprovalStep;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;

class SimulationSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create or Find Key Users
        $roles = [
            'admin', 'director', 'control_obra', 'director_ejecutivo', 
            'accountant', 'direccion', 'tesoreria'
        ];

        $users = [];
        foreach ($roles as $role) {
            $users[$role] = User::firstOrCreate(
                ['email' => $role . '@simulation.com'],
                [
                    'name' => 'Simulated ' . ucfirst(str_replace('_', ' ', $role)),
                    'password' => Hash::make('password'),
                    'role' => $role,
                ]
            );
        }

        // Additional employees
        $employees = [];
        for ($i = 1; $i <= 10; $i++) {
            $employees[] = User::firstOrCreate(
                ['email' => 'employee' . $i . '@simulation.com'],
                [
                    'name' => 'Employee ' . $i,
                    'password' => Hash::make('password'),
                    'role' => 'user',
                ]
            );
        }

        // 2. Create Cost Centers
        $ccNames = [
            'Torre Reforma - Construcción',
            'Parque Eólico - Mantenimiento',
            'Oficinas Corporativas - CDMX',
            'Proyecto Puentes Regionales',
            'Logística y Operaciones'
        ];

        $costCenters = [];
        foreach ($ccNames as $index => $name) {
            $cc = CostCenter::updateOrCreate(
                ['code' => 'CC-' . (100 + $index)],
                [
                    'name' => $name,
                    'director_id' => $users['director']->id,
                    'control_obra_id' => $users['control_obra']->id,
                    'director_ejecutivo_id' => $users['director_ejecutivo']->id,
                    'description' => 'Centro de costos simulado para pruebas de agrupación.',
                ]
            );
            $costCenters[] = $cc;

            // 3. Setup Approval Steps (if none)
            if ($cc->approvalSteps()->count() === 0) {
                ApprovalStep::create(['cost_center_id' => $cc->id, 'user_id' => $users['director']->id, 'order' => 1, 'name' => 'Director de Proyecto (N1)']);
                ApprovalStep::create(['cost_center_id' => $cc->id, 'user_id' => $users['control_obra']->id, 'order' => 2, 'name' => 'Control de Obra (N2)']);
                ApprovalStep::create(['cost_center_id' => $cc->id, 'user_id' => $users['director_ejecutivo']->id, 'order' => 3, 'name' => 'Director Ejecutivo (N3)']);
                ApprovalStep::create(['cost_center_id' => $cc->id, 'user_id' => $users['accountant']->id, 'order' => 4, 'name' => 'CXP (N4)']);
                ApprovalStep::create(['cost_center_id' => $cc->id, 'user_id' => $users['direccion']->id, 'order' => 5, 'name' => 'Subdirección (N5)']);
                ApprovalStep::create(['cost_center_id' => $cc->id, 'user_id' => $users['tesoreria']->id, 'order' => 6, 'name' => 'Dirección General (N6)']);
            }
        }

        // 4. Create Reimbursements for the last 4 weeks
        $types = ['reembolso', 'fondo_fijo', 'comida', 'viaje'];
        $categories = ['papeleria', 'mantenimiento', 'comida', 'viaticos', 'combustible'];
        $statuses = ['pendiente', 'aprobado_director', 'aprobado_control', 'aprobado', 'rechazado'];

        $now = Carbon::now();
        
        for ($i = 0; $i < 150; $i++) {
            // Randomly select a week from last 3 weeks + current week
            $weekOffset = rand(0, 3);
            $randomDate = $now->copy()->subWeeks($weekOffset)->subDays(rand(0, 6));
            $weekString = $randomDate->addDays(2)->format('W-Y'); // Matches the week logic in controller (addDays(2) to align Saturday-Friday)
            
            $cc = $costCenters[array_rand($costCenters)];
            $requester = $employees[array_rand($employees)];
            $type = $types[array_rand($types)];
            $status = $statuses[array_rand($statuses)];
            
            $total = rand(100, 5000);
            $subtotal = $total / 1.16;

            $reimbursement = Reimbursement::create([
                'type' => $type,
                'cost_center_id' => $cc->id,
                'week' => $weekString,
                'category' => $categories[array_rand($categories)],
                'uuid' => Str::uuid(),
                'rfc_emisor' => 'ABC' . rand(1000, 9999) . 'XYZ',
                'nombre_emisor' => 'Proveedor Simulado #' . rand(1, 50),
                'rfc_receptor' => 'GRP123456MOD',
                'nombre_receptor' => 'Grupo INDI S.A.',
                'folio' => strtoupper(substr($type, 0, 3)) . '-' . str_pad($i + 1, 6, '0', STR_PAD_LEFT),
                'fecha' => $randomDate->format('Y-m-d'),
                'total' => $total,
                'subtotal' => $subtotal,
                'impuestos' => $total - $subtotal,
                'moneda' => 'MXN',
                'status' => $status,
                'user_id' => $requester->id,
                'observaciones' => 'Simulación automática de datos para pruebas visuales.',
                'created_at' => $randomDate,
                'updated_at' => $randomDate,
            ]);
        }
    }
}
