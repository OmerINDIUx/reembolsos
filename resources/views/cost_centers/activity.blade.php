<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <nav class="flex mb-2 text-xs font-medium text-gray-500 dark:text-gray-400">
                    <a href="{{ route('cost_centers.index') }}" class="hover:text-indigo-600">Centros de Costos</a><span class="mx-2">/</span>
                    <a href="{{ route('cost_centers.show', $costCenter) }}" class="hover:text-indigo-600">{{ $costCenter->code }}</a><span class="mx-2">/</span>
                    <span>Actividad</span>
                </nav>
                <h2 class="font-black text-3xl text-gray-900 dark:text-white uppercase tracking-tighter">Actividad completa</h2>
                <p class="text-sm text-gray-500 mt-1">{{ $costCenter->name }}</p>
            </div>
            <a href="{{ route('cost_centers.show', $costCenter) }}" class="inline-flex items-center justify-center px-5 py-3 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-black uppercase tracking-widest rounded-xl">&larr; Volver al panel</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <form method="GET" class="bg-white dark:bg-gray-800 rounded-3xl border border-gray-100 dark:border-gray-700 p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-6 gap-4">
                    <div class="xl:col-span-2">
                        <label class="block text-[9px] font-black uppercase tracking-widest text-gray-400 mb-2">Buscar</label>
                        <input name="search" value="{{ request('search') }}" placeholder="Folio, proveedor, categoría o persona..." class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900">
                    </div>
                    <div>
                        <label class="block text-[9px] font-black uppercase tracking-widest text-gray-400 mb-2">Estatus</label>
                        <select name="status" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900">
                            <option value="">Todos</option>
                            @foreach($statusOptions as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ ucwords(str_replace('_', ' ', $status)) }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[9px] font-black uppercase tracking-widest text-gray-400 mb-2">Fondo fijo</label>
                        <select name="fixed_fund_id" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900">
                            <option value="">Todos</option>
                            @foreach($costCenter->fixedFunds as $fund)<option value="{{ $fund->id }}" @selected((string) request('fixed_fund_id') === (string) $fund->id)>{{ $fund->name }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[9px] font-black uppercase tracking-widest text-gray-400 mb-2">Categoría</label>
                        <select name="category" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900">
                            <option value="">Todas</option>
                            @foreach($categoryOptions as $category)<option value="{{ $category }}" @selected(request('category') === $category)>{{ $category }}</option>@endforeach
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div><label class="block text-[9px] font-black uppercase text-gray-400 mb-2">Desde</label><input type="date" name="from_date" value="{{ request('from_date') }}" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900"></div>
                        <div><label class="block text-[9px] font-black uppercase text-gray-400 mb-2">Hasta</label><input type="date" name="to_date" value="{{ request('to_date') }}" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900"></div>
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-5">
                    <a href="{{ route('cost_centers.activity', $costCenter) }}" class="px-5 py-2.5 rounded-xl bg-gray-100 dark:bg-gray-700 text-xs font-black uppercase text-gray-600 dark:text-gray-300">Limpiar</a>
                    <button class="px-5 py-2.5 rounded-xl bg-indigo-600 text-white text-xs font-black uppercase">Aplicar filtros</button>
                </div>
            </form>

            <div class="bg-white dark:bg-gray-800 rounded-[2.5rem] border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="px-8 py-6 border-b border-gray-100 dark:border-gray-700 flex justify-between">
                    <div><p class="text-[10px] font-black uppercase tracking-widest text-indigo-500">Resultados</p><h3 class="text-xl font-black dark:text-white">{{ $activities->total() }} movimientos</h3></div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900/30">
                            <tr>
                                <th class="px-6 py-4 text-left text-[9px] font-black uppercase text-gray-400">Fecha / Folio</th>
                                <th class="px-6 py-4 text-left text-[9px] font-black uppercase text-gray-400">Concepto</th>
                                <th class="px-6 py-4 text-left text-[9px] font-black uppercase text-gray-400">Responsable / Fondo</th>
                                <th class="px-6 py-4 text-right text-[9px] font-black uppercase text-gray-400">Importe</th>
                                <th class="px-6 py-4 text-left text-[9px] font-black uppercase text-gray-400">Semáforo</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse($activities as $item)
                                @php
                                    $isPaymentApproved = $item->status === 'pendiente_pago' && $item->approved_by_treasury_at;
                                    $traffic = match (true) {
                                        in_array($item->status, ['aprobado', 'pagado'], true) || $isPaymentApproved => ['bg-emerald-500', 'bg-emerald-100 text-emerald-700', $isPaymentApproved ? 'Aprobado para pago' : ($item->status === 'pagado' ? 'Pagado' : 'Aprobado')],
                                        in_array($item->status, ['rechazado', 'requiere_correccion'], true) => ['bg-red-500', 'bg-red-100 text-red-700', $item->status === 'rechazado' ? 'Rechazado' : 'Requiere corrección'],
                                        default => ['bg-amber-500', 'bg-amber-100 text-amber-700', ucwords(str_replace('_', ' ', $item->status))],
                                    };
                                @endphp
                                <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-900/20 cursor-pointer" onclick="window.location='{{ route('reimbursements.show', $item) }}'">
                                    <td class="px-6 py-5 whitespace-nowrap"><p class="font-black dark:text-white">{{ $item->folio ?: '#'.$item->id }}</p><p class="text-[9px] text-gray-400">{{ $item->created_at->format('d/m/Y H:i') }}</p></td>
                                    <td class="px-6 py-5"><p class="font-black dark:text-white">{{ $item->title ?: $item->nombre_emisor ?: $item->category ?: 'Reembolso' }}</p><p class="text-[10px] text-gray-500">{{ $item->category ?: ucfirst(str_replace('_', ' ', $item->type)) }}</p></td>
                                    <td class="px-6 py-5"><p class="text-xs font-bold dark:text-gray-200">{{ $item->payee?->name ?? $item->user?->name ?? 'Sin responsable' }}</p><p class="text-[9px] uppercase text-gray-400">{{ $item->fixedFund?->name ?? 'Sin fondo fijo' }}</p></td>
                                    <td class="px-6 py-5 text-right font-black dark:text-white">${{ number_format((float)$item->total + (float)($item->propina ?? 0), 2) }}</td>
                                    <td class="px-6 py-5"><span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-[9px] font-black uppercase {{ $traffic[1] }}"><span class="w-2.5 h-2.5 rounded-full {{ $traffic[0] }}"></span>{{ $traffic[2] }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-8 py-20 text-center text-xs font-black uppercase text-gray-400">No hay actividad con estos filtros</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($activities->hasPages())<div class="px-8 py-5 border-t border-gray-100 dark:border-gray-700">{{ $activities->links() }}</div>@endif
            </div>
        </div>
    </div>
</x-app-layout>
