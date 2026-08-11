<x-app-layout>
    <x-slot name="header"><div class="flex items-center justify-between gap-4"><div><p class="text-xs font-black uppercase tracking-widest text-red-500">Administración</p><h2 class="text-2xl font-black text-gray-900 dark:text-white">Inhabilitar cuenta</h2></div><a href="{{ route('users.index') }}" class="rounded-xl border px-4 py-2 text-sm font-bold">Cancelar</a></div></x-slot>
    <div class="mx-auto max-w-6xl space-y-6 py-8">
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 text-amber-900"><strong>{{ $user->name }}</strong> conservará todo su histórico. Selecciona una decisión para cada reembolso y fondo fijo.</div>
        <form id="deactivation-form" method="POST" action="{{ route('users.destroy', $user) }}" class="rounded-3xl border bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:p-8">
            @csrf @method('DELETE')
            <input type="hidden" name="reimbursement_action" value="keep"><input type="hidden" name="reimbursement_transfer_to_user_id"><input type="hidden" name="approval_action" value="remove"><input type="hidden" name="approval_reassign_to_user_id"><input type="hidden" name="pending_approval_action" value="next"><input type="hidden" name="transfer_to_user_id">
            <div id="step-1" class="space-y-8"><div><p class="text-sm font-black uppercase tracking-widest text-red-500">Paso 1 de 2</p><h3 class="mt-1 text-xl font-black">Decisiones por operación</h3></div>
                <section><div class="mb-3 flex items-center justify-between"><h4 class="text-lg font-black">Reembolsos pendientes ({{ $pendingReimbursements->count() }})</h4><span class="text-xs text-gray-500">Centro de costos y acción individual</span></div>@forelse($pendingReimbursements as $reimbursement)<div class="mb-3 grid gap-3 rounded-2xl border p-4 md:grid-cols-[1fr_1.2fr_1fr]"><div><div class="flex flex-wrap items-center gap-2"><p class="font-black">{{ $reimbursement->folio ?? ('Reembolso #'.$reimbursement->id) }}</p><a href="{{ route('reimbursements.show', $reimbursement) }}" target="_blank" rel="noopener" class="inline-flex items-center rounded-lg border border-indigo-200 px-2.5 py-1 text-[11px] font-black text-indigo-700 hover:bg-indigo-50">Ver archivos</a></div><p class="mt-1 text-xs font-semibold text-gray-600">{{ ucfirst(str_replace('_' , ' ', $reimbursement->type ?? 'Reembolso')) }} · {{ ucfirst($reimbursement->category ?? 'Sin categoría') }}</p><p class="text-xs text-gray-500">Estado: {{ ucfirst(str_replace('_' , ' ', $reimbursement->status)) }} · Fecha: {{ $reimbursement->fecha?->format('d/m/Y') ?? 'Sin fecha' }}</p><p class="text-xs text-gray-500">Solicitante: {{ $reimbursement->user?->name ?? 'No disponible' }} · Beneficiario: {{ $reimbursement->payee?->name ?? $reimbursement->user?->name ?? 'No disponible' }}</p><p class="mt-2 text-lg font-black text-indigo-700">${{ number_format((float) $reimbursement->total + (float) ($reimbursement->propina ?? 0), 2) }} {{ $reimbursement->moneda ?? 'MXN' }}</p></div><div><p class="text-xs font-bold uppercase text-gray-500">Centro de costos</p><p class="font-bold">{{ $reimbursement->costCenter?->name ?? 'Sin centro de costos' }}</p><p class="text-xs text-gray-500">{{ $reimbursement->costCenter?->code ?? '' }}</p></div><div><select name="reimbursement_decisions[{{ $reimbursement->id }}]" class="fund-choice w-full rounded-xl"><option value="keep">Conservar este reembolso</option><option value="transfer">Transferir este reembolso</option></select><select name="reimbursement_transfer_to_user_ids[{{ $reimbursement->id }}]" class="transfer-target mt-2 hidden w-full rounded-xl"><option value="">Selecciona responsable</option>@foreach($activeUserCandidates as $candidate)<option value="{{ $candidate->id }}">{{ $candidate->name }} — {{ $candidate->role_name }}</option>@endforeach</select></div></div>@empty<p class="rounded-xl bg-gray-50 p-4 text-sm text-gray-500">No hay reembolsos pendientes.</p>@endforelse</section>
                <section><div class="mb-3 flex items-center justify-between"><h4 class="text-lg font-black">Fondos fijos ({{ $activeFunds->count() }})</h4><span class="text-xs text-gray-500">Eliminar o transferir uno por uno</span></div>@forelse($activeFunds as $fund)<div class="mb-3 grid gap-3 rounded-2xl border p-4 md:grid-cols-[1fr_1.2fr_1fr]"><div><p class="font-black">{{ $fund->name }}</p><p class="text-xs text-gray-500">Presupuesto: ${{ number_format($fund->budget, 2) }}</p></div><div><p class="text-xs font-bold uppercase text-gray-500">Centro de costos</p><p class="font-bold">{{ $fund->costCenter?->name ?? 'Sin centro de costos' }}</p><p class="text-xs text-gray-500">{{ $fund->costCenter?->code ?? '' }}</p></div><div><select name="fixed_fund_decisions[{{ $fund->id }}]" class="fund-choice w-full rounded-xl"><option value="delete">Eliminar fondo fijo</option><option value="transfer">Transferir fondo fijo</option></select><select name="fixed_fund_transfer_to_user_ids[{{ $fund->id }}]" class="transfer-target mt-2 hidden w-full rounded-xl"><option value="">Selecciona responsable</option>@foreach($activeUserCandidates as $candidate)<option value="{{ $candidate->id }}">{{ $candidate->name }} — {{ $candidate->role_name }}</option>@endforeach</select></div></div>@empty<p class="rounded-xl bg-gray-50 p-4 text-sm text-gray-500">No hay fondos fijos activos.</p>@endforelse</section>
                @if($approvalStepsCount)<section class="rounded-2xl border border-indigo-200 bg-indigo-50/50 p-5"><h4 class="text-lg font-black">Flujos de aprobación ({{ $approvalStepsCount }})</h4>@foreach($approvalSteps->groupBy(fn($step) => $step->costCenter?->id ?? 0) as $steps)<div class="mt-3 rounded-xl bg-white/70 p-3"><p class="font-black">{{ $steps->first()->costCenter?->name ?? 'Sin centro de costos' }}</p><p class="text-xs text-gray-600">{{ $steps->pluck('name')->filter()->join(', ') ?: $steps->count().' paso(s)' }}</p></div>@endforeach<select name="approval_action" class="mt-4 w-full rounded-xl"><option value="remove">Eliminar los pasos y continuar el flujo</option><option value="reassign">Sustituir al aprobador en todos los centros mostrados</option></select><select name="approval_reassign_to_user_id" class="mt-3 hidden w-full rounded-xl" id="approval-target"><option value="">Selecciona sustituto</option>@foreach($activeUserCandidates as $candidate)<option value="{{ $candidate->id }}">{{ $candidate->name }} — {{ $candidate->role_name }}</option>@endforeach</select><select name="pending_approval_action" class="mt-3 w-full rounded-xl"><option value="next">Enviar pendientes al siguiente paso</option><option value="previous">Regresarlos al paso anterior</option><option value="keep">Mantenerlos en el paso actual</option></select></section>@endif
                <div class="flex justify-end"><button type="button" id="continue" class="rounded-xl bg-indigo-600 px-5 py-3 font-black text-white">Continuar a revisión →</button></div>
            </div>
            <div id="step-2" class="hidden space-y-6"><div><p class="text-sm font-black uppercase tracking-widest text-red-500">Paso 2 de 2</p><h3 class="mt-1 text-xl font-black">Revisa y confirma</h3></div><div id="review" class="space-y-2 rounded-2xl bg-gray-50 p-5 text-sm dark:bg-gray-900"></div><label class="flex gap-3 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-900"><input id="confirm" type="checkbox" class="mt-1"><span>Confirmo que deseo inhabilitar la cuenta y aplicar las decisiones seleccionadas.</span></label><div class="flex justify-between gap-3"><button type="button" id="back" class="rounded-xl border px-5 py-3 font-black">← Volver</button><button id="submit" disabled class="rounded-xl bg-red-600 px-5 py-3 font-black text-white opacity-50 disabled:cursor-not-allowed">Inhabilitar cuenta</button></div></div>
        </form>
    </div>
    <script>
        const form = document.getElementById('deactivation-form');
        const approval = document.querySelector('#step-1 select[name="approval_action"]');
        const approvalTarget = document.getElementById('approval-target');

        document.querySelectorAll('select[name^="reimbursement_decisions"], select[name^="fixed_fund_decisions"]').forEach(select => {
            select.addEventListener('change', () => {
                const target = select.closest('.mb-3')?.querySelector('.transfer-target');
                if (target) target.classList.toggle('hidden', select.value !== 'transfer');
            });
        });

        if (approval) {
            approval.addEventListener('change', () => approvalTarget?.classList.toggle('hidden', approval.value !== 'reassign'));
        }

        const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, char => ({ '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#039;' }[char]));

        document.getElementById('continue').onclick = () => {
            const transfers = [...document.querySelectorAll('.transfer-target:not(.hidden)')];
            const missing = transfers.some(select => !select.value) || (approval?.value === 'reassign' && !approvalTarget?.value);
            if (missing) { alert('Selecciona un responsable para cada transferencia.'); return; }

            const cards = [...document.querySelectorAll('#step-1 section>div.mb-3')].map(row => {
                const cells = row.children;
                const title = cells[0]?.querySelector('p')?.textContent?.trim() || 'Registro';
                const center = cells[1]?.querySelectorAll('p')[1]?.textContent?.trim() || 'Sin centro de costos';
                const action = row.querySelector('select[name^="reimbursement_decisions"], select[name^="fixed_fund_decisions"]');
                const target = row.querySelector('.transfer-target:not(.hidden)');
                const actionText = action?.options[action.selectedIndex]?.text || '';
                const targetText = target?.options[target.selectedIndex]?.text || '';
                return `<div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm"><div class="flex flex-wrap items-start justify-between gap-3"><div><p class="font-black text-gray-900">${escapeHtml(title)}</p><p class="mt-1 text-xs font-semibold uppercase tracking-wide text-gray-500">Centro de costos: ${escapeHtml(center)}</p></div><div class="text-right text-sm"><p class="font-bold text-indigo-700">${escapeHtml(actionText)}</p>${targetText ? `<p class="mt-1 text-xs text-gray-600">Responsable: ${escapeHtml(targetText)}</p>` : ''}</div></div></div>`;
            }).join('');

            let approvalCard = '';
            if (approval) {
                const pending = document.querySelector('#step-1 select[name="pending_approval_action"]');
                const approver = approvalTarget?.options[approvalTarget.selectedIndex]?.text || '';
                approvalCard = `<div class="rounded-xl border border-indigo-200 bg-indigo-50 p-4"><p class="font-black text-indigo-900">Flujos de aprobación</p><p class="mt-1 text-sm text-indigo-800">${escapeHtml(approval.options[approval.selectedIndex].text)}</p>${approver ? `<p class="mt-1 text-xs text-indigo-700">Responsable: ${escapeHtml(approver)}</p>` : ''}<p class="mt-1 text-xs text-indigo-700">Pendientes: ${escapeHtml(pending?.options[pending.selectedIndex]?.text || '')}</p></div>`;
            }

            document.getElementById('review').innerHTML = (approvalCard + cards) || '<p>No hay operaciones pendientes.</p>';
            document.getElementById('step-1').classList.add('hidden');
            document.getElementById('step-2').classList.remove('hidden');
            window.scrollTo({ top: 0, behavior: 'smooth' });
        };

        document.getElementById('back').onclick = () => { document.getElementById('step-2').classList.add('hidden'); document.getElementById('step-1').classList.remove('hidden'); };
        document.getElementById('confirm').onchange = event => { document.getElementById('submit').disabled = !event.target.checked; document.getElementById('submit').classList.toggle('opacity-50', !event.target.checked); };
    </script>
</x-app-layout>