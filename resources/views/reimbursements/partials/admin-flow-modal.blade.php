@php
    $flowHasErrors = $errors->getBag('adminFlow')->any();
    $flowStatus = $flowHasErrors ? old('status') : '';
@endphp
<div x-data="{
        open: @js($flowHasErrors), submitting: false, choosing: false, query: '', trigger: null,
        status: @js($flowStatus),
        type: @js($flowHasErrors ? old('type') : $reimbursement->type),
        center: @js((string) ($flowHasErrors ? old('cost_center_id') : $reimbursement->cost_center_id)),
        comment: @js($flowHasErrors ? old('admin_comment', '') : ''),
        options: @js($adminFlowCostCenters->map(fn ($center) => ['value' => (string) $center->id, 'name' => $center->name, 'code' => $center->code])->values()),
        get selected() { return this.options.find(option => option.value === this.center); },
        get filtered() {
            const normalize = value => String(value || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
            const term = normalize(this.query.trim());
            return this.options.filter(option => normalize(option.name + ' ' + option.code).includes(term));
        },
        close() { if (!this.submitting) { this.open = false; this.choosing = false; this.trigger?.focus(); } },
        focusInside(event) {
            const elements = [...this.$refs.dialog.querySelectorAll('button, input, select, textarea, [tabindex]')].filter(el => !el.disabled && el.type !== 'hidden' && el.getClientRects().length);
            const first = elements[0], last = elements[elements.length - 1];
            if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last?.focus(); }
            else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first?.focus(); }
        }
    }"
    x-init="$watch('open', value => { if (value) $nextTick(() => $refs.status.focus()); }); if (open) $nextTick(() => $refs.status.focus())"
    @open-admin-flow-modal.window="trigger = document.activeElement; open = true"
    @keydown.escape.window="if (open) { if (choosing) { choosing = false; $refs.centerButton.focus(); } else close(); }"
    x-show="open" x-cloak
    class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/60 p-4 sm:p-6">
    <div class="flex min-h-full items-center justify-center" @click.self="close()">
        <section x-ref="dialog" role="dialog" aria-modal="true" aria-labelledby="flow-title" aria-describedby="flow-description"
            @keydown.tab="focusInside($event)"
            class="relative w-full max-w-2xl overflow-hidden rounded-2xl bg-white text-left shadow-2xl dark:bg-gray-800">
            <form action="{{ route('reimbursements.admin_flow_update', $reimbursement) }}" method="POST"
                @submit="if (submitting || !selected || !comment.trim()) { $event.preventDefault(); } else { submitting = true; choosing = false; }">
                @csrf
                @method('PATCH')
                <header class="flex items-start justify-between gap-4 border-b border-gray-100 px-6 py-5 dark:border-gray-700 sm:px-8">
                    <div>
                        <p class="mb-1 text-xs font-semibold uppercase tracking-wider text-indigo-600 dark:text-indigo-300">Solicitud {{ $reimbursement->true_folio }}</p>
                        <h2 id="flow-title" class="text-xl font-bold text-gray-900 dark:text-white">Editar flujo</h2>
                        <p id="flow-description" class="mt-1 text-sm text-gray-500 dark:text-gray-400">Define cómo debe continuar esta solicitud.</p>
                    </div>
                    <button type="button" @click="close()" :disabled="submitting" aria-label="Cerrar edición de flujo" class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 focus:ring-2 focus:ring-indigo-500 dark:hover:bg-gray-700">✕</button>
                </header>
                <div class="max-h-[65vh] space-y-5 overflow-y-auto px-6 py-5 sm:px-8">
                    @if($flowHasErrors)
                        <div role="alert" class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-800 dark:bg-red-900/20 dark:text-red-200">
                            <p class="font-semibold">Revisa los siguientes datos para guardar:</p>
                            <ul class="mt-1 list-inside list-disc">@foreach($errors->getBag('adminFlow')->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                        </div>
                    @endif
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="flow-status" class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-200">Estado al guardar</label>
                            <select id="flow-status" x-ref="status" name="status" x-model="status" class="w-full rounded-xl border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                <option value="">Conservar estado y etapa actuales</option>
                                @foreach($adminFlowStatusOptions as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
                            </select>
                        </div>
                        <div>
                            <label for="flow-type" class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-200">Tipo de solicitud</label>
                            <select id="flow-type" name="type" x-model="type" required class="w-full rounded-xl border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                <option value="" disabled>Selecciona un tipo</option>
                                @foreach($adminFlowTypeOptions as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
                            </select>
                        </div>
                    </div>
                    <div>
                        <label id="flow-center-label" class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-200">Centro de costos</label>
                        <input type="hidden" name="cost_center_id" :value="center">
                        <button type="button" x-ref="centerButton" aria-labelledby="flow-center-label flow-center-value" aria-controls="flow-center-picker" :aria-expanded="choosing"
                            @click="choosing = !choosing; query = ''; if (choosing) $nextTick(() => $refs.search.focus())"
                            class="flex w-full items-center justify-between gap-3 rounded-xl border border-gray-300 px-4 py-3 text-left text-sm focus:ring-2 focus:ring-indigo-500 dark:border-gray-600 dark:text-white">
                            <span id="flow-center-value" class="min-w-0 break-words">
                                <span class="block font-medium" x-text="selected?.name || 'Selecciona un centro de costos'"></span>
                                <span class="mt-0.5 block text-xs text-gray-500 dark:text-gray-400" x-text="selected?.code || ''"></span>
                            </span>
                            <span class="shrink-0 text-indigo-600 dark:text-indigo-300" x-text="choosing ? 'Cerrar' : 'Cambiar'"></span>
                        </button>
                        <div id="flow-center-picker" x-show="choosing" x-cloak class="mt-2 rounded-xl border border-gray-200 bg-gray-50 p-3 dark:border-gray-600 dark:bg-gray-900/40">
                            <label for="flow-center-search" class="sr-only">Buscar por nombre o código</label>
                            <input id="flow-center-search" x-ref="search" type="search" x-model="query" @keydown.enter.prevent autocomplete="off" placeholder="Buscar por nombre o código…" class="w-full rounded-lg border-gray-300 text-sm focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            <p class="my-2 text-xs text-gray-500 dark:text-gray-400" role="status" x-text="filtered.length + ' centros disponibles'"></p>
                            <div class="max-h-44 space-y-1 overflow-y-auto" aria-label="Centros de costos disponibles">
                                <template x-for="option in filtered" :key="option.value">
                                    <button type="button" :aria-pressed="center === option.value" @click="center = option.value; choosing = false; $refs.centerButton.focus()"
                                        :class="center === option.value ? 'bg-indigo-100 text-indigo-900 dark:bg-indigo-900/50 dark:text-indigo-100' : 'text-gray-700 hover:bg-white dark:text-gray-200 dark:hover:bg-gray-700'"
                                        class="flex w-full items-start justify-between gap-3 rounded-lg px-3 py-2.5 text-left text-sm focus:ring-2 focus:ring-indigo-500">
                                        <span class="min-w-0 break-words"><span class="block font-medium" x-text="option.name"></span><span class="block text-xs opacity-70" x-text="option.code"></span></span>
                                        <span x-show="center === option.value" class="shrink-0 text-xs font-semibold">Elegido</span>
                                    </button>
                                </template>
                                <p x-show="filtered.length === 0" class="px-3 py-4 text-sm text-gray-500 dark:text-gray-400">No hay coincidencias. Prueba con otro nombre o código.</p>
                            </div>
                        </div>
                    </div>
                    <div class="rounded-xl border border-indigo-100 bg-indigo-50 p-4 text-sm text-indigo-900 dark:border-indigo-800 dark:bg-indigo-900/20 dark:text-indigo-200" aria-live="polite">
                        <p class="font-semibold">Qué ocurrirá al guardar</p>
                        <p class="mt-1 leading-relaxed" x-show="!status">Se conservarán el estado, la etapa pendiente y las aprobaciones existentes. Si cambias de centro, debe existir una etapa equivalente.</p>
                        <p class="mt-1 leading-relaxed" x-show="status === 'enviado'">La solicitud continuará desde su etapa pendiente, conservando las aprobaciones existentes. No volverá al inicio.</p>
                        <p class="mt-1 leading-relaxed" x-show="status === 'requiere_correccion'">La solicitud quedará pendiente de corrección del solicitante. No avanzará en aprobación hasta que vuelva a enviarla.</p>
                        <p class="mt-1 leading-relaxed" x-show="status === 'rechazado'">La solicitud quedará rechazada y fuera del flujo de aprobación.</p>
                        <p class="mt-2 leading-relaxed" x-show="type === 'fondo_fijo'">Se asignará un fondo fijo activo del centro y su responsable como destinatario del pago.</p>
                    </div>
                    <div>
                        <div class="mb-1.5 flex items-center justify-between gap-2">
                            <label for="flow-comment" class="text-sm font-semibold text-gray-700 dark:text-gray-200">Motivo del ajuste <span class="font-normal text-gray-500">(obligatorio)</span></label>
                            <span class="text-xs text-gray-500" x-text="comment.length + '/1000'"></span>
                        </div>
                        <textarea id="flow-comment" name="admin_comment" x-model="comment" rows="3" required maxlength="1000" aria-describedby="flow-comment-help" class="w-full rounded-xl border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white" placeholder="Ej. Reasignar al centro correcto y reenviar a autorización."></textarea>
                        <p id="flow-comment-help" class="mt-1 text-xs text-gray-500 dark:text-gray-400">Este motivo quedará registrado en el historial de la solicitud.</p>
                    </div>
                </div>
                <footer class="flex flex-col-reverse gap-3 border-t border-gray-100 bg-gray-50 px-6 py-4 dark:border-gray-700 dark:bg-gray-900/40 sm:flex-row sm:justify-end sm:px-8">
                    <button type="button" @click="close()" :disabled="submitting" class="rounded-xl border border-gray-300 px-5 py-2.5 text-sm font-semibold text-gray-700 hover:bg-white focus:ring-2 focus:ring-indigo-500 disabled:opacity-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">Cancelar</button>
                    <button type="submit" :disabled="submitting || !selected || !comment.trim() || !type" class="rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                        <span x-text="submitting ? 'Guardando…' : 'Guardar ajuste'"></span>
                    </button>
                </footer>
            </form>
        </section>
    </div>
</div>
