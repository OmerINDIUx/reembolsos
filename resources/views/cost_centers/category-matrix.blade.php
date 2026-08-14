<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <nav class="flex mb-2 text-xs font-medium text-gray-500 dark:text-gray-400">
                    <a href="{{ route('cost_centers.index') }}" class="hover:text-indigo-600">Centros de Costos</a>
                    <span class="mx-2">/</span>
                    <a href="{{ route('cost_centers.show', ['cost_center' => $costCenter] + request()->only(['period_type', 'period_week', 'period_month', 'period_quarter', 'period_year'])) }}" class="hover:text-indigo-600">{{ $costCenter->code }}</a>
                    <span class="mx-2">/</span>
                    <span>Matriz de Gastos</span>
                </nav>
                <h2 class="font-black text-3xl text-gray-900 dark:text-white uppercase tracking-tighter">Matriz completa de gastos</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $costCenter->name }}</p>
            </div>
            <a href="{{ route('cost_centers.show', ['cost_center' => $costCenter] + request()->only(['period_type', 'period_week', 'period_month', 'period_quarter', 'period_year'])) }}" class="inline-flex items-center justify-center px-5 py-3 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-black uppercase tracking-widest rounded-xl transition-colors">
                &larr; Volver al panel
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            <x-time-filter-bar :action="route('cost_centers.category_matrix', $costCenter)" :periods="$periods" />

            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5">
                <div class="bg-gray-900 text-white p-6 rounded-3xl">
                    <p class="text-[9px] font-black uppercase tracking-widest text-gray-400">Gasto total</p>
                    <p class="text-3xl font-black mt-2">${{ number_format($totals->amount, 2) }}</p>
                    <p class="text-[10px] font-bold text-gray-400 mt-2">{{ $totals->count }} comprobantes · {{ $totals->categories }} categorías</p>
                </div>
                <div class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-100 dark:border-emerald-800 p-6 rounded-3xl">
                    <p class="text-[9px] font-black uppercase tracking-widest text-emerald-600">Aprobado / Pagado</p>
                    <p class="text-3xl font-black text-emerald-700 dark:text-emerald-300 mt-2">${{ number_format($totals->approved_amount, 2) }}</p>
                    <p class="text-[10px] font-bold text-emerald-600 mt-2">{{ $totals->approved_count }} comprobantes</p>
                </div>
                <div class="bg-rose-50 dark:bg-rose-900/20 border border-rose-100 dark:border-rose-800 p-6 rounded-3xl">
                    <p class="text-[9px] font-black uppercase tracking-widest text-rose-600">Rechazado</p>
                    <p class="text-3xl font-black text-rose-700 dark:text-rose-300 mt-2">${{ number_format($totals->rejected_amount, 2) }}</p>
                    <p class="text-[10px] font-bold text-rose-600 mt-2">{{ $totals->rejected_count }} comprobantes</p>
                </div>
                <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-100 dark:border-amber-800 p-6 rounded-3xl">
                    <p class="text-[9px] font-black uppercase tracking-widest text-amber-600">En proceso</p>
                    <p class="text-3xl font-black text-amber-700 dark:text-amber-300 mt-2">${{ number_format($totals->pending_amount, 2) }}</p>
                    <p class="text-[10px] font-bold text-amber-600 mt-2">{{ $totals->pending_count }} comprobantes</p>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-[2.5rem] shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="px-8 py-6 border-b border-gray-100 dark:border-gray-700">
                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-indigo-500">Detalle completo</p>
                    <h3 class="text-xl font-black text-gray-900 dark:text-white uppercase tracking-tighter mt-1">Todas las categorías</h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-gray-50/70 dark:bg-gray-900/30 border-b border-gray-100 dark:border-gray-700">
                            <tr>
                                <th class="px-8 py-4 text-left text-[9px] font-black uppercase tracking-widest text-gray-400">Categoría</th>
                                <th class="px-6 py-4 text-left text-[9px] font-black uppercase tracking-widest text-gray-400">Participación</th>
                                <th class="px-6 py-4 text-right text-[9px] font-black uppercase tracking-widest text-gray-400">Total</th>
                                <th class="px-6 py-4 text-right text-[9px] font-black uppercase tracking-widest text-gray-400">Aprobado</th>
                                <th class="px-6 py-4 text-right text-[9px] font-black uppercase tracking-widest text-gray-400">Rechazado</th>
                                <th class="px-8 py-4 text-right text-[9px] font-black uppercase tracking-widest text-gray-400">En proceso</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse($categories as $category)
                                @php $participation = $totals->amount > 0 ? ((float) $category->amount / $totals->amount) * 100 : 0; @endphp
                                <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-900/20 transition-colors">
                                    <td class="px-8 py-5">
                                        <p class="text-sm font-black text-gray-900 dark:text-white uppercase">{{ $category->category ?: 'Sin categoría' }}</p>
                                        <p class="text-[9px] font-bold uppercase tracking-widest text-gray-400">{{ $category->count }} comprobantes</p>
                                    </td>
                                    <td class="px-6 py-5 min-w-[220px]">
                                        <div class="flex items-center justify-between gap-3 mb-2">
                                            <span class="text-[10px] font-black text-indigo-600">{{ number_format($participation, 1) }}%</span>
                                        </div>
                                        <div class="w-full h-2 rounded-full bg-gray-100 dark:bg-gray-900 overflow-hidden">
                                            <div class="h-full rounded-full bg-indigo-600" style="width: {{ min($participation, 100) }}%"></div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5 text-right">
                                        <p class="text-sm font-black text-gray-900 dark:text-white">${{ number_format($category->amount, 2) }}</p>
                                    </td>
                                    <td class="px-6 py-5 text-right">
                                        <p class="text-sm font-black text-emerald-600">${{ number_format($category->approved_amount, 2) }}</p>
                                        <p class="text-[9px] font-bold text-gray-400">{{ $category->approved_count }} comprobantes</p>
                                    </td>
                                    <td class="px-6 py-5 text-right">
                                        <p class="text-sm font-black text-rose-600">${{ number_format($category->rejected_amount, 2) }}</p>
                                        <p class="text-[9px] font-bold text-gray-400">{{ $category->rejected_count }} comprobantes</p>
                                    </td>
                                    <td class="px-8 py-5 text-right">
                                        <p class="text-sm font-black text-amber-600">${{ number_format($category->pending_amount, 2) }}</p>
                                        <p class="text-[9px] font-bold text-gray-400">{{ $category->pending_count }} comprobantes</p>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-8 py-20 text-center">
                                        <p class="text-xs font-black uppercase tracking-widest text-gray-400">No hay gastos en el periodo seleccionado</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if($categories->isNotEmpty())
                            <tfoot class="bg-gray-900 text-white">
                                <tr>
                                    <td class="px-8 py-5 font-black uppercase tracking-widest text-xs">Total</td>
                                    <td class="px-6 py-5 text-xs font-black">100%</td>
                                    <td class="px-6 py-5 text-right font-black">${{ number_format($totals->amount, 2) }}</td>
                                    <td class="px-6 py-5 text-right font-black text-emerald-300">${{ number_format($totals->approved_amount, 2) }}</td>
                                    <td class="px-6 py-5 text-right font-black text-rose-300">${{ number_format($totals->rejected_amount, 2) }}</td>
                                    <td class="px-8 py-5 text-right font-black text-amber-300">${{ number_format($totals->pending_amount, 2) }}</td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>