<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\CostCenter;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class CostCenterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure director exists
        $director = User::firstOrCreate(
            ['email' => 'director@example.com'],
            [
                'name' => 'Director General',
                'password' => Hash::make('password'), // Default password
                'role' => 'director',
            ]
        );

        $centers = [
            'AEROPUERTO OAXACA',
            'ARRIAGA TAPACHULA',
            'CABLE OBREGÓN',
            'CABLEBUS L3 OPERACIÓN',
            'CABO RODAJE',
            'CASA COYOACAN',
            'COMIDA AICM',
            'CONAVI ARBOLEDAS',
            'CONAVI RINCON DEL AGUA',
            'CONAVI YUCATAN',
            'CONAVI ZAPOTLAN',
            'Concretera LZC',
            'Concretera Tijuana',
            'CONCRETERAS LINEA K',
            'CONEXIÓN ORIENTE',
            'DEDO NORTE',
            'EDIFICACION ESTACIONES',
            'EDIFICACIONES CDMX',
            'EMBAJADA CANADA',
            'ESTACIÓN SANTA FE',
            'ESTACIONES SANTA MARTHA',
            'EXPOSICION MONTERREY',
            'FacilidadesGuadalajara',
            'FASE 3 MANZANILLO',
            'GAP GUADALAJARA',
            'HOSPITAL LOS CABOS',
            'HOSPITAL TAMPICO',
            'HPH FASE 4',
            'INIFED CDMX',
            'INIFED LOS CABOS',
            'INIFED QUINTANA ROO',
            'Japón Utopía',
            'LERMA NUEVO LAREDO',
            'LERMA TRITURACION MANZANILLO',
            'LERMA VIAS',
            'LIBRAMIENTO MORELIA',
            'LINEA 3 CABLEBUS',
            'LINEA K',
            'LINEA K FERROVIARIAS',
            'MTTO CATENARIA',
            'OBRA CETRAM MARTIN CARRERA',
            'PANTALON L12',
            'PLATAFORMAS TIJUANA',
            'PUENTES RECAL',
            'RAMPAS TROLEBUS',
            'REHABILITACIÓN CONSTITUYENTES',
            'ROMPEOLAS SALINA CRUZ',
            'Rompeolas Veracruz',
            'SPV TALLERES',
            'T1 CANCUN',
            'TALLER PANTITLAN',
            'TALLERES TREN MAYA',
            'TELEFERICO URUAPAN',
            'TRAMO 5 SUR',
            'TREN GOLFO DE MEXICO',
            'TREN MAYA TRAMO 3',
            'TROLEBUS CATANARIA',
            'TUNEL 12 PR',
            'TUNEL FALSO TREN TOLUCA',
            'UTOPIA TOPILEJO',
            'VIADUCTO MONTERREY',
            'VILLAHERMOSA ASUR',
            'Vivienda CONAVI',
        ];

        foreach ($centers as $centerName) {
            // Generate a code from the name (e.g. UPPER-CASE-SLUG)
            // or just use the name if code is not needed but required by DB
            // Using slug is safer for URLs/Codes
            $code = Str::upper(Str::slug($centerName));

            CostCenter::create([
                'name' => $centerName,
                'code' => $code, // Auto-generated
                'director_id' => $director->id,
                'description' => null,
            ]);
        }
    }
}
