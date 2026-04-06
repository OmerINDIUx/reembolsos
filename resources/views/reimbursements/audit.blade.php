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
                                <div class="flex items-center space-x-3 pt-1">
                                    <span class="px-2 py-0.5 bg-indigo-600 text-white rounded text-[8px] font-black uppercase tracking-widest">Semana {{ $auditMeta['week'] }}</span>
                                    <span class="px-2 py-0.5 bg-white dark:bg-gray-800 text-gray-500 dark:text-gray-400 rounded text-[8px] font-black uppercase tracking-widest border border-gray-200 dark:border-gray-600">{{ ucfirst(str_replace('_', ' ', $auditMeta['type'])) }}</span>
                                    @if(isset($auditItems) && $auditItems->count() > 0)
                                        @php 
                                            $ccModel = $auditItems->first()->costCenter;
                                            $reportId = ($ccModel->abbreviation ?? 'SCC') . '-' . $auditMeta['week'] . '-AUD';
                                            $solicitors = $auditItems->pluck('user.name')->unique();
                                        @endphp
                                        <span class="px-2 py-0.5 bg-amber-100 text-amber-700 rounded text-[8px] font-black uppercase tracking-widest border border-amber-200">ID: {{ $reportId }}</span>
                                    @endif
                                </div>
                                @if(isset($solicitors) && $solicitors->count() > 0)
                                    <p class="text-[10px] text-gray-500 font-bold mt-2 italic opacity-80">
                                        Solicitantes: {{ $solicitors->take(3)->implode(', ') }}{{ $solicitors->count() > 3 ? ' y ' . ($solicitors->count() - 3) . ' más' : '' }}
                                    </p>
                                @endif
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

                        <!-- Card 3: Gasto Promedio -->
                        <div class="bg-white dark:bg-gray-800 p-6 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm relative overflow-hidden group">
                             <div class="relative z-10">
                                <span class="text-[10px] text-gray-400 font-black uppercase tracking-widest mb-1 block">Gasto Promedio</span>
                                <span class="text-3xl font-black text-blue-600 dark:text-blue-400 tracking-tighter leading-none">${{ number_format($auditStats['avg'], 2) }}</span>
                                <p class="text-[9px] text-gray-400 font-bold mt-2 uppercase tracking-tight italic">Por comprobante</p>
                             </div>
                             <div class="absolute -right-2 -bottom-2 opacity-5 group-hover:scale-110 transition-transform">
                                <svg class="w-20 h-20 text-blue-600" fill="currentColor" viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"/></svg>
                             </div>
                        </div>

                        <!-- Card 4: Top Solicitante -->
                        <div class="bg-white dark:bg-gray-800 p-6 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm relative overflow-hidden group">
                             <div class="relative z-10">
                                <span class="text-[10px] text-gray-400 font-black uppercase tracking-widest mb-1 block">Top Solicitante</span>
                                @if(isset($auditStats['top_solicitor']))
                                    <span class="text-xl font-black text-gray-900 dark:text-white truncate block w-full tracking-tight">{{ $auditStats['top_solicitor']['user'] }}</span>
                                    <p class="text-[9px] text-indigo-500 font-black mt-2 uppercase tracking-tight italic">${{ number_format($auditStats['top_solicitor']['total'], 2) }} gastados</p>
                                @else
                                    <span class="text-xl font-black text-gray-400">N/A</span>
                                @endif
                             </div>
                             <div class="absolute -right-2 -bottom-2 opacity-5 group-hover:scale-110 transition-transform">
                                <svg class="w-20 h-20 text-indigo-600" fill="currentColor" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                             </div>
                        </div>
                    </div>

                    {{-- Segunda Fila de Metricas --}}
                    <div class="px-6 md:px-8 pb-8 grid grid-cols-1 md:grid-cols-2 gap-6 bg-gray-50 dark:bg-gray-900/20">
                        <!-- Card 5: Estatus Mix -->
                        <div class="bg-white dark:bg-gray-800 p-6 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm relative overflow-hidden group">
                             <div class="relative z-10">
                                <span class="text-[10px] text-gray-400 font-black uppercase tracking-widest mb-1 block">Estatus de Solicitudes</span>
                                <div class="grid grid-cols-2 gap-2 pt-1">
                                    @foreach($auditStats['status_counts'] as $status => $count)
                                        <div class="flex justify-between items-center text-[9px] bg-gray-50 dark:bg-gray-700/50 p-2 rounded-lg border border-gray-100 dark:border-gray-600">
                                            <span class="font-black uppercase text-gray-500 truncate italic">{{ str_replace('_', ' ', $status) }}</span>
                                            <span class="font-black text-indigo-600 dark:text-indigo-400">{{ $count }}</span>
                                        </div>
                                    @endforeach
                                </div>
                             </div>
                        </div>

                        <!-- Card 6: Categorías -->
                        <div class="bg-white dark:bg-gray-800 p-6 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm relative overflow-hidden group">
                             <div class="relative z-10">
                                <span class="text-[10px] text-gray-400 font-black uppercase tracking-widest mb-1 block">Distribución de Gasto</span>
                                <div class="grid grid-cols-2 gap-2 pt-1">
                                    @foreach($auditStats['category_totals'] as $category => $amount)
                                        <div class="flex justify-between items-center text-[9px] bg-gray-50 dark:bg-gray-700/50 p-2 rounded-lg border border-gray-100 dark:border-gray-600">
                                            <span class="font-black uppercase text-gray-500 truncate">{{ $category }}</span>
                                            <span class="font-black text-indigo-600 dark:text-indigo-400">${{ number_format($amount, 0) }}</span>
                                        </div>
                                    @endforeach
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
                                        <th class="px-4 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest">ID Auditoría</th>
                                        <th class="px-4 py-4 text-center text-[10px] font-black text-gray-400 uppercase tracking-widest">Validación</th>
                                        <th class="px-4 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest">Emisor / Solicitante</th>
                                        <th class="px-4 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Método / Uso</th>
                                        <th class="px-4 py-4 text-center text-[10px] font-black text-gray-400 uppercase tracking-widest">Forma</th>
                                        <th class="px-4 py-4 text-right text-[10px] font-black text-gray-400 uppercase tracking-widest">Monto Neto</th>
                                        <th class="px-4 py-4"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                    @forelse($auditItems as $r)
                                        @php
                                            $typeAbbr = strtoupper(substr($r->type, 0, 3));
                                            $ccAbbr = $r->costCenter->abbreviation ?? 'SCC';
                                            $year = $r->fecha ? $r->fecha->format('Y') : date('Y');
                                            $compositeId = "{$ccAbbr}-{$typeAbbr}-{$year}-{$r->week}-" . str_pad($r->id, 3, '0', STR_PAD_LEFT);
                                            
                                            $val = $r->validation_data ?? [];
                                            $uuidMatch = $val['uuid_match'] ?? true; 
                                            $totalMatch = $val['total_match'] ?? true;
                                        @endphp
                                        <tr class="hover:bg-gray-50/50 dark:hover:bg-indigo-900/5 transition-colors border-b border-gray-50 dark:border-gray-800">
                                            <td class="px-4 py-4">
                                                <div class="flex flex-col">
                                                    <span class="text-[10px] font-black text-indigo-600 dark:text-indigo-400 italic mb-0.5 tracking-tight">{{ $compositeId }}</span>
                                                    <span class="text-[11px] font-bold text-gray-800 dark:text-gray-100">{{ $r->fecha ? $r->fecha->format('d/m/Y') : 'S/F' }}</span>
                                                    <span class="text-[9px] text-gray-400 font-mono font-medium uppercase tracking-tighter">{{ $r->uuid ?? 'SIN UUID' }}</span>
                                                </div>
                                            </td>
                                            <td class="px-4 py-4 text-center">
                                                <div class="flex flex-col items-center justify-center space-y-1">
                                                    <div class="flex items-center space-x-1.5">
                                                        <span class="text-[8px] font-black uppercase text-gray-400">UUID</span>
                                                        @if($r->uuid)
                                                            <svg class="w-3.5 h-3.5 {{ $uuidMatch ? 'text-emerald-500' : 'text-red-500' }}" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                                            </svg>
                                                        @else
                                                            <span class="text-[8px] font-black text-amber-500">N/A</span>
                                                        @endif
                                                    </div>
                                                    <div class="flex items-center space-x-1.5">
                                                        <span class="text-[8px] font-black uppercase text-gray-400">Total</span>
                                                        <svg class="w-3.5 h-3.5 {{ $totalMatch ? 'text-emerald-500' : 'text-red-500' }}" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                                        </svg>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-4 py-4">
                                                <div class="flex flex-col">
                                                    <span class="text-[11px] font-black text-gray-700 dark:text-gray-200 truncate max-w-[180px]">{{ $r->nombre_emisor }}</span>
                                                    <div class="flex items-center space-x-1 mt-0.5">
                                                        <span class="w-1.5 h-1.5 rounded-full 
                                                            {{ $r->status === 'aprobado' ? 'bg-green-500' : '' }}
                                                            {{ str_contains($r->status, 'aprobado_') ? 'bg-blue-500' : '' }}
                                                            {{ $r->status === 'pendiente' ? 'bg-amber-500' : '' }}
                                                            {{ $r->status === 'rechazado' ? 'bg-red-500' : '' }}
                                                        "></span>
                                                        <span class="text-[9px] text-gray-400 font-bold uppercase truncate max-w-[120px]">{{ $r->user->name ?? 'N/A' }}</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-4 py-4">
                                                <div class="flex flex-col space-y-1">
                                                    <div class="flex items-center space-x-1.5">
                                                        <span class="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 rounded text-[8px] font-black border border-gray-200 dark:border-gray-700 uppercase">{{ $r->metodo_pago ?? 'N/A' }}</span>
                                                        <span class="text-[9px] font-bold text-gray-400 italic">Pago</span>
                                                    </div>
                                                    <div class="flex items-center space-x-1.5">
                                                        <span class="px-1.5 py-0.5 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded text-[8px] font-black border border-indigo-100 dark:border-indigo-800 uppercase">{{ $r->uso_cfdi ?? 'N/A' }}</span>
                                                        <span class="text-[9px] font-bold text-gray-400 italic">Uso</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-4 py-4 text-center">
                                                <span class="px-2 py-1 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 rounded-lg text-[10px] font-black border border-gray-200 dark:border-gray-700 shadow-sm">{{ $r->forma_pago ?? '--' }}</span>
                                            </td>
                                            <td class="px-4 py-4 text-right">
                                                <div class="flex flex-col items-end">
                                                    <span class="font-mono font-black text-sm text-gray-900 dark:text-gray-100">${{ number_format($r->total, 2) }}</span>
                                                    <span class="text-[8px] font-black text-indigo-500 uppercase tracking-widest italic opacity-60">Base: ${{ number_format($r->subtotal, 2) }}</span>
                                                </div>
                                            </td>
                                            <td class="px-4 py-4 text-right">
                                                <a href="{{ route('reimbursements.show', $r->id) }}"
                                                   class="p-2 text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded-lg transition-colors block">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
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
                                        <div class="flex flex-col">
                                            @php
                                                $cc = $ccItems->first()->costCenter;
                                                $internalId = ($cc->abbreviation ?? 'SCC') . '-' . $selectedWeek;
                                                $invoices = $ccItems->whereNotNull('uuid')->count();
                                                $tickets = $ccItems->where('folio', 'SIN-FACTURA')->count();
                                            @endphp
                                            <span class="text-[10px] font-black text-indigo-500 uppercase tracking-widest italic opacity-70 leading-none mb-1">{{ $internalId }}</span>
                                            <h2 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight leading-none">
                                                {{ $ccName }}
                                            </h2>
                                        </div>
                                    </div>
                                    <div class="mt-4 md:mt-0 text-right">
                                        <span class="text-[10px] text-gray-400 font-black uppercase tracking-widest block mb-1">Subtotal Proyecto</span>
                                        <span class="text-2xl font-black text-gray-900 dark:text-white">${{ number_format($ccItems->sum('total'), 2) }}</span>
                                    </div>
                                </div>

                                <div class="space-y-3">
                                    @php $groupedByType = $ccItems->groupBy('type'); @endphp
                                    @foreach($groupedByType as $type => $typeItems)
                                        @php
                                            $cc = $typeItems->first()->costCenter;
                                            $typeInitials = strtoupper(substr($type, 0, 3));
                                            $batchId = ($cc->abbreviation ?? 'SCC') . '-' . $selectedWeek . '-' . $typeInitials;
                                            
                                            $invoices = $typeItems->whereNotNull('uuid')->count();
                                            $tickets = $typeItems->where('folio', 'SIN-FACTURA')->count();
                                            
                                            $mainSolicitor = $typeItems->groupBy('user_id')
                                                ->map(fn($group) => ['name' => $group->first()->user->name ?? 'N/A', 'total' => $group->sum('total')])
                                                ->sortByDesc('total')
                                                ->first();
                                        @endphp
                                        <a href="{{ route('reimbursements.audit', ['week' => $selectedWeek, 'cc' => $ccName, 'type' => $type]) }}"
                                           class="flex flex-col md:flex-row items-center justify-between p-5 bg-gray-50 dark:bg-gray-900/40 hover:bg-white dark:hover:bg-gray-800 rounded-2xl border border-transparent hover:border-indigo-100 dark:hover:border-indigo-900 hover:shadow-lg transition-all group/type no-underline space-y-4 md:space-y-0">
                                            <div class="flex items-center space-x-5">
                                                <div class="w-10 h-10 bg-white dark:bg-gray-800 rounded-xl flex items-center justify-center shadow-sm border border-gray-100 dark:border-gray-700 group-hover/type:bg-indigo-600 transition-colors">
                                                    <svg class="w-5 h-5 text-indigo-500 group-hover/type:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                                </div>
                                                <div>
                                                    <span class="text-[9px] font-black uppercase tracking-widest text-indigo-500 group-hover/type:text-indigo-400 mb-0.5 block italic">{{ $batchId }}</span>
                                                    <span class="text-sm font-black text-gray-700 dark:text-gray-300">{{ ucfirst(str_replace('_', ' ', $type)) }}</span>
                                                </div>
                                            </div>

                                            <div class="flex flex-wrap items-center gap-6 md:gap-12">
                                                <div class="flex flex-col items-center">
                                                    <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest">Compuestos</span>
                                                    <div class="flex items-center space-x-3">
                                                        <div class="flex flex-col items-center">
                                                            <span class="text-[10px] font-black text-gray-700 dark:text-gray-300">{{ $invoices }}</span>
                                                            <span class="text-[8px] font-black text-gray-400 uppercase tracking-tight">Facturas</span>
                                                        </div>
                                                        <span class="text-gray-300">|</span>
                                                        <div class="flex flex-col items-center">
                                                            <span class="text-[10px] font-black text-gray-700 dark:text-gray-300">{{ $tickets }}</span>
                                                            <span class="text-[8px] font-black text-gray-400 uppercase tracking-tight">Tickets</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="hidden sm:flex flex-col items-center max-w-[120px]">
                                                    <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest">Responsable</span>
                                                    <span class="text-[10px] font-bold text-gray-600 dark:text-gray-400 truncate w-full text-center">{{ $mainSolicitor['name'] ?? 'Varios' }}</span>
                                                </div>
                                                <div class="text-right">
                                                    <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest block mb-0.5">Total Lote</span>
                                                    <span class="text-xl font-black text-gray-900 dark:text-white">${{ number_format($typeItems->sum('total'), 2) }}</span>
                                                </div>
                                                <div class="w-8 h-8 flex items-center justify-center rounded-full bg-gray-100 dark:bg-gray-700 group-hover/type:bg-indigo-100 dark:group-hover/type:bg-indigo-900 transition-colors">
                                                    <svg class="w-4 h-4 text-gray-400 group-hover/type:text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
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
