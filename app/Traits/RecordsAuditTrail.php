<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

trait RecordsAuditTrail
{
    /** @var array<int, array<string, array{from: mixed, to: mixed}>> */
    private static array $pendingAuditChanges = [];

    public static function bootRecordsAuditTrail(): void
    {
        static::updating(function (Model $model): void {
            $changes = [];

            foreach ($model->getDirty() as $field => $newValue) {
                if (in_array($field, ['updated_at', 'password', 'remember_token', 'invitation_token'], true)) {
                    continue;
                }

                $changes[$field] = [
                    'from' => $model->getRawOriginal($field),
                    'to' => $newValue,
                ];
            }

            self::$pendingAuditChanges[spl_object_id($model)] = $changes;
        });

        static::updated(function (Model $model): void {
            $changes = self::$pendingAuditChanges[spl_object_id($model)] ?? [];
            unset(self::$pendingAuditChanges[spl_object_id($model)]);

            if ($changes !== []) {
                self::recordAudit($model, 'modificado', $changes);
            }
        });
    }

    public function auditLogs()
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }

    public function recordViewAudit(): void
    {
        self::recordAudit($this, 'consultado');
    }

    public function recordAuditActivity(string $action, array $changes = []): void
    {
        self::recordAudit($this, $action, $changes);
    }

    private static function recordAudit(Model $model, string $action, array $changes = []): void
    {
        $actorId = auth()->id();

        if (!$actorId) {
            return;
        }

        if ($action === 'modificado') {
            foreach (['admin_comment', 'user_correction_comment', 'rejection_comment', 'comment'] as $field) {
                $reason = request()?->input($field);
                if (is_string($reason) && trim($reason) !== '') {
                    $changes['motivo'] = ['from' => null, 'to' => trim($reason)];
                    break;
                }
            }
        }

        $log = new AuditLog([
            'actor_id' => $actorId,
            'action' => $action,
            'changes' => $changes ?: null,
            'ip_address' => request()?->ip(),
        ]);

        $model->auditLogs()->save($log);
    }
}
