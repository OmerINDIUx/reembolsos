@if(Auth::user()->isAdmin())
    @php
        $isReimbursementAudit = request()->routeIs('reimbursements.show');
        $actionLabels = [
            'consultado' => 'Consultó el registro',
            'modificado' => 'Actualizó información',
            'configuración actualizada' => 'Actualizó la configuración',
        ];
        if ($isReimbursementAudit) {
            $actionLabels += [
            'abrió documento: PDF' => 'Abrió el comprobante PDF',
            'abrió documento: XML' => 'Abrió el archivo XML',
            'abrió documento: TICKET' => 'Abrió el ticket',
            'descargó documento: PDF' => 'Descargó el comprobante PDF',
            'descargó documento: XML' => 'Descargó el archivo XML',
            'descargó documento: TICKET' => 'Descargó el ticket',
            ];
        }
        $fieldLabels = [
            'name' => 'Nombre', 'email' => 'Correo electrónico', 'email_normalized' => 'Correo normalizado',
            'role' => 'Rol', 'profile_id' => 'Perfil', 'status' => 'Estatus', 'budget' => 'Presupuesto',
            'code' => 'Código', 'company_id' => 'Empresa', 'description' => 'Descripción',
            'menfis_email' => 'Correo Menfis', 'beneficiary_id' => 'Beneficiario', 'is_active' => 'Activo',
            'type' => 'Tipo', 'cost_center_id' => 'Centro de costos', 'category' => 'Categoría',
            'total' => 'Total', 'subtotal' => 'Subtotal', 'propina' => 'Propina', 'fecha' => 'Fecha',
            'payment_week' => 'Semana de pago', 'current_step_id' => 'Paso actual', 'observaciones' => 'Observaciones',
            'payee_id' => 'Beneficiario del pago', 'title' => 'Título', 'week' => 'Semana',
            'motivo' => 'Motivo del cambio', 'approvers' => 'Flujo de aprobadores',
            'authorized_users' => 'Usuarios autorizados', 'fixed_funds' => 'Fondos fijos',
        ];
        $formatValue = function (string $field, mixed $value): string {
            if ($value === null || $value === '') return 'Sin valor';
            if (in_array($field, ['is_active', 'company_confirmed'], true)) return $value ? 'Sí' : 'No';
            if (in_array($field, ['budget', 'total', 'subtotal', 'propina', 'impuestos'], true) && is_numeric($value)) return '$' . number_format((float) $value, 2);
            if ($field === 'company_id' && is_numeric($value)) return \App\Models\Company::find($value)?->name ?? 'Empresa #' . $value;
            if (is_array($value)) return 'Información actualizada';
            return \Illuminate\Support\Str::limit((string) $value, 100);
        };
    @endphp

    <section class="mt-8 overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-xl shadow-gray-200/40 dark:border-gray-700 dark:bg-gray-800 dark:shadow-none">
        <div class="flex flex-col gap-4 border-b border-gray-100 px-6 py-6 dark:border-gray-700 sm:flex-row sm:items-center sm:justify-between sm:px-8">
            <div class="flex items-center gap-4">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-indigo-600 text-white shadow-lg shadow-indigo-600/20">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 4H7a2 2 0 01-2-2V6a2 2 0 012-2h6l4 4v10a2 2 0 01-2 2zM13 4v4h4" /></svg>
                </div>
                <div>
                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-indigo-600 dark:text-indigo-400">Control administrativo</p>
                    <h3 class="mt-0.5 text-xl font-black uppercase tracking-tight text-gray-900 dark:text-white">Auditoría del registro</h3>
                    <p class="mt-1 text-xs font-medium text-gray-500 dark:text-gray-400">Consultas, cambios y accesos a documentos.</p>
                </div>
            </div>
            <span class="w-fit rounded-full bg-indigo-50 px-3 py-1.5 text-[10px] font-black uppercase tracking-widest text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300">Solo administradores</span>
        </div>

        <div class="border-b border-gray-100 bg-gray-50/70 px-6 py-5 dark:border-gray-700 dark:bg-gray-900/20 sm:px-8">
            <form method="GET" class="grid gap-3 md:grid-cols-2 xl:grid-cols-[minmax(0,1fr)_auto_auto_auto_auto]">
                @foreach(request()->except(['audit_search', 'audit_action', 'audit_from', 'audit_to', 'audit_page']) as $key => $value)
                    @if(is_scalar($value))
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endif
                @endforeach
                <div class="relative">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m2.35-5.15a7.5 7.5 0 11-15 0 7.5 7.5 0 0115 0z" /></svg>
                    <input name="audit_search" value="{{ request('audit_search') }}" placeholder="Buscar por nombre o correo" class="w-full rounded-xl border-gray-200 py-2.5 pl-10 text-sm font-medium text-gray-700 shadow-sm placeholder:text-gray-400 focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                </div>
                <select name="audit_action" class="rounded-xl border-gray-200 py-2.5 text-sm font-bold text-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
                    <option value="">Toda la actividad</option>
                    @foreach($actionLabels as $value => $label)
                        <option value="{{ $value }}" @selected(request('audit_action') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <input type="date" name="audit_from" value="{{ request('audit_from') }}" aria-label="Desde" class="rounded-xl border-gray-200 py-2.5 text-sm font-medium text-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
                <input type="date" name="audit_to" value="{{ request('audit_to') }}" aria-label="Hasta" class="rounded-xl border-gray-200 py-2.5 text-sm font-medium text-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
                <button class="rounded-xl bg-indigo-600 px-5 py-2.5 text-xs font-black uppercase tracking-widest text-white shadow-lg shadow-indigo-600/20 transition hover:bg-indigo-700">Aplicar</button>
            </form>
        </div>

        <div class="divide-y divide-gray-100 dark:divide-gray-700">
            @forelse($auditLogs as $log)
                <article class="flex gap-4 px-6 py-5 transition hover:bg-gray-50/70 dark:hover:bg-gray-900/20 sm:px-8">
                    <div class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-300">
                        @if(str_starts_with($log->action, 'abrió') || str_starts_with($log->action, 'descargó'))
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v8m0 0 3-3m-3 3-3-3m9 4v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4" /></svg>
                        @elseif($log->action === 'modificado' || $log->action === 'configuración actualizada')
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m16.86 3.49 3.65 3.65M6 18l-2 2 2-6L15.5 4.5a2.58 2.58 0 013.65 3.65L9.65 17.65 6 18z" /></svg>
                        @else
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.55 2.28A1 1 0 0120 13.17v.66a1 1 0 01-.45.89L15 17m0-7v7m0-7L6.45 5.72A1 1 0 005 6.61v10.78a1 1 0 001.45.89L15 14.5" /></svg>
                        @endif
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                            <p class="text-sm font-bold text-gray-900 dark:text-white">
                                {{ $log->actor?->name ?? 'Usuario eliminado' }}
                                <span class="font-medium text-gray-500 dark:text-gray-400">{{ $actionLabels[$log->action] ?? $log->action }}</span>
                            </p>
                            <time class="shrink-0 text-[10px] font-black uppercase tracking-widest text-gray-400" datetime="{{ $log->created_at->toIso8601String() }}">{{ $log->created_at->format('d/m/Y · H:i') }}</time>
                        </div>
                        @if($log->changes)
                            <div class="mt-3 space-y-1.5">
                                @foreach($log->changes as $field => $change)
                                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">
                                        <span class="font-black uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ $fieldLabels[$field] ?? str($field)->replace('_', ' ')->headline() }}:</span>
                                        <span class="line-through decoration-gray-300">{{ $formatValue($field, $change['from'] ?? null) }}</span>
                                        <span class="mx-1 text-indigo-500">→</span>
                                        <span class="font-bold text-gray-700 dark:text-gray-200">{{ $formatValue($field, $change['to'] ?? null) }}</span>
                                    </p>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </article>
            @empty
                <div class="px-6 py-14 text-center sm:px-8">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-gray-100 text-gray-400 dark:bg-gray-700 dark:text-gray-300"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m-9 4h12a2 2 0 002-2V6a2 2 0 00-2-2H9l-3 3v11a2 2 0 002 2z" /></svg></div>
                    <p class="mt-4 text-sm font-black uppercase tracking-widest text-gray-500 dark:text-gray-400">Sin actividad para estos filtros</p>
                </div>
            @endforelse
        </div>

        @if($auditLogs instanceof \Illuminate\Pagination\LengthAwarePaginator && $auditLogs->hasPages())
            <div class="border-t border-gray-100 px-6 py-4 dark:border-gray-700 sm:px-8">{{ $auditLogs->links() }}</div>
        @endif
    </section>
@endif
