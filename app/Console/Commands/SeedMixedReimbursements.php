<?php

namespace App\Console\Commands;

use App\Models\CostCenter;
use App\Models\Reimbursement;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SeedMixedReimbursements extends Command
{
    private const MARKER = '[DEMO MIXTO SEMANAS ATRAS]';

    protected $signature = 'reimbursement:seed-mixed {--count=72} {--replace : Elimina únicamente demos mixtos anteriores}';
    protected $description = 'Crea reembolsos demo mixtos en semanas anteriores para revisar Gestión, Pagos e Historial';

    public function handle(): int
    {
        $count = max(12, min(300, (int) $this->option('count')));
        $users = User::whereNotIn('role', ['admin_view'])->get();
        $costCenters = CostCenter::where('is_active', true)
            ->whereHas('approvalSteps')
            ->whereHas('fixedFunds', fn ($query) => $query->where('is_active', true))
            ->with([
                'approvalSteps' => fn ($query) => $query->with('user')->orderBy('order'),
                'fixedFunds' => fn ($query) => $query->where('is_active', true)->with('user'),
            ])
            ->get();

        if ($users->isEmpty() || $costCenters->isEmpty()) {
            $this->error('Se necesitan usuarios, centros activos, fondos fijos y pasos de aprobación.');
            return self::FAILURE;
        }

        $statuses = [
            'pendiente',
            'pendiente_revision_cxp',
            'pendiente_pago',
            'aprobado',
            'rechazado',
            'requiere_correccion',
        ];
        $types = ['reembolso', 'comida', 'fondo_fijo', 'viaje'];
        $categories = ['gasolina', 'comida', 'hospedaje', 'transporte', 'materiales', 'servicios', 'otros'];

        DB::transaction(function () use ($count, $users, $costCenters, $statuses, $types, $categories) {
            if ($this->option('replace')) {
                Reimbursement::where('observaciones', 'like', '%' . self::MARKER . '%')->delete();
            }

            for ($index = 0; $index < $count; $index++) {
                $status = $statuses[$index % count($statuses)];
                $type = $types[$index % count($types)];
                $cc = $costCenters[$index % $costCenters->count()];
                $owner = $users[($index * 3) % $users->count()];
                $fund = $cc->fixedFunds[$index % $cc->fixedFunds->count()];
                $steps = $cc->approvalSteps->values();

                $weeksAgo = 1 + ($index % 14);
                $createdAt = now()->subWeeks($weeksAgo)->startOfWeek()->addDays(2)->setTime(9 + ($index % 8), 15);
                $week = $createdAt->copy()->addDays(2)->format('W-Y');
                $amount = 450 + (($index * 347) % 14800);
                $tip = $type === 'comida' ? round(min($amount * 0.12, 450), 2) : 0;

                $currentStep = null;
                if ($status === 'pendiente') {
                    $currentStep = $steps[$index % $steps->count()];
                }

                $payeeId = $type === 'fondo_fijo' ? $fund->user_id : $owner->id;
                $reimbursementData = [
                    'type' => $type,
                    'cost_center_id' => $cc->id,
                    'fixed_fund_id' => $fund->id,
                    'user_id' => $owner->id,
                    'payee_id' => $payeeId,
                    'week' => $week,
                    'category' => $type === 'comida' ? 'comida' : $categories[$index % count($categories)],
                    'fecha' => $createdAt->copy()->subDays(1),
                    'total' => $amount,
                    'subtotal' => round($amount / 1.16, 2),
                    'impuestos' => round($amount - ($amount / 1.16), 2),
                    'propina' => $tip,
                    'moneda' => 'MXN',
                    'nombre_emisor' => 'PROVEEDOR DEMO ' . str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                    'rfc_emisor' => 'XAXX010101000',
                    'rfc_receptor' => 'GII010101AAA',
                    'nombre_receptor' => 'GAMI DEMOSTRACIÓN',
                    'status' => $status,
                    'current_step_id' => $currentStep?->id,
                    'title' => $type === 'viaje' ? 'Viaje demo semana ' . $week : null,
                    'validation_data' => ['uuid_match' => true, 'total_match' => true, 'demo' => true],
                    'observaciones' => self::MARKER . ' Escenario ' . str_replace('_', ' ', $status) . '.',
                ];
                if (Schema::hasColumn('reimbursements', 'created_by_id')) {
                    $reimbursementData['created_by_id'] = $owner->id;
                }
                $reimbursement = Reimbursement::create($reimbursementData);

                $reimbursement->timestamps = false;
                $reimbursement->created_at = $createdAt;
                $reimbursement->updated_at = $createdAt->copy()->addDays(min($weeksAgo * 2, 18));
                $this->applyLegacyApprovalDates($reimbursement, $steps, $status, $createdAt);
                $reimbursement->saveQuietly();
                $reimbursement->timestamps = true;

                $this->createApprovalTrail($reimbursement, $steps, $currentStep, $status, $owner, $createdAt);
            }
        });

        $summary = Reimbursement::where('observaciones', 'like', '%' . self::MARKER . '%')
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->orderBy('status')
            ->pluck('total', 'status');

        $this->info("Datos demo creados: {$summary->sum()}");
        $this->table(['Estado', 'Cantidad'], $summary->map(fn ($total, $status) => [$status, $total])->values()->all());
        return self::SUCCESS;
    }

    private function applyLegacyApprovalDates(Reimbursement $reimbursement, $steps, string $status, Carbon $createdAt): void
    {
        if ($status === 'pendiente') return;

        $approver = $steps->last()?->user_id;
        $reimbursement->approved_by_director_id = $approver;
        $reimbursement->approved_by_director_at = $createdAt->copy()->addDays(1);
        $reimbursement->approved_by_control_id = $approver;
        $reimbursement->approved_by_control_at = $createdAt->copy()->addDays(2);
        $reimbursement->approved_by_executive_id = $approver;
        $reimbursement->approved_by_executive_at = $createdAt->copy()->addDays(3);

        if (in_array($status, ['pendiente_pago', 'aprobado'], true)) {
            $reimbursement->approved_by_cxp_id = $approver;
            $reimbursement->approved_by_cxp_at = $createdAt->copy()->addDays(5);
        }
        if ($status === 'aprobado') {
            $reimbursement->approved_by_treasury_id = $approver;
            $reimbursement->approved_by_treasury_at = $createdAt->copy()->addDays(7);
        }
    }

    private function createApprovalTrail(Reimbursement $reimbursement, $steps, $currentStep, string $status, User $owner, Carbon $createdAt): void
    {
        $reimbursement->approvals()->create([
            'user_id' => $owner->id,
            'step_name' => 'Solicitante',
            'action' => 'enviado',
            'comment' => 'Solicitud demo enviada.',
        ]);

        $completedSteps = $status === 'pendiente'
            ? $steps->where('order', '<', $currentStep?->order)
            : $steps;

        foreach ($completedSteps as $offset => $step) {
            $approval = $reimbursement->approvals()->create([
                'user_id' => $step->user_id,
                'step_name' => $step->name,
                'action' => 'aprobado',
                'comment' => 'Aprobación demo.',
            ]);
            $approval->timestamps = false;
            $approval->created_at = $createdAt->copy()->addDays($offset + 1);
            $approval->updated_at = $approval->created_at;
            $approval->saveQuietly();
        }

        if (in_array($status, ['rechazado', 'requiere_correccion'], true)) {
            $step = $steps->last();
            $reimbursement->approvals()->create([
                'user_id' => $step?->user_id,
                'step_name' => $step?->name ?? 'Revisión',
                'action' => $status,
                'comment' => $status === 'rechazado' ? 'Rechazo demo definitivo.' : 'Corrección demo solicitada.',
            ]);
        }
    }
}
