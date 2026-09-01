<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <a href="{{ route('cost_centers.index', ['tab' => 'active']) }}" class="flex h-10 w-10 items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-500 shadow-sm dark:border-gray-700 dark:bg-gray-800">←</a>
            <div><p class="text-[10px] font-black uppercase tracking-[0.22em] text-amber-500">Centros de costos</p><h2 class="text-xl font-black text-gray-900 dark:text-white">Desactivar {{ $costCenter->name }}</h2></div>
        </div>
    </x-slot>
    <div class="min-h-screen bg-gray-50 py-10 dark:bg-gray-900">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <form id="deactivation-form" action="{{ route('cost_centers.toggle_status', $costCenter) }}" method="POST" class="rounded-3xl border border-gray-200 bg-white p-6 shadow-xl dark:border-gray-700 dark:bg-gray-800 sm:p-8">
                @csrf
                @method('PATCH')
                <div id="step-1" class="space-y-8">
                    <div><p class="text-sm font-black uppercase tracking-widest text-amber-600">Paso 1 de 2</p><h3 class="mt-1 text-2xl font-black text-gray-900 dark:text-white">Decide qué ocurrirá con los reembolsos</h3><p class="mt-2 text-sm text-gray-500 dark:text-gray-400">El centro dejará de aceptar operaciones nuevas. Cada trámite abierto puede continuar hasta concluir o detenerse definitivamente.</p></div>
                    <div class="grid gap-4 rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-950 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-100 sm:grid-cols-3">
                        <div><p class="text-xs font-black uppercase opacity-70">Centro</p><p class="mt-1 font-black">{{ $costCenter->code }} · {{ $costCenter->name }}</p></div>
                        <div><p class="text-xs font-black uppercase opacity-70">Reembolsos abiertos</p><p class="mt-1 text-xl font-black">{{ $pendingReimbursements->count() }}</p></div>
                        <div><p class="text-xs font-black uppercase opacity-70">Acción</p><p class="mt-1 font-black">Sólo desactivar; nunca eliminar</p></div>
                    </div>
                    <section>
                        <div class="mb-4"><h4 class="text-lg font-black text-gray-900 dark:text-white">Reembolsos en proceso</h4><p class="text-sm text-gray-500">La decisión se aplica individualmente. Los reembolsos concluidos no se modifican.</p></div>
                        <div class="mb-4 space-y-4 rounded-2xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/60">
                            <div class="grid gap-3 md:grid-cols-[1.5fr_1fr_1fr_auto]">
                                <div>
                                    <label for="reimbursement-search" class="text-[10px] font-black uppercase tracking-widest text-gray-500">Buscar</label>
                                    <input id="reimbursement-search" type="search" class="mt-1 block w-full rounded-xl border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-900" placeholder="Folio, solicitante, beneficiario o paso">
                                </div>
                                <div>
                                    <label for="status-filter" class="text-[10px] font-black uppercase tracking-widest text-gray-500">Estado</label>
                                    <select id="status-filter" class="mt-1 block w-full rounded-xl border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-900">
                                        <option value="">Todos los estados</option>
                                        @foreach($pendingReimbursements->pluck('status')->filter()->unique()->sort()->values() as $status)
                                            <option value="{{ $status }}">{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label for="decision-filter" class="text-[10px] font-black uppercase tracking-widest text-gray-500">Decisión</label>
                                    <select id="decision-filter" class="mt-1 block w-full rounded-xl border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-900">
                                        <option value="">Todas las decisiones</option>
                                        <option value="continue">Continuar</option>
                                        <option value="reject">Detener y rechazar</option>
                                    </select>
                                </div>
                                <button type="button" id="clear-filters" class="self-end rounded-xl border border-gray-300 px-4 py-2.5 text-xs font-black dark:border-gray-600">Limpiar</button>
                            </div>
                            <div class="flex flex-col gap-3 border-t border-gray-200 pt-4 dark:border-gray-700 lg:flex-row lg:items-center lg:justify-between">
                                <label class="flex items-center gap-2 text-sm font-bold text-gray-700 dark:text-gray-200"><input id="select-filtered" type="checkbox" class="rounded border-gray-300 text-indigo-600"><span>Seleccionar resultados filtrados</span></label>
                                <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                                    <span id="selected-count" class="text-xs font-black text-gray-500">0 seleccionados</span>
                                    <select id="bulk-decision" class="rounded-xl border-gray-300 text-sm font-bold dark:border-gray-600 dark:bg-gray-900"><option value="continue">Continuar hasta concluir</option><option value="reject">Detener y rechazar</option></select>
                                    <button type="button" id="apply-bulk-decision" class="rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-black text-white hover:bg-indigo-700">Aplicar a seleccionados</button>
                                </div>
                            </div>
                        </div>
                        @forelse($pendingReimbursements as $reimbursement)
                            @php
                                $statusLabel = match ($reimbursement->status) {
                                    'borrador' => 'Borrador',
                                    'pendiente_autorizacion' => 'En autorización',
                                    'requiere_correccion' => 'Requiere corrección',
                                    'pendiente_revision_cxp' => 'Pendiente de revisión CXP',
                                    'pendiente_pago' => 'Pendiente de pago',
                                    'en_evento' => 'En evento',
                                    default => ucfirst(str_replace('_', ' ', $reimbursement->status)),
                                };
                            @endphp
                            <div class="reimbursement-card mb-3 grid gap-4 rounded-2xl border border-gray-200 p-4 dark:border-gray-700 md:grid-cols-[auto_1fr_1fr_1.1fr]" data-folio="{{ $reimbursement->folio ?? ('Reembolso #'.$reimbursement->id) }}" data-status="{{ $reimbursement->status }}" data-search="{{ mb_strtolower(implode(' ', [$reimbursement->folio, $reimbursement->user?->name, $reimbursement->payee?->name, $reimbursement->currentStep?->name, $statusLabel])) }}">
                                <div class="flex items-start"><input type="checkbox" class="reimbursement-selector mt-1 rounded border-gray-300 text-indigo-600" aria-label="Seleccionar {{ $reimbursement->folio ?? ('reembolso '.$reimbursement->id) }}"></div>
                                <div>
                                    <div class="flex flex-wrap items-center gap-2"><p class="font-black text-gray-900 dark:text-white">{{ $reimbursement->folio ?? ('Reembolso #'.$reimbursement->id) }}</p><a href="{{ route('reimbursements.show', $reimbursement) }}" target="_blank" rel="noopener" class="rounded-lg border border-indigo-200 px-2 py-1 text-[10px] font-black uppercase text-indigo-700 dark:border-indigo-800 dark:text-indigo-300">Ver detalle</a></div>
                                    <p class="mt-1 text-xs text-gray-500">{{ $reimbursement->user?->name ?? 'Sin solicitante' }}</p>
                                    <p class="mt-2 text-lg font-black text-indigo-700 dark:text-indigo-300">${{ number_format((float) $reimbursement->total + (float) ($reimbursement->propina ?? 0), 2) }} {{ $reimbursement->moneda ?? 'MXN' }}</p>
                                </div>
                                <div><p class="text-xs font-black uppercase text-gray-400">Estado actual</p><p class="mt-1 font-bold text-gray-800 dark:text-gray-200">{{ $statusLabel }}</p><p class="mt-1 text-xs text-gray-500">{{ $reimbursement->currentStep?->name ?? 'Sin paso asignado' }}</p></div>
                                <div>
                                    <label for="decision-{{ $reimbursement->id }}" class="text-xs font-black uppercase text-gray-400">Decisión</label>
                                    <select id="decision-{{ $reimbursement->id }}" name="reimbursement_decisions[{{ $reimbursement->id }}]" class="reimbursement-decision mt-2 block w-full rounded-xl border-gray-300 text-sm font-bold dark:border-gray-600 dark:bg-gray-900">
                                        <option value="continue" @selected(old('reimbursement_decisions.'.$reimbursement->id, 'continue') === 'continue')>Continuar hasta concluir</option>
                                        <option value="reject" @selected(old('reimbursement_decisions.'.$reimbursement->id) === 'reject')>Detener y rechazar</option>
                                    </select>
                                </div>
                            </div>
                        @empty
                            <p class="rounded-2xl bg-gray-50 p-5 text-sm text-gray-500 dark:bg-gray-900">No hay reembolsos abiertos. Puedes continuar con la desactivación.</p>
                        @endforelse
                        <p id="filter-empty" class="hidden rounded-2xl bg-gray-50 p-5 text-sm text-gray-500 dark:bg-gray-900">No hay reembolsos que coincidan con los filtros.</p>
                        <div id="reimbursement-pagination" class="mt-5 hidden flex-col gap-3 border-t border-gray-200 pt-5 dark:border-gray-700 sm:flex-row sm:items-center sm:justify-between">
                            <p id="pagination-summary" class="text-xs font-bold text-gray-500 dark:text-gray-400"></p>
                            <div class="flex flex-wrap items-center gap-2">
                                <button type="button" id="previous-page" class="rounded-lg border border-gray-300 px-3 py-2 text-xs font-black disabled:cursor-not-allowed disabled:opacity-40 dark:border-gray-600">Anterior</button>
                                <div id="page-numbers" class="flex flex-wrap items-center gap-1"></div>
                                <button type="button" id="next-page" class="rounded-lg border border-gray-300 px-3 py-2 text-xs font-black disabled:cursor-not-allowed disabled:opacity-40 dark:border-gray-600">Siguiente</button>
                            </div>
                        </div>
                    </section>
                    <div id="reason-container" class="hidden rounded-2xl border border-red-200 bg-red-50 p-5 dark:border-red-900 dark:bg-red-950/30">
                        <label for="deactivation_reason" class="text-xs font-black uppercase text-red-700 dark:text-red-300">Motivo para detener los reembolsos</label>
                        <textarea id="deactivation_reason" name="deactivation_reason" rows="3" maxlength="2000" class="mt-2 block w-full rounded-xl border-red-200 bg-white text-sm dark:border-red-900 dark:bg-gray-900" placeholder="Explica por qué estos reembolsos deben rechazarse.">{{ old('deactivation_reason') }}</textarea>
                        <x-input-error :messages="$errors->get('deactivation_reason')" class="mt-2" />
                    </div>
                    <x-input-error :messages="$errors->get('reimbursement_decisions')" class="mt-2" />
                    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end"><a href="{{ route('cost_centers.index', ['tab' => 'active']) }}" class="rounded-xl border border-gray-300 px-5 py-3 text-center text-sm font-black dark:border-gray-600">Cancelar</a><button type="button" id="continue" class="rounded-xl bg-indigo-600 px-6 py-3 text-sm font-black text-white">Continuar a revisión →</button></div>
                </div>
                <div id="step-2" class="hidden space-y-6">
                    <div><p class="text-sm font-black uppercase tracking-widest text-red-500">Paso 2 de 2</p><h3 class="mt-1 text-2xl font-black text-gray-900 dark:text-white">Revisa y confirma</h3></div>
                    <div id="review" class="space-y-2 rounded-2xl bg-gray-50 p-5 text-sm dark:bg-gray-900"></div>
                    <label class="flex gap-3 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-900 dark:border-red-900 dark:bg-red-950/30 dark:text-red-100"><input id="confirm" type="checkbox" class="mt-1 rounded border-red-300 text-red-600"><span>Confirmo que deseo desactivar este centro, aplicar estas decisiones y conservarlo en el historial.</span></label>
                    <div class="flex justify-between gap-3"><button type="button" id="back" class="rounded-xl border border-gray-300 px-5 py-3 font-black dark:border-gray-600">← Volver</button><button id="submit" disabled class="rounded-xl bg-red-600 px-5 py-3 font-black text-white opacity-50 disabled:cursor-not-allowed">Desactivar centro</button></div>
                </div>
            </form>
        </div>
    </div>
    <script>
        const reimbursementCards = Array.from(document.querySelectorAll('.reimbursement-card'));
        const pageSize = 10;
        let currentPage = 1;
        const pagination = document.getElementById('reimbursement-pagination');
        const paginationSummary = document.getElementById('pagination-summary');
        const pageNumbers = document.getElementById('page-numbers');
        const previousPage = document.getElementById('previous-page');
        const nextPage = document.getElementById('next-page');
        const searchInput = document.getElementById('reimbursement-search');
        const statusFilter = document.getElementById('status-filter');
        const decisionFilter = document.getElementById('decision-filter');
        const selectFiltered = document.getElementById('select-filtered');
        const selectedCount = document.getElementById('selected-count');
        const filterEmpty = document.getElementById('filter-empty');
        const normalize = value => String(value || '').toLocaleLowerCase('es').normalize('NFD').replace(/[̀-ͯ]/g, '');

        const filteredCards = () => {
            const search = normalize(searchInput.value.trim());
            return reimbursementCards.filter(card => {
                const decision = card.querySelector('.reimbursement-decision').value;
                return (!search || normalize(card.dataset.search).includes(search))
                    && (!statusFilter.value || card.dataset.status === statusFilter.value)
                    && (!decisionFilter.value || decision === decisionFilter.value);
            });
        };

        const syncSelectionState = () => {
            const filtered = filteredCards();
            const selected = reimbursementCards.filter(card => card.querySelector('.reimbursement-selector').checked);
            const selectedFiltered = filtered.filter(card => card.querySelector('.reimbursement-selector').checked);
            selectedCount.textContent = selected.length + (selected.length === 1 ? ' seleccionado' : ' seleccionados');
            selectFiltered.checked = filtered.length > 0 && selectedFiltered.length === filtered.length;
            selectFiltered.indeterminate = selectedFiltered.length > 0 && selectedFiltered.length < filtered.length;
        };

        const renderReimbursementPage = () => {
            const filtered = filteredCards();
            const pageCount = Math.max(1, Math.ceil(filtered.length / pageSize));
            currentPage = Math.min(currentPage, pageCount);
            const start = (currentPage - 1) * pageSize;
            const end = Math.min(start + pageSize, filtered.length);
            const visible = new Set(filtered.slice(start, end));
            reimbursementCards.forEach(card => card.classList.toggle('hidden', !visible.has(card)));
            filterEmpty.classList.toggle('hidden', filtered.length > 0 || reimbursementCards.length === 0);
            pagination.classList.toggle('hidden', reimbursementCards.length === 0);
            pagination.classList.toggle('flex', reimbursementCards.length > 0);
            paginationSummary.textContent = filtered.length
                ? 'Mostrando ' + (start + 1) + '–' + end + ' de ' + filtered.length
                : '0 resultados';
            previousPage.disabled = currentPage === 1;
            nextPage.disabled = currentPage === pageCount;
            pageNumbers.innerHTML = '';
            for (let page = 1; page <= pageCount; page += 1) {
                const button = document.createElement('button');
                button.type = 'button';
                button.textContent = page;
                button.className = page === currentPage
                    ? 'h-9 min-w-9 rounded-lg bg-indigo-600 px-2 text-xs font-black text-white'
                    : 'h-9 min-w-9 rounded-lg border border-gray-300 px-2 text-xs font-black dark:border-gray-600';
                button.addEventListener('click', () => { currentPage = page; renderReimbursementPage(); });
                pageNumbers.appendChild(button);
            }
            syncSelectionState();
        };
        previousPage.addEventListener('click', () => { if (currentPage > 1) { currentPage -= 1; renderReimbursementPage(); } });
        nextPage.addEventListener('click', () => {
            const pageCount = Math.max(1, Math.ceil(filteredCards().length / pageSize));
            if (currentPage < pageCount) { currentPage += 1; renderReimbursementPage(); }
        });

        [searchInput, statusFilter, decisionFilter].forEach(control => {
            control.addEventListener(control === searchInput ? 'input' : 'change', () => { currentPage = 1; renderReimbursementPage(); });
        });
        document.getElementById('clear-filters').addEventListener('click', () => {
            searchInput.value = '';
            statusFilter.value = '';
            decisionFilter.value = '';
            currentPage = 1;
            renderReimbursementPage();
        });
        reimbursementCards.forEach(card => card.querySelector('.reimbursement-selector').addEventListener('change', syncSelectionState));
        selectFiltered.addEventListener('change', () => {
            filteredCards().forEach(card => { card.querySelector('.reimbursement-selector').checked = selectFiltered.checked; });
            syncSelectionState();
        });
        document.getElementById('apply-bulk-decision').addEventListener('click', () => {
            const selected = reimbursementCards.filter(card => card.querySelector('.reimbursement-selector').checked);
            if (!selected.length) {
                alert('Selecciona al menos un reembolso.');
                return;
            }
            const value = document.getElementById('bulk-decision').value;
            selected.forEach(card => {
                const select = card.querySelector('.reimbursement-decision');
                select.value = value;
                select.dispatchEvent(new Event('change'));
            });
            currentPage = 1;
            renderReimbursementPage();
        });
        renderReimbursementPage();

        const decisions = Array.from(document.querySelectorAll('.reimbursement-decision'));
        const reasonContainer = document.getElementById('reason-container');
        const reason = document.getElementById('deactivation_reason');
        const updateReason = () => {
            const needed = decisions.some(select => select.value === 'reject');
            reasonContainer.classList.toggle('hidden', !needed);
            reason.required = needed;
        };
        decisions.forEach(select => select.addEventListener('change', updateReason));
        updateReason();
        document.getElementById('continue').addEventListener('click', () => {
            if (reason.required && !reason.value.trim()) { reason.focus(); reason.reportValidity(); return; }
            const review = document.getElementById('review');
            review.innerHTML = '';
            if (!decisions.length) review.textContent = 'No hay reembolsos abiertos; sólo se desactivará el centro.';
            decisions.forEach(select => {
                const row = document.createElement('div');
                row.className = 'flex flex-wrap justify-between gap-3 rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800';
                const folio = document.createElement('strong');
                folio.textContent = select.closest('.reimbursement-card').dataset.folio;
                const action = document.createElement('span');
                action.className = select.value === 'reject' ? 'font-black text-red-600' : 'font-black text-emerald-600';
                action.textContent = select.options[select.selectedIndex].text;
                row.append(folio, action);
                review.appendChild(row);
            });
            document.getElementById('step-1').classList.add('hidden');
            document.getElementById('step-2').classList.remove('hidden');
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
        document.getElementById('back').addEventListener('click', () => { document.getElementById('step-2').classList.add('hidden'); document.getElementById('step-1').classList.remove('hidden'); });
        document.getElementById('confirm').addEventListener('change', event => { const submit = document.getElementById('submit'); submit.disabled = !event.target.checked; submit.classList.toggle('opacity-50', !event.target.checked); });
    </script>
</x-app-layout>
