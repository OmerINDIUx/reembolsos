<?php

namespace App\Support;

use App\Models\Reimbursement;

class NotificationRouteHelper
{
    public static function reimbursement(?Reimbursement $reimbursement): string
    {
        return $reimbursement
            ? route('reimbursements.show', $reimbursement->id)
            : route('reimbursements.index');
    }

    public static function reimbursementsByIds(array $ids, string $tab = 'management'): string
    {
        $ids = collect($ids)
            ->filter(fn ($id) => filled($id) && is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($ids->count() === 1) {
            return route('reimbursements.show', $ids->first());
        }

        if ($ids->isNotEmpty()) {
            return route('reimbursements.audit', [
                'tab' => $tab,
                'ids' => $ids->implode(','),
            ]);
        }

        return route('reimbursements.index', ['tab' => $tab]);
    }
}
