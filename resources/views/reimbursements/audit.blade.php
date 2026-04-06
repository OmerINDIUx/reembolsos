<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center space-x-3">
            <a href="{{ route('reimbursements.index', ['tab' => 'management']) }}" class="p-2 rounded-xl bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div>
                <p class="text-[10px] font-black text-indigo-500 uppercase tracking-widest italic opacity-70">Módulo de Gestión</p>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Auditoría de Reembolsos</h2>
            </div>
        </div>
    </x-slot>

    <style>
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if($auditItems && $auditMeta)
                {{-- ===== VISTA DETALLE: tabla de auditoría ===== --}}
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-3xl border border-gray-100 dark:border-gray-700">

                    {{-- Header de Auditoría --}}
                    <div class="p-8 pb-4 border-b border-gray-100 dark:border-gray-700 bg-indigo-50/50 dark:bg-indigo-900/10">
                        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                            <div class="space-y-1">
                                <div class="flex items-center space-x-2">
                                    <span class="text-[9px] font-black text-indigo-600 dark:text-indigo-400 uppercase tracking-widest italic opacity-60">Auditoría de Reembolsos</span>
                                    <div class="h-px w-6 bg-indigo-200 dark:bg-indigo-700"></div>
                                </div>
                                <h3 class="text-2xl font-black text-gray-900 dark:text-gray-100 uppercase tracking-tight leading-none">
                                    {{ $auditMeta['cc_name'] }}
                                </h3>
                                <div class="flex items-center space-x-2 pt-1">
                                    <span class="px-2 py-0.5 bg-indigo-600 text-white rounded text-[8px] font-black uppercase tracking-widest">Semana {{ $auditMeta['week'] }}</span>
                                    <span class="px-2 py-0.5 bg-white dark:bg-gray-800 text-gray-500 dark:text-gray-400 rounded text-[8px] font-black uppercase tracking-widest border border-gray-200 dark:border-gray-600">{{ ucfirst(str_replace('_', ' ', $auditMeta['type'])) }}</span>
                                </div>
                            </div>
                            <div class="flex items-center space-x-4">
                                <a href="{{ route('reimbursements.audit', ['week' => $selectedWeek]) }}" class="text-[10px] font-black text-indigo-600 hover:underline uppercase tracking-widest">Ver toda la semana</a>
                            </div>
                        </div>
                    </div>

                    {{-- Paneles de Auditoría (Dashboard) --}}
                    @if($auditStats)
                    <div class="p-6 md:p-8 grid grid-cols-1 md:grid-cols-4 gap-6 bg-gray-50 dark:bg-gray-900/20">
                        <!-- Card 1: Total -->
                        <div class="bg-white dark:bg-gray-800 p-6 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm relative overflow-hidden group">
                             <div class="relative z-10">
                                <span class="text-[10px] text-gray-400 font-black uppercase tracking-widest mb-1 block">Monto Auditado</span>
                                <span class="text-3xl font-black text-indigo-700 dark:text-indigo-400 tracking-tighter leading-none">${{ number_format($auditStats['total'], 2) }}</span>
                                <p class="text-[9px] text-gray-400 font-bold mt-2 uppercase tracking-tight italic">{{ $auditStats['count'] }} Comprobantes</p>
                             </div>
                             <div class="absolute -right-2 -bottom-2 opacity-5 group-hover:scale-110 transition-transform">
                                <svg class="w-20 h-20 text-indigo-600" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
                             </div>
                        </div>

                        <!-- Card 2: Validaciones -->
                        <div class="bg-white dark:bg-gray-800 p-6 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm relative overflow-hidden group">
                             @php $valPercent = $auditStats['count'] > 0 ? ($auditStats['validation_passed'] / $auditStats['count']) * 100 : 0; @endphp
                             <div class="relative z-10">
                                <span class="text-[10px] text-gray-400 font-black uppercase tracking-widest mb-1 block">Validación SAT/PDF</span>
                                <div class="flex items-baseline space-x-1">
                                    <span class="text-3xl font-black {{ $valPercent < 80 ? 'text-amber-500' : 'text-emerald-500' }} tracking-tighter leading-none">{{ number_format($valPercent, 1) }}%</span>
                                    <span class="text-[9px] font-black text-gray-400 uppercase">éxito</span>
                                </div>
                                <div class="w-full bg-gray-100 dark:bg-gray-700 h-1 rounded-full mt-3 overflow-hidden">
                                    <div class="h-full {{ $valPercent < 80 ? 'bg-amber-500' : 'bg-emerald-500' }} transition-all" style="width: {{ $valPercent }}%"></div>
                                </div>
                             </div>
                             <div class="absolute -right-2 -bottom-2 opacity-5 group-hover:scale-110 transition-transform">
                                <svg class="w-20 h-20 text-emerald-600" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14.5l-4-4 1.41-1.41L11 13.67l7.09-7.09L19.5 8 11 16.5z"/></svg>
                             </div>
                        </div>

                        <!-- Card 3: Estatus Mix -->
                        <div class="bg-white dark:bg-gray-800 p-6 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm relative overflow-hidden group">
                             <div class="relative z-10">
                                <span class="text-[10px] text-gray-400 font-black uppercase tracking-widest mb-1 block">Estatus de Solicitudes</span>
                                <div class="space-y-1.5 pt-1">
                                    @foreach($auditStats['status_counts'] as $status => $count)
                                        @if($loop->index < 4)
                                        <div class="flex justify-between items-center text-[9px]">
                                            <span class="font-black uppercase text-gray-500 truncate w-24 italic">{{ str_replace('_', ' ', $status) }}</span>
                                            <span class="font-black text-gray-900 dark:text-white">{{ $count }}</span>
                                        </div>
                                        @endif
                                    @endforeach
                                </div>
                             </div>
                        </div>

                        <!-- Card 4: Categorías -->
                        <div class="bg-white dark:bg-gray-800 p-6 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm relative overflow-hidden group">
                             <div class="relative z-10">
                                <span class="text-[10px] text-gray-400 font-black uppercase tracking-widest mb-1 block">Distribución de Gasto</span>
                                <div class="space-y-1.5 pt-1">
                                    @foreach($auditStats['category_totals'] as $category => $amount)
                                        @if($loop->index < 3)
                                        <div class="flex justify-between items-center text-[9px]">
                                            <span class="font-black uppercase text-gray-500 truncate w-20">{{ $category }}</span>
                                            <span class="font-black text-gray-900 dark:text-white">${{ number_format($amount, 0) }}</span>
                                        </div>
                                        @endif
                                    @endforeach
                                    @if(count($auditStats['category_totals']) > 3)
                                        <div class="text-right text-[8px] font-black text-indigo-500 uppercase italic">+ otros</div>
                                    @endif
                                </div>
                             </div>
                        </div>
                    </div>
                    @endif


                    {{-- Tabla de detalle --}}
                    <div class="p-6 md:p-8">
                        <div class="rounded-[1.5rem] border border-gray-100 dark:border-gray-700 overflow-hidden bg-white dark:bg-gray-900/30">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50/50 dark:bg-gray-800/80">
                                    <tr>
                                        <th class="px-6 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest">Identificador</th>
                                        <th class="px-6 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest">Fecha</th>
                                        <th class="px-6 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest">Emisor / Solicitante</th>
                                        <th class="px-6 py-4 text-center text-[10px] font-black text-gray-400 uppercase tracking-widest">Estatus</th>
                                        <th class="px-6 py-4 text-right text-[10px] font-black text-gray-400 uppercase tracking-widest">Monto Neto</th>
                                        <th class="px-6 py-4"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                    @forelse($auditItems as $r)
                                        <tr class="hover:bg-gray-50/50 dark:hover:bg-indigo-900/5 transition-colors">
                                            <td class="px-6 py-5">
                                                <div class="flex flex-col">
                                                    <span class="text-xs font-black text-gray-800 dark:text-gray-100">{{ $r->folio }}</span>
                                                    <span class="text-[9px] text-gray-400 font-medium truncate max-w-[130px]">{{ $r->uuid }}</span>
                                                </div>
                                            </td>
                                            <td class="px-6 py-5">
                                                <span class="text-[11px] font-bold text-gray-500 whitespace-nowrap italic">
                                                    {{ $r->fecha ? \Carbon\Carbon::parse($r->fecha)->format('d/m/Y') : 'S/F' }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-5">
                                                <div class="flex flex-col">
                                                    <span class="text-[11px] font-black text-gray-700 dark:text-gray-300 truncate max-w-[220px]">{{ $r->nombre_emisor }}</span>
                                                    <span class="text-[10px] text-indigo-500 font-bold opacity-60 uppercase">{{ $r->user->name ?? 'N/A' }}</span>
                                                </div>
                                            </td>
                                            <td class="px-6 py-5 text-center">
                                                <span class="px-2 py-0.5 text-[9px] font-black rounded-full uppercase leading-none
                                                    {{ $r->status === 'aprobado' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : '' }}
                                                    {{ str_contains($r->status, 'aprobado_') ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300' : '' }}
                                                    {{ $r->status === 'pendiente' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300' : '' }}
                                                    {{ $r->status === 'rechazado' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300' : '' }}
                                                ">
                                                    {{ str_replace('_', ' ', $r->status) }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-5 text-right font-mono font-black text-sm text-gray-900 dark:text-gray-100">
                                                ${{ number_format($r->total, 2) }}
                                            </td>
                                            <td class="px-6 py-5 text-right">
                                                <a href="{{ route('reimbursements.show', $r->id) }}"
                                                   class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-[10px] font-black uppercase tracking-widest rounded-xl transition-all shadow-md shadow-indigo-200 dark:shadow-none hover:-translate-y-0.5">
                                                    Audit. Gasto
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="px-6 py-16 text-center text-sm text-gray-400 font-bold italic">
                                                Sin reembolsos para este grupo.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            @else
                {{-- ===== PANTALLA 2: Desglose de Centros de Costo por Semana ===== --}}
                <div class="bg-gray-50 dark:bg-gray-900/40 rounded-[2.5rem] p-4 md:p-8 border border-gray-100 dark:border-gray-800">
                    
                    @if($selectedWeek)
                        @php 
                            $weekItems = $groupedByWeek->get($selectedWeek, collect());
                            $displayItems = $selectedCcName ? $weekItems->filter(fn($r) => ($r->costCenter->name ?? 'Sin Centro de Costos') === $selectedCcName) : $weekItems;
                        @endphp
                        <div class="mb-10 p-2">
                             <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-6 mb-8">
                                <div>
                                    <h3 class="text-xs font-black text-indigo-600 dark:text-indigo-400 uppercase tracking-[0.2em] mb-2 italic">
                                        {{ $selectedCcName ? 'Proyecto Seleccionado' : 'Semana Seleccionada' }}
                                    </h3>
                                    <div class="flex flex-wrap items-center gap-4">
                                        <h1 class="text-5xl font-black text-gray-900 dark:text-white tracking-tighter">
                                            {{ $selectedCcName ?? "Semana $selectedWeek" }}
                                        </h1>
                                        @if($selectedCcName)
                                            <span class="px-4 py-1.5 bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300 rounded-full text-[10px] font-black uppercase tracking-widest border border-indigo-200 dark:border-indigo-800">Semana {{ $selectedWeek }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <a href="{{ route('reimbursements.index', ['tab' => 'management']) }}" class="bg-white dark:bg-gray-800 px-4 py-2 border border-gray-200 dark:border-gray-700 rounded-xl text-[10px] font-black uppercase tracking-widest text-gray-500 hover:text-indigo-600 transition-colors">Volver a gestión</a>
                                </div>
                             </div>

                             {{-- Summary Panels --}}
                             @if($auditStats)
                             <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div class="bg-white dark:bg-gray-800 p-5 rounded-[2rem] border border-gray-100 dark:border-gray-700 shadow-sm flex flex-col">
                                    <span class="text-[9px] text-gray-400 font-black uppercase mb-1">Monto de la Semana</span>
                                    <span class="text-3xl font-black text-gray-900 dark:text-white">${{ number_format($auditStats['total'], 2) }}</span>
                                </div>
                                <div class="bg-white dark:bg-gray-800 p-5 rounded-[2rem] border border-gray-100 dark:border-gray-700 shadow-sm flex flex-col">
                                    <span class="text-[9px] text-gray-400 font-black uppercase mb-1">Comprobantes</span>
                                    <span class="text-3xl font-black text-gray-900 dark:text-white">{{ $auditStats['count'] }}</span>
                                </div>
                                <div class="bg-indigo-600 p-5 rounded-[2rem] shadow-xl shadow-indigo-200 dark:shadow-none flex flex-col text-white">
                                    <span class="text-[9px] text-indigo-200 font-black uppercase mb-1">Promedio / Gasto</span>
                                    <span class="text-3xl font-black">${{ number_format($auditStats['avg'], 2) }}</span>
                                </div>
                                <div class="bg-white dark:bg-gray-800 p-5 rounded-[2rem] border border-gray-100 dark:border-gray-700 shadow-sm flex flex-col">
                                    <span class="text-[9px] text-emerald-500 font-black uppercase mb-1">Fidelidad de Datos</span>
                                    @php $valPercent = $auditStats['count'] > 0 ? ($auditStats['validation_passed'] / $auditStats['count']) * 100 : 0; @endphp
                                    <span class="text-3xl font-black {{ $valPercent < 80 ? 'text-amber-500' : 'text-emerald-500' }}">{{ number_format($valPercent, 0) }}%</span>
                                </div>
                             </div>
                             @endif
                        </div>

                        @php 
                            $groupedByCC = $displayItems->groupBy(function($item) {
                                return $item->costCenter->name ?? 'Sin Centro de Costos';
                            });
                        @endphp

                        @forelse($groupedByCC as $ccName => $ccItems)
                            <div class="bg-white dark:bg-gray-800 rounded-[2rem] shadow-sm border border-gray-100 dark:border-gray-700 p-8 mb-6 last:mb-0 group hover:shadow-lg transition-all duration-300">
                                <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 pb-6 border-b border-gray-50 dark:border-gray-700">
                                    <div>
                                        <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 italic opacity-60">Centro de Costos / Proyecto</h4>
                                        <h2 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight leading-none">
                                            {{ $ccName }}
                                        </h2>
                                    </div>
                                    <div class="mt-4 md:mt-0 text-right">
                                        <span class="text-[10px] text-gray-400 font-black uppercase tracking-widest block mb-1">Subtotal Proyecto</span>
                                        <span class="text-2xl font-black text-gray-900 dark:text-white">${{ number_format($ccItems->sum('total'), 2) }}</span>
                                    </div>
                                </div>

                                <div class="space-y-3">
                                    @php $groupedByType = $ccItems->groupBy('type'); @endphp
                                    @foreach($groupedByType as $type => $typeItems)
                                        <a href="{{ route('reimbursements.audit', ['week' => $selectedWeek, 'cc' => $ccName, 'type' => $type]) }}"
                                           class="flex items-center justify-between p-5 bg-gray-50 dark:bg-gray-900/40 hover:bg-white dark:hover:bg-gray-800 rounded-2xl border border-transparent hover:border-indigo-100 dark:hover:border-indigo-900 hover:shadow-lg transition-all group/type no-underline">
                                            <div class="flex items-center space-x-5">
                                                <div class="w-10 h-10 bg-white dark:bg-gray-800 rounded-xl flex items-center justify-center shadow-sm border border-gray-100 dark:border-gray-700 group-hover/type:bg-indigo-600 transition-colors">
                                                    <svg class="w-5 h-5 text-indigo-500 group-hover/type:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                                </div>
                                                <div>
                                                    <span class="text-[10px] font-black uppercase tracking-widest text-gray-400 group-hover/type:text-indigo-400 mb-0.5 block italic">{{ ucfirst(str_replace('_', ' ', $type)) }}</span>
                                                    <span class="text-sm font-black text-gray-700 dark:text-gray-300">{{ $typeItems->count() }} Comprobantes</span>
                                                </div>
                                            </div>
                                            <div class="flex items-center space-x-8">
                                                <div class="text-right">
                                                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-0.5">Total Tipo</span>
                                                    <span class="text-xl font-black text-gray-900 dark:text-white">${{ number_format($typeItems->sum('total'), 2) }}</span>
                                                </div>
                                                <div class="w-8 h-8 flex items-center justify-center rounded-full bg-gray-100 dark:bg-gray-700 group-hover/type:bg-indigo-100 dark:group-hover/type:bg-indigo-900 transition-colors">
                                                    <svg class="w-4 h-4 text-gray-400 group-hover/type:text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                                                </div>
                                            </div>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @empty
                            <div class="p-20 text-center bg-white dark:bg-gray-800 rounded-3xl border border-gray-100 dark:border-gray-700">
                                <p class="text-gray-400 font-black uppercase tracking-widest text-sm">No hay reportes registrados.</p>
                                <a href="{{ route('reimbursements.index', ['tab' => 'management']) }}" class="mt-6 inline-flex items-center text-indigo-600 font-bold hover:underline">
                                    ← Volver al Módulo de Gestión
                                </a>
                            </div>
                        @endforelse

                    @else
                        <div class="p-20 text-center">
                            <h3 class="text-xl font-black text-gray-400 uppercase tracking-widest">Favor de seleccionar una semana fiscal para auditar</h3>
                            <a href="{{ route('reimbursements.index', ['tab' => 'management']) }}" class="mt-6 inline-flex items-center px-6 py-3 bg-indigo-600 text-white rounded-xl font-black uppercase tracking-widest text-xs">
                                ← Ver todas las semanas
                            </a>
                        </div>
                    @endif

                </div>
            @endif

        </div>
    </div>
</x-app-layout>
