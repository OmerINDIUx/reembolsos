<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <nav class="flex mb-2 text-xs font-medium text-gray-500 dark:text-gray-400">
                    <a href="{{ route('cost_centers.index') }}" class="hover:text-indigo-600">Centros de Costos</a><span class="mx-2">/</span>
                    <a href="{{ route('cost_centers.show', $costCenter) }}" class="hover:text-indigo-600">{{ $costCenter->code }}</a><span class="mx-2">/</span><span>Fondos fijos</span>
                </nav>
                <h2 class="font-black text-3xl text-gray-900 dark:text-white uppercase tracking-tighter">Historial de Budget</h2>
                <p class="text-sm text-gray-500 mt-1">Entradas, salidas y reposiciones · {{ $costCenter->name }}</p>
            </div>
            <a href="{{ route('cost_centers.show', $costCenter) }}" class="inline-flex items-center justify-center px-5 py-3 bg-indigo-600 text-white text-xs font-black uppercase rounded-xl">&larr; Volver al panel</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
                <div class="p-5 rounded-2xl bg-gray-900 text-white"><p class="text-[9px] font-black uppercase text-gray-400">Capital</p><p class="text-xl font-black">${{ number_format($fixedFundLedgerTotals['capital'], 2) }}</p></div>
                <div class="p-5 rounded-2xl bg-red-50 border border-red-100"><p class="text-[9px] font-black uppercase text-red-500">Salidas</p><p class="text-xl font-black text-red-700">-${{ number_format($fixedFundLedgerTotals['outflows'], 2) }}</p></div>
                <div class="p-5 rounded-2xl bg-blue-50 border border-blue-100"><p class="text-[9px] font-black uppercase text-blue-500">Repuesto</p><p class="text-xl font-black text-blue-700">+${{ number_format($fixedFundLedgerTotals['replenished'], 2) }}</p></div>
                <div class="p-5 rounded-2xl bg-amber-50 border border-amber-100"><p class="text-[9px] font-black uppercase text-amber-500">Pendiente</p><p class="text-xl font-black text-amber-700">${{ number_format($fixedFundLedgerTotals['pending_replenishment'], 2) }}</p></div>
                <div class="p-5 rounded-2xl bg-emerald-50 border border-emerald-100"><p class="text-[9px] font-black uppercase text-emerald-500">Renovaciones completas</p><p class="text-xl font-black text-emerald-700">{{ $budgetRenewalCount }}</p></div>
            </div>

            <form method="GET" class="bg-white dark:bg-gray-800 rounded-3xl border border-gray-100 dark:border-gray-700 p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-6 gap-4">
                    <div class="xl:col-span-2"><label class="block text-[9px] font-black uppercase text-gray-400 mb-2">Buscar</label><input name="search" value="{{ request('search') }}" placeholder="Concepto, folio, fondo o estado..." class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900"></div>
                    <div><label class="block text-[9px] font-black uppercase text-gray-400 mb-2">Movimiento</label><select name="direction" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900"><option value="">Todos</option><option value="in" @selected(request('direction')==='in')>Entradas</option><option value="out" @selected(request('direction')==='out')>Salidas</option></select></div>
                    <div><label class="block text-[9px] font-black uppercase text-gray-400 mb-2">Tipo</label><select name="kind" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900"><option value="">Todos</option><option value="capital" @selected(request('kind')==='capital')>Capital / renovación</option><option value="expense" @selected(request('kind')==='expense')>Gasto</option><option value="replenishment" @selected(request('kind')==='replenishment')>Reposición</option></select></div>
                    <div><label class="block text-[9px] font-black uppercase text-gray-400 mb-2">Fondo fijo</label><select name="fixed_fund_id" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900"><option value="">Todos</option>@foreach($fundSummaries as $fund)<option value="{{ $fund->id }}" @selected((string)request('fixed_fund_id')===(string)$fund->id)>{{ $fund->name }}</option>@endforeach</select></div>
                    <div class="grid grid-cols-2 gap-2"><div><label class="block text-[9px] font-black uppercase text-gray-400 mb-2">Desde</label><input type="date" name="from_date" value="{{ request('from_date') }}" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900"></div><div><label class="block text-[9px] font-black uppercase text-gray-400 mb-2">Hasta</label><input type="date" name="to_date" value="{{ request('to_date') }}" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900"></div></div>
                </div>
                <div class="flex justify-end gap-3 mt-5"><a href="{{ route('cost_centers.fixed_fund_history', $costCenter) }}" class="px-5 py-2.5 rounded-xl bg-gray-100 text-xs font-black uppercase">Limpiar</a><button class="px-5 py-2.5 rounded-xl bg-indigo-600 text-white text-xs font-black uppercase">Aplicar filtros</button></div>
            </form>

            <div class="bg-white dark:bg-gray-800 rounded-[2.5rem] border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="px-8 py-6 border-b border-gray-100 dark:border-gray-700"><p class="text-[10px] font-black uppercase text-indigo-500">Estado de cuenta</p><h3 class="text-xl font-black dark:text-white">{{ $movements->total() }} movimientos</h3></div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900/30"><tr><th class="px-6 py-4 text-left text-[9px] font-black uppercase text-gray-400">Fecha</th><th class="px-6 py-4 text-left text-[9px] font-black uppercase text-gray-400">Fondo</th><th class="px-6 py-4 text-left text-[9px] font-black uppercase text-gray-400">Concepto</th><th class="px-6 py-4 text-right text-[9px] font-black uppercase text-gray-400">Entrada</th><th class="px-6 py-4 text-right text-[9px] font-black uppercase text-gray-400">Salida</th><th class="px-6 py-4 text-left text-[9px] font-black uppercase text-gray-400">Estado</th></tr></thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse($movements as $entry)
                                <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-900/20">
                                    <td class="px-6 py-5 whitespace-nowrap"><p class="font-black dark:text-white">{{ $entry['occurred_at']->format('d/m/Y') }}</p><p class="text-[9px] text-gray-400">{{ $entry['occurred_at']->format('H:i') }}</p></td>
                                    <td class="px-6 py-5 text-xs font-black dark:text-gray-200">{{ $entry['fund_name'] }}</td>
                                    <td class="px-6 py-5 min-w-[280px]">@if($entry['reimbursement_id'])<a href="{{ route('reimbursements.show',$entry['reimbursement_id']) }}" class="font-black dark:text-white hover:text-indigo-600">{{ $entry['concept'] }}</a>@else<p class="font-black dark:text-white">{{ $entry['concept'] }}</p>@endif<p class="text-[10px] text-gray-500 mt-1">{{ $entry['detail'] }}</p></td>
                                    <td class="px-6 py-5 text-right font-black text-emerald-600">@if($entry['direction']==='in')+${{ number_format($entry['amount'],2) }}@else<span class="text-gray-300">—</span>@endif</td>
                                    <td class="px-6 py-5 text-right font-black text-red-600">@if($entry['direction']==='out')-${{ number_format($entry['amount'],2) }}@else<span class="text-gray-300">—</span>@endif</td>
                                    <td class="px-6 py-5"><span class="inline-flex px-3 py-1.5 rounded-full text-[9px] font-black uppercase {{ $entry['kind']==='replenishment' ? 'bg-blue-100 text-blue-700' : ($entry['status']==='Pendiente de reposición' ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700') }}">{{ $entry['status'] }}</span></td>
                                </tr>
                            @empty<tr><td colspan="6" class="px-8 py-20 text-center text-xs font-black uppercase text-gray-400">No hay movimientos con estos filtros</td></tr>@endforelse
                        </tbody>
                    </table>
                </div>
                @if($movements->hasPages())<div class="px-8 py-5 border-t border-gray-100 dark:border-gray-700">{{ $movements->links() }}</div>@endif
            </div>
        </div>
    </div>
</x-app-layout>
