<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\CostCenter;
use App\Models\Reimbursement;
use App\Models\ApprovalStep;
use App\Models\TravelEvent;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class SimulationSeeder extends Seeder
{
    public function run(): void
    {
        // 0. Inform status
        $this->command->info('Clearing database tables...');

        // 1. Clear existing data (except admin@example.com)
        // Disable foreign key checks for clearing
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        Reimbursement::truncate();
        TravelEvent::truncate();
        DB::table('travel_event_user')->truncate(); // Pivot table
        ApprovalStep::truncate();
        CostCenter::truncate();
        
        // Delete all users except admin@example.com
        User::where('email', '!=', 'admin@example.com')->delete();
        
        // Ensure admin@example.com exists
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Root Admin',
                'password' => Hash::make('password'),
                'role' => 'admin',
            ]
        );

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->command->info('Seeding new data...');

        // 2. Create Role-Based Users
        $roles = [
            'director', 'control_obra', 'director_ejecutivo', 
            'accountant', 'direccion', 'tesoreria'
        ];

        $users = [];
        $users['admin'] = $admin;

        foreach ($roles as $role) {
            $users[$role] = User::create([
                'email' => $role . '@simulation.com',
                'name' => 'Simulated ' . ucfirst(str_replace('_', ' ', $role)),
                'password' => Hash::make('password'),
                'role' => $role,
            ]);
        }

        // Additional employees (Requesters)
        $employees = [];
        for ($i = 1; $i <= 10; $i++) {
            $employees[] = User::create([
                'email' => 'employee' . $i . '@simulation.com',
                'name' => 'Empleado ' . $i,
                'password' => Hash::make('password'),
                'role' => 'user',
            ]);
        }

        // 3. Create Cost Centers
        $ccNames = [
            'Torre Reforma - Construcción',
            'Parque Eólico - Mantenimiento',
            'Oficinas Corporativas - CDMX',
            'Proyecto Puentes Regionales',
            'Logística y Operaciones',
            'Sistemas y TI',
            'Recursos Humanos'
        ];

        $costCenters = [];
        foreach ($ccNames as $index => $name) {
            $cc = CostCenter::create([
                'code' => 'CC-' . (100 + $index),
                'name' => $name,
                'director_id' => $users['director']->id,
                'control_obra_id' => $users['control_obra']->id,
                'director_ejecutivo_id' => $users['director_ejecutivo']->id,
                'description' => 'Centro de costos con presupuesto dinámico y flujo variable.',
                'budget' => rand(100000, 1000000), // Adding substantial budget
            ]);
            $costCenters[] = $cc;

            // 4. Setup Approval Steps (Varied levels: some 3, some 6)
            $levels = (rand(1, 10) > 5) ? 6 : 3;

            ApprovalStep::create(['cost_center_id' => $cc->id, 'user_id' => $users['director']->id, 'order' => 1, 'name' => 'Director de Proyecto (N1)']);
            ApprovalStep::create(['cost_center_id' => $cc->id, 'user_id' => $users['control_obra']->id, 'order' => 2, 'name' => 'Control de Obra (N2)']);
            ApprovalStep::create(['cost_center_id' => $cc->id, 'user_id' => $users['director_ejecutivo']->id, 'order' => 3, 'name' => 'Director Ejecutivo (N3)']);
            
            if ($levels === 6) {
                ApprovalStep::create(['cost_center_id' => $cc->id, 'user_id' => $users['accountant']->id, 'order' => 4, 'name' => 'CXP (N4)']);
                ApprovalStep::create(['cost_center_id' => $cc->id, 'user_id' => $users['direccion']->id, 'order' => 5, 'name' => 'Subdirección (N5)']);
                ApprovalStep::create(['cost_center_id' => $cc->id, 'user_id' => $users['tesoreria']->id, 'order' => 6, 'name' => 'Dirección General (N6)']);
            }
        }

        // 5. Create Travel Events (Viajes y Eventos)
        $travelNames = [
            'Convención Anual Cancún',
            'Supervisión Obra Monterrey',
            'Capacitación Técnica Bajío',
            'Visita Proveedores Querétaro',
            'Junta Regional Guadalajara'
        ];

        $travelEvents = [];
        foreach ($travelNames as $index => $name) {
            $travelEvents[] = TravelEvent::create([
                'name' => $name,
                'code' => 'TVL-' . (2024001 + $index),
                'cost_center_id' => $costCenters[array_rand($costCenters)]->id,
                'user_id' => $employees[array_rand($employees)]->id,
                'director_id' => $users['director']->id,
                'location' => 'Ubicación Simuada ' . $index,
                'start_date' => Carbon::now()->addDays(rand(1, 30)),
                'end_date' => Carbon::now()->addDays(rand(31, 35)),
                'description' => 'Evento de viaje para fines de prueba de reembolsos agrupados.',
                'status' => 'approved'
            ]);
        }

        // 6. Create Reimbursements for the last 8 weeks
        $types = ['reembolso', 'fondo_fijo', 'comida', 'viaje'];
        $categories = ['papeleria', 'mantenimiento', 'comida', 'viaticos', 'combustible', 'hotel', 'transporte'];
        $statuses = ['pendiente', 'aprobado_director', 'aprobado_control', 'aprobado', 'rechazado'];

        $now = Carbon::now();
        
        for ($i = 0; $i < 200; $i++) {
            // Randomly select a week from last 8 weeks
            $weekOffset = rand(0, 8);
            $randomDate = $now->copy()->subWeeks($weekOffset)->subDays(rand(0, 6));
            $weekString = $randomDate->addDays(2)->format('W-Y'); 
            
            $cc = $costCenters[array_rand($costCenters)];
            $requester = $employees[array_rand($employees)];
            $type = $types[array_rand($types)];
            $status = $statuses[array_rand($statuses)];
            
            // Link to Travel Event if type is 'viaje' or randomly for 20% of others
            $travelEventId = null;
            if ($type === 'viaje' || rand(1, 100) <= 20) {
                $travelEventId = $travelEvents[array_rand($travelEvents)]->id;
            }

            $total = rand(100, 8000);
            $subtotal = $total / 1.16;

            Reimbursement::create([
                'type' => $type,
                'cost_center_id' => $cc->id,
                'travel_event_id' => $travelEventId,
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

        $this->command->info('Successfully seeded Simulation data!');
    }
}
