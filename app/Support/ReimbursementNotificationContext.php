<?php

namespace App\Support;

use Illuminate\Support\Str;

class ReimbursementNotificationContext
{
    public static function from($reimbursement): array
    {
        $reimbursement->loadMissing(['costCenter', 'currentStep', 'user', 'payee', 'createdBy']);

        $folio = self::folio($reimbursement);
        $status = self::statusLabel($reimbursement);
        $action = self::actionLabel($reimbursement);
        $amount = (float) ($reimbursement->total ?? 0) + (float) ($reimbursement->propina ?? 0);
        $observations = trim((string) ($reimbursement->observaciones ?? ''));

        return [
            'id' => $reimbursement->id,
            'folio' => $folio,
            'status' => $status,
            'status_code' => (string) ($reimbursement->status ?? ''),
            'action' => $action,
            'stage' => $reimbursement->currentStep?->name ?: $status,
            'requester' => $reimbursement->user?->name ?: ($reimbursement->createdBy?->name ?? 'No identificado'),
            'payee' => $reimbursement->payee?->name ?: ($reimbursement->user?->name ?? 'No identificado'),
            'cost_center' => $reimbursement->costCenter?->name ?: 'Sin centro de costos',
            'category' => $reimbursement->category ?: 'Sin categoría',
            'issuer' => $reimbursement->nombre_emisor ?: 'Sin emisor capturado',
            'title' => $reimbursement->title ?: '',
            'amount' => $amount,
            'currency' => $reimbursement->moneda ?: 'MXN',
            'reason' => $observations !== '' ? Str::limit($observations, 220) : '',
            'url' => NotificationRouteHelper::reimbursement($reimbursement),
        ];
    }

    public static function folio($reimbursement): string
    {
        return (string) ($reimbursement->true_folio ?: $reimbursement->folio ?: ('REEMBOLSO-' . $reimbursement->id));
    }

    public static function statusLabel($reimbursement): string
    {
        return match ((string) $reimbursement->status) {
            'enviado' => 'Enviado para aprobación',
            'pendiente_revision_cxp' => 'Pendiente de revisión de CXP',
            'pendiente_pago' => $reimbursement->approved_by_treasury_at
                ? 'Disponible para pago'
                : 'Pendiente de autorización de pago',
            'requiere_correccion' => 'Requiere corrección',
            'rechazado' => 'Rechazado',
            'aprobado' => 'Aprobado',
            'pagado' => 'Pagado',
            'borrador' => 'Borrador',
            default => ucfirst(str_replace('_', ' ', (string) ($reimbursement->status ?: 'Sin estatus'))),
        };
    }

    public static function actionLabel($reimbursement): string
    {
        return match ((string) $reimbursement->status) {
            'enviado' => 'Revisar la solicitud y aprobarla o indicar el motivo de rechazo/corrección.',
            'pendiente_revision_cxp' => 'Validar la documentación, importes y datos fiscales; después aprobar o devolver para corrección.',
            'pendiente_pago' => $reimbursement->approved_by_treasury_at
                ? 'Generar o confirmar el pago desde el módulo de pagos.'
                : 'Revisar los datos bancarios y autorizar el pago.',
            'requiere_correccion' => 'Corregir los datos o adjuntos indicados y reenviar la solicitud.',
            'rechazado' => 'Consultar el motivo del rechazo; no requiere aprobación mientras permanezca en este estado.',
            'aprobado' => 'La solicitud ya fue aprobada; consultar el detalle para dar seguimiento al siguiente proceso.',
            'pagado' => 'El pago ya fue registrado; consultar el detalle si necesitas comprobarlo.',
            default => 'Abrir el detalle para revisar el estado y las acciones disponibles.',
        };
    }
}
