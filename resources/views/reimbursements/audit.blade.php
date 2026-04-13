<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center space-x-3">
            <a href="{{ route('reimbursements.index', ['tab' => request('tab', 'management')]) }}" class="p-2 rounded-xl bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            @php
                $tab = request('tab', 'management');
                $tabName = match($tab) {
                    'management' => 'Módulo de Gestión',
                    'active' => 'Mis Reembolsos',
                    'history' => 'Mis Pagados/Rechazados',
                    'global_history' => 'Todos los Reembolsos (Global)',
                    default => 'Reembolsos'
                };
            @endphp
            <div>
                <p class="text-[10px] font-black text-indigo-500 uppercase tracking-widest italic opacity-70">{{ $tabName }}</p>
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
                <div x-data="bulkAudit()" class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-3xl border border-gray-100 dark:border-gray-700 relative">
                    {{-- Modal moved here to ensure it is always in the same scope --}}
                    @include('reimbursements.partials.bulk-audit-modal')


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
                                <a href="{{ route('reimbursements.audit', ['week' => $selectedWeek, 'tab' => request('tab')]) }}" class="text-[10px] font-black text-indigo-600 hover:underline uppercase tracking-widest">Ver toda la semana</a>
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


                    {{-- Global Audit Filters (Detail View) --}}
                    <div class="px-8 mt-4 mb-6">
                        <div class="bg-gray-50 dark:bg-gray-700/50 p-4 rounded-lg border border-gray-200 dark:border-gray-700">
                            <form action="{{ route('reimbursements.audit') }}" method="GET" class="grid grid-cols-1 md:grid-cols-6 gap-4" novalidate>
                                {{-- Preserve existing params --}}
                                @foreach(request()->except(['search_audit', 'validation_audit', 'xml_audit', 'method_audit', 'usage_audit']) as $name => $value)
                                    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                                @endforeach

                                {{-- Row 1 --}}
                                <div class="col-span-1 md:col-span-6">
                                    <label for="search_audit_det" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Buscador General</label>
                                    <input type="text" name="search_audit" id="search_audit_det" value="{{ request('search_audit') }}" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="Busca cualquier dato del reembolso...">
                                </div>

                                {{-- Section: Detail Filters --}}
                                <div class="col-span-1 md:col-span-2">
                                    <label for="validation_audit_det" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Validación</label>
                                    <select name="validation_audit" id="validation_audit_det" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                        <option value="">Cualquier validación</option>
                                        <option value="success" {{ request('validation_audit') == 'success' ? 'selected' : '' }}>Éxito (Validadas)</option>
                                        <option value="error" {{ request('validation_audit') == 'error' ? 'selected' : '' }}>Error (Desajuste)</option>
                                        <option value="manual" {{ request('validation_audit') == 'manual' ? 'selected' : '' }}>Manual (Sin XML)</option>
                                    </select>
                                </div>
                                <div class="col-span-1 md:col-span-2">
                                    <label for="xml_audit_det" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Documento XML</label>
                                    <select name="xml_audit" id="xml_audit_det" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                        <option value="">Todos los tipos</option>
                                        <option value="with_xml" {{ request('xml_audit') == 'with_xml' ? 'selected' : '' }}>Con Factura (XML)</option>
                                        <option value="no_xml" {{ request('xml_audit') == 'no_xml' ? 'selected' : '' }}>Sin Factura (Ticket)</option>
                                    </select>
                                </div>
                                <div class="col-span-1 md:col-span-2">
                                    <label for="method_audit_det" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Método de Pago</label>
                                    <select name="method_audit" id="method_audit_det" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                        <option value="">Cualquiera</option>
                                        @foreach($availableMethods as $method)
                                            <option value="{{ $method }}" {{ request('method_audit') == $method ? 'selected' : '' }}>{{ $method }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-span-1 md:col-span-2">
                                    <label for="usage_audit_det" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Uso de CFDI</label>
                                    <select name="usage_audit" id="usage_audit_det" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                        <option value="">Cualquiera</option>
                                        @foreach($availableUsages as $usage)
                                            <option value="{{ $usage }}" {{ request('usage_audit') == $usage ? 'selected' : '' }}>{{ $usage }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-span-1 md:col-span-4 flex justify-end items-end space-x-2">
                                    <a href="{{ route('reimbursements.audit', request()->only(['tab', 'week', 'cc', 'type'])) }}" class="inline-flex items-center px-4 py-2 bg-gray-200 dark:bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-gray-700 dark:text-gray-200 uppercase tracking-widest hover:bg-gray-300 dark:hover:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150 h-[38px]">
                                        Limpiar
                                    </a>
                                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150 h-[38px]">
                                        Filtrar
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    {{-- Detalle de Items (Card-Style strictly aligned with summary) --}}
                    <div class="p-4 md:p-8 space-y-3 relative">
                        
                        <div class="flex justify-between items-center px-2 mb-4 bg-gray-50/80 dark:bg-gray-800/50 p-2 rounded-xl border border-gray-200 dark:border-gray-700">
                            <label class="flex items-center space-x-3 cursor-pointer select-none">
                                <input type="checkbox" x-model="selectAll" @change="toggleAll" class="w-5 h-5 rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 transition-all duration-200" />
                                <span class="text-[10px] font-black uppercase tracking-widest text-gray-500">Seleccionar Todos</span>
                            </label>
                            
                            <div x-show="selectedCount > 0" x-transition.opacity class="flex items-center space-x-4">
                                <span class="text-xs font-black text-indigo-600 uppercase tracking-widest" x-text="selectedCount + ' Seleccionados'"></span>
                                
                                <button type="button" @click="openModal = true" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-[10px] font-black uppercase tracking-widest rounded-xl transition-colors shadow-lg shadow-indigo-200 flex items-center space-x-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                                    <span>Acción Masiva</span>
                                </button>

                                <button type="button" @click="openModal = true" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-[10px] font-black uppercase tracking-widest rounded-xl transition-colors shadow-lg shadow-indigo-200 flex items-center space-x-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                                    <span>Acción Masiva</span>
                                </button>
                            </div>
                        </div>
                        @forelse($auditItems as $r)
                            @php
                                $typeAbbr = strtoupper(substr($r->type, 0, 3));
                                $ccAbbr = $r->costCenter->abbreviation ?? 'SCC';
                                $year = $r->fecha ? $r->fecha->format('Y') : date('Y');
                                $compositeId = "{$ccAbbr}-{$typeAbbr}-{$year}-{$r->week}-" . str_pad($r->id, 3, '0', STR_PAD_LEFT);
                                
                                $val = $r->validation_data ?? [];
                                $uuidMatch = $val['uuid_match'] ?? true; 
                                $totalMatch = $val['total_match'] ?? true;

                                // SEMAFORO LOGIC
                                $semaforoColor = 'bg-emerald-500 shadow-emerald-200'; // Success
                                $semaforoText = 'Validada';
                                
                                if (!$r->uuid) {
                                    $semaforoColor = 'bg-amber-500 shadow-amber-200'; // Manual (Ticket)
                                    $semaforoText = 'Manual';
                                } elseif (!$uuidMatch || !$totalMatch) {
                                    $semaforoColor = 'bg-red-500 shadow-red-200'; // Error
                                    $semaforoText = 'Error';
                                }
                            @endphp
                            <a href="{{ route('reimbursements.show', $r->id) }}"
                               class="flex flex-col md:flex-row items-center justify-between p-5 bg-gray-50/50 dark:bg-gray-900/40 hover:bg-white dark:hover:bg-gray-800 rounded-2xl border border-transparent hover:border-indigo-100 dark:hover:border-indigo-900 hover:shadow-lg transition-all group/item no-underline space-y-4 md:space-y-0">
                                
                                <div class="flex items-center space-x-5">
                                    <div class="flex items-center justify-center w-10 h-10 border border-transparent">
                                        <input type="checkbox" value="{{ $r->id }}" x-model="selectedIds" data-amount="{{ $r->total }}" data-has-uuid="{{ $r->uuid ? '1' : '0' }}" data-mismatch="{{ (!$uuidMatch || !$totalMatch) ? '1' : '0' }}" @click.stop class="reimbursement-checkbox w-6 h-6 rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 transition-all duration-200 cursor-pointer" />

                                    </div>
                                    <div class="flex flex-col">
                                        <div class="flex items-center space-x-2 mb-0.5">
                                            <span class="text-[9px] font-black uppercase tracking-widest text-indigo-500 italic">{{ $compositeId }}</span>
                                        </div>
                                        <span class="text-sm font-black text-gray-700 dark:text-gray-300">{{ $r->nombre_emisor ?: 'S/N' }}</span>
                                        <div class="flex items-center space-x-1 mt-0.5">
                                            <span class="text-[8px] font-black text-gray-400 uppercase tracking-tighter">{{ $r->fecha ? $r->fecha->format('d/m/Y') : 'S/F' }}</span>
                                            <span class="text-gray-300 mx-1">|</span>
                                            <span class="text-[8px] font-black text-gray-400 uppercase tracking-tighter truncate max-w-[150px]">{{ $r->uuid ?? 'TICKET / MANUAL' }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex flex-wrap items-center gap-6 md:gap-12">
                                    {{-- Semaforo --}}
                                    <div class="flex flex-col items-center">
                                        <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1 leading-none">Validación</span>
                                        <div class="flex items-center space-x-1.5">
                                            <div class="w-2.5 h-2.5 rounded-full {{ $semaforoColor }} shadow-sm"></div>
                                            <span class="text-[10px] font-black text-gray-700 dark:text-gray-300 uppercase tracking-tighter">{{ $semaforoText }}</span>
                                        </div>
                                    </div>

                                    {{-- Documento --}}
                                    <div class="hidden sm:flex flex-col items-center">
                                        <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1 leading-none">Documento</span>
                                        @if($r->uuid)
                                            <span class="text-[9px] font-black text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/30 px-2 py-0.5 rounded border border-indigo-100 dark:border-indigo-800 uppercase tracking-tighter">Factura</span>
                                        @else
                                            <span class="text-[9px] font-black text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/30 px-2 py-0.5 rounded border border-amber-100 dark:border-amber-800 uppercase tracking-tighter">Ticket / Manual</span>
                                        @endif
                                    </div>

                                    {{-- Pago/Uso --}}
                                    <div class="hidden sm:flex flex-col items-center">
                                        <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1 leading-none">Método / Uso</span>
                                        <div class="flex items-center space-x-2">
                                            <span class="text-[10px] font-bold text-gray-600 bg-gray-100 dark:bg-gray-800 px-1.5 rounded-md border border-gray-200 dark:border-gray-700">{{ $r->metodo_pago ?? 'N/A' }}</span>
                                            <span class="text-[10px] font-bold text-indigo-600 bg-indigo-50 dark:bg-indigo-900/30 px-1.5 rounded-md border border-indigo-100 dark:border-indigo-800">{{ $r->uso_cfdi ?? 'N/A' }}</span>
                                        </div>
                                    </div>

                                    {{-- Solicitante --}}
                                    <div class="hidden lg:flex flex-col items-center max-w-[100px]">
                                        <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1 leading-none">Solicitante</span>
                                        <span class="text-[10px] font-bold text-gray-600 dark:text-gray-400 uppercase truncate w-full text-center">{{ $r->user->name ?? 'N/A' }}</span>
                                    </div>

                                    {{-- Monto --}}
                                    <div class="text-right">
                                        <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest block mb-0.5 leading-none">Monto Neto</span>
                                        <div class="flex flex-col items-end">
                                            <span class="text-xl font-black text-gray-900 dark:text-white">${{ number_format($r->total, 2) }}</span>
                                            <span class="text-[8px] font-black text-indigo-500 uppercase tracking-widest italic opacity-50">BASE: ${{ number_format($r->subtotal, 2) }}</span>
                                        </div>
                                    </div>

                                </div>
                            </a>
                        @empty
                            <div class="px-6 py-16 text-center text-sm text-gray-400 font-bold italic bg-gray-50/50 dark:bg-gray-800/20 rounded-2xl border border-dashed border-gray-200 dark:border-gray-700">
                                Sin reembolsos para este grupo.
                            </div>
                        @endforelse
                    </div>
                </div>

            @else
                {{-- ===== PANTALLA 2: Desglose de Centros de Costo por Semana ===== --}}
                <div x-data="bulkAuditIndex()" class="relative border-transparent">
                    {{-- Modal moved here to ensure it is always in the same scope --}}
                    @include('reimbursements.partials.bulk-index-modal')


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
                                    <a href="{{ route('reimbursements.index', ['tab' => request('tab', 'management')]) }}" class="bg-white dark:bg-gray-800 px-4 py-2 border border-gray-200 dark:border-gray-700 rounded-xl text-[10px] font-black uppercase tracking-widest text-gray-500 hover:text-indigo-600 transition-colors">Volver a listado</a>
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

                         {{-- Global Audit Filters (Summary View) --}}
                         <div class="px-8 mt-4 mb-6">
                            <div class="bg-gray-50 dark:bg-gray-700/50 p-4 rounded-lg border border-gray-200 dark:border-gray-700">
                                <form action="{{ route('reimbursements.audit') }}" method="GET" class="grid grid-cols-1 md:grid-cols-6 gap-4" novalidate>
                                    {{-- Preserve existing params --}}
                                    @foreach(request()->except(['search_audit', 'type_audit', 'category_audit', 'xml_audit']) as $name => $value)
                                        <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                                    @endforeach

                                    {{-- Row 1 --}}
                                    <div class="col-span-1 md:col-span-6">
                                        <label for="search_audit_sum" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Buscador General</label>
                                        <input type="text" name="search_audit" id="search_audit_sum" value="{{ request('search_audit') }}" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="Busca cualquier dato del reembolso...">
                                    </div>

                                    {{-- Row 2 --}}
                                    <div class="col-span-1 md:col-span-3">
                                        <label for="type_audit_sum" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tipo de Reembolso</label>
                                        <select name="type_audit" id="type_audit_sum" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                            <option value="">Todos</option>
                                            <option value="reembolso" {{ request('type_audit') == 'reembolso' ? 'selected' : '' }}>Reembolso</option>
                                            <option value="comida" {{ request('type_audit') == 'comida' ? 'selected' : '' }}>Comida</option>
                                            <option value="fondo_fijo" {{ request('type_audit') == 'fondo_fijo' ? 'selected' : '' }}>Fondo Fijo</option>
                                            <option value="viaje" {{ request('type_audit') == 'viaje' ? 'selected' : '' }}>Viaje</option>
                                        </select>
                                    </div>
                                    <div class="col-span-1 md:col-span-3 flex justify-end items-end space-x-2">
                                        <a href="{{ route('reimbursements.audit', request()->only(['tab'])) }}" class="inline-flex items-center px-4 py-2 bg-gray-200 dark:bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-gray-700 dark:text-gray-200 uppercase tracking-widest hover:bg-gray-300 dark:hover:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150 h-[38px]">
                                            Limpiar
                                        </a>
                                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150 h-[38px]">
                                            Filtrar
                                        </button>
                                    </div>
                                </form>
                            </div>
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
                                                $tickets = $ccItems->whereNull('uuid')->count();
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

                                <!-- Action Bar for Group in Pantalla 2 (Inline) -->
                                <div class="flex justify-between items-center px-4 mb-4 bg-gray-50/80 dark:bg-gray-800/50 p-2 rounded-xl border border-gray-200 dark:border-gray-700">
                                    <label class="flex items-center space-x-3 cursor-pointer select-none">
                                        <input type="checkbox" x-model="selectAll" @change="toggleAll" class="w-5 h-5 rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 transition-all duration-200" />
                                        <span class="text-[10px] font-black uppercase tracking-widest text-gray-500">Seleccionar Todos</span>
                                    </label>
                                    
                                    <div x-show="selectedGroupCount > 0" x-transition.opacity class="flex items-center space-x-4">
                                        <span class="text-xs font-black text-indigo-600 uppercase tracking-widest" x-text="selectedGroupCount + ' Seleccionados'"></span>
                                        
                                        <button type="button" @click="downloadCaratula()" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-[10px] font-black uppercase tracking-widest rounded-xl transition-colors shadow-lg shadow-emerald-200 flex items-center space-x-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                            <span>Descargar Carátula</span>
                                        </button>

                                        <button type="button" @click="openModal = true" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-[10px] font-black uppercase tracking-widest rounded-xl transition-colors shadow-lg shadow-indigo-200 flex items-center space-x-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                                            <span>Acción Masiva</span>
                                        </button>
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
                                            $tickets = $typeItems->whereNull('uuid')->count();
                                            
                                            $mismatchCount = 0;
                                            foreach($typeItems as $tItem) {
                                                $val = $tItem->validation_data ?? [];
                                                if (!($val['uuid_match'] ?? true) || !($val['total_match'] ?? true)) {
                                                    $mismatchCount++;
                                                }
                                            }
                                            $idsJson = json_encode($typeItems->pluck('id'));
                                            $totalTypeAmount = $typeItems->sum('total');
                                            
                                            $mainSolicitor = $typeItems->groupBy('user_id')
                                                ->map(fn($group) => ['name' => $group->first()->user->name ?? 'N/A', 'total' => $group->sum('total')])
                                                ->sortByDesc('total')
                                                ->first();
                                        @endphp
                                        <a href="{{ route('reimbursements.audit', ['week' => $selectedWeek, 'cc' => $ccName, 'type' => $type, 'tab' => request('tab')]) }}"
                                           class="flex flex-col md:flex-row items-center justify-between p-5 bg-gray-50 dark:bg-gray-900/40 hover:bg-white dark:hover:bg-gray-800 rounded-2xl border border-transparent hover:border-indigo-100 dark:hover:border-indigo-900 hover:shadow-lg transition-all group/type no-underline space-y-4 md:space-y-0">
                                            <div class="flex items-center space-x-5">
                                                <div class="flex items-center justify-center border-r border-gray-200 dark:border-gray-700 pr-4" @click.stop>
                                                    <input type="checkbox"
                                                           data-ids="{{ $idsJson }}"
                                                           data-amount="{{ $totalTypeAmount }}"
                                                           data-has-uuid="{{ $tickets }}"
                                                           data-mismatch="{{ $mismatchCount }}"
                                                           class="cc-group-checkbox w-6 h-6 rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 transition-all duration-200 cursor-pointer" 
                                                           @change="toggleGroupData($event.target)" />
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
                                <a href="{{ route('reimbursements.index', ['tab' => request('tab', 'management')]) }}" class="mt-6 inline-flex items-center text-indigo-600 font-bold hover:underline">
                                    ← Volver al listado
                                </a>
                            </div>
                        @endforelse

                    @else
                        <div class="px-8 mt-4 mb-20">
                            {{-- Global Audit Filters (Landing View) --}}
                            <div class="bg-white dark:bg-gray-800 p-8 rounded-[2.5rem] border border-gray-100 dark:border-gray-700 shadow-xl mb-8">
                                <form action="{{ route('reimbursements.audit') }}" method="GET" class="grid grid-cols-1 md:grid-cols-6 gap-6" novalidate>
                                    <input type="hidden" name="tab" value="{{ request('tab', 'management') }}">

                                    {{-- Row 1 --}}
                                    <div class="col-span-1 md:col-span-3">
                                        <label for="search_audit_land" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Buscador General</label>
                                        <input type="text" name="search_audit" id="search_audit_land" value="{{ request('search_audit') }}" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="Busca cualquier dato del reembolso...">
                                    </div>
                                    <div class="col-span-1 md:col-span-3">
                                        <label for="cc_land" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Centro de Costos / Obra</label>
                                        <select name="cc" id="cc_land" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                            <option value="">Selecciona CC...</option>
                                            @foreach($authorizedCCs as $cc)
                                                <option value="{{ $cc->name }}" {{ request('cc') == $cc->name ? 'selected' : '' }}>{{ $cc->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    {{-- Row 2 --}}
                                    <div class="col-span-1 md:col-span-3">
                                        <label for="week_land" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Semana Fiscal</label>
                                        <select name="week" id="week_land" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                            <option value="">Selecciona Semana...</option>
                                            @foreach($availableWeeks as $w)
                                                <option value="{{ $w }}" {{ request('week') == $w ? 'selected' : '' }}>Semana {{ $w }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-span-1 md:col-span-3 flex justify-end items-end space-x-2">
                                        <a href="{{ route('reimbursements.audit', ['tab' => request('tab', 'management')]) }}" class="inline-flex items-center px-6 py-3 bg-gray-200 dark:bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-gray-700 dark:text-gray-200 uppercase tracking-widest hover:bg-gray-300 dark:hover:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150 h-[44px]">
                                            Limpiar
                                        </a>
                                        <button type="submit" class="inline-flex items-center px-6 py-3 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150 h-[44px]">
                                            Filtrar Auditoría
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <div class="text-center py-12">
                                <h3 class="text-xl font-normal text-gray-400 uppercase tracking-[0.2em]">Comienza seleccionando una semana para auditar</h3>
                                <p class="text-sm text-gray-400 mt-2">Usa los filtros superiores para navegar entre proyectos y semanas.</p>
                            </div>
                        </div>
                    @endif

                </div>
            @endif

        </div>
    </div>

    <!-- Bulk Audit Action Modal logic moved into the component above -->




    <!-- Caratula PDF Modal (audit view) -->
    <div x-data="{
        open: false, 
        week: '{{ $auditMeta['week'] ?? '' }}', 
        cost_center_id: '{{ ($auditItems && $auditItems->count() > 0) ? $auditItems->first()->cost_center_id : '' }}',
        loading: false, progress: 0,
        async startDownload(url) {
            this.loading = true;
            this.progress = 0;
            let sim = 0;
            // Simulate smooth progress (0→85%) while server generates the PDF
            const ticker = setInterval(() => {
                if (sim < 85) {
                    // Easing: fast start, slows down as it approaches 85
                    sim += (85 - sim) * 0.04;
                    this.progress = Math.round(sim);
                }
            }, 100);
            try {
                const response = await fetch(url);
                clearInterval(ticker);
                if (!response.ok) throw new Error('Error al generar el PDF');
                // Read the response (server already generated it, comes in fast)
                const contentLength = response.headers.get('Content-Length');
                const total = contentLength ? parseInt(contentLength) : 0;
                const reader = response.body.getReader();
                const chunks = [];
                let received = 0;
                while(true) {
                    const { done, value } = await reader.read();
                    if (done) break;
                    chunks.push(value);
                    received += value.length;
                    // Map download phase to 85→99%
                    if (total) this.progress = Math.round(85 + ((received / total) * 14));
                }
                this.progress = 100;
                const blob = new Blob(chunks, { type: 'application/pdf' });
                const dlUrl = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = dlUrl;
                a.download = 'caratula.pdf';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                setTimeout(() => URL.revokeObjectURL(dlUrl), 1000);
                setTimeout(() => { this.loading = false; this.open = false; }, 1500);
            } catch(e) {
                clearInterval(ticker);
                this.loading = false;
                console.error('Error generando caratula:', e);
                alert('Ocurrió un error al generar la carátula. Intenta de nuevo.');
            }
        }
    }" 
         @open-caratula-pdf-modal.window="open = true" 
         x-show="open" 
         class="fixed z-50 inset-0 overflow-y-auto" 
         style="display: none;">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="open = false" aria-hidden="true"></div>
            <div class="inline-block bg-white dark:bg-gray-800 rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:max-w-lg sm:w-full border border-gray-100 dark:border-gray-700">
                <div class="p-8">
                    <div class="flex items-center space-x-4 mb-6">
                        <div class="w-12 h-12 bg-emerald-100 dark:bg-emerald-900/30 rounded-2xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-black text-gray-900 dark:text-white tracking-tight leading-none">Generar Carátulas PDF</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Consolidado de reembolsos y comprobantes.</p>
                        </div>
                    </div>

                    <div class="space-y-5">
                        <!-- Semana -->
                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Semana Fiscal</label>
                            @php
                                $pdfWeeks = \App\Models\Reimbursement::select('week')->whereNotNull('week')->distinct()->orderByRaw("SUBSTRING_INDEX(week, '-', -1) DESC")->orderByRaw("SUBSTRING_INDEX(week, '-', 1) DESC")->pluck('week');
                            @endphp
                            <select x-model="week" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 text-sm font-bold">
                                <option value="">Todas las semanas</option>
                                @foreach($pdfWeeks as $pw)
                                    <option value="{{ $pw }}" {{ ($auditMeta['week'] ?? '') == $pw ? 'selected' : '' }}>Semana {{ $pw }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Centro de Costos -->
                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Centro de Costos / Obra</label>
                            @php
                                $pdfCCs = \App\Models\CostCenter::orderBy('name')->get();
                            @endphp
                            <select x-model="cost_center_id" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 text-sm font-bold">
                                <option value="">Todos los centros</option>
                                @foreach($pdfCCs as $pcc)
                                    <option value="{{ $pcc->id }}" {{ (isset($auditItems) && $auditItems->count() > 0 && $auditItems->first()->cost_center_id == $pcc->id) ? 'selected' : '' }}>{{ $pcc->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="mt-8 flex flex-col space-y-3">
                        <!-- Progress Bar (visible during loading) -->
                        <div x-show="loading" class="w-full">
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="text-xs font-black text-emerald-600 uppercase tracking-widest">Generando Carátula...</span>
                                <span class="text-xs font-black text-gray-500" x-text="progress + '%'"></span>
                            </div>
                            <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-2.5 overflow-hidden">
                                <div class="bg-emerald-500 h-2.5 rounded-full transition-all duration-300 ease-out"
                                     :style="`width: ${progress}%`"></div>
                            </div>
                            <p class="text-[10px] text-gray-400 mt-1.5 text-center">El popup se cerrará automáticamente al terminar.</p>
                        </div>

                        <button type="button" 
                                x-show="!loading"
                                @click="startDownload(`{{ route('reimbursements.download_caratula') }}?week=${week}&cost_center_id=${cost_center_id}&tab={{ request('tab', 'management') }}`)"
                                class="w-full inline-flex justify-center items-center rounded-xl px-4 py-3 bg-emerald-600 text-sm font-black text-white uppercase tracking-widest hover:bg-emerald-700 transition-colors shadow-lg shadow-emerald-100 dark:shadow-none">
                            Descargar Carátula PDF
                        </button>
                        <button type="button" @click="open = false" :disabled="loading" class="text-xs font-black text-gray-400 hover:text-gray-600 uppercase tracking-widest transition-colors pb-2 disabled:opacity-40 disabled:cursor-not-allowed">
                            Cancelar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('bulkAudit', () => ({
            selectedIds: [],
            selectAll: false,
            openModal: false,
            confirmed: false,
            selectedAction: '',
            
            get selectedCount() {
                return this.selectedIds.length;
            },
            
            get allCheckboxes() {
                return Array.from(document.querySelectorAll('input[type="checkbox"][data-amount]'));
            },
            
            get totalAmount() {
                let total = 0;
                this.selectedIds.forEach(id => {
                    const el = document.querySelector(`input[type="checkbox"][value="${id}"]`);
                    if (el) total += parseFloat(el.dataset.amount || 0);
                });
                return total;
            },
            
            get missingUuidCount() {
                let count = 0;
                this.selectedIds.forEach(id => {
                    const el = document.querySelector(`input[type="checkbox"][value="${id}"]`);
                    if (el && el.dataset.hasUuid === '0') count++;
                });
                return count;
            },
            
            get mismatchCount() {
                let count = 0;
                this.selectedIds.forEach(id => {
                    const el = document.querySelector(`input[type="checkbox"][value="${id}"]`);
                    if (el && el.dataset.mismatch === '1') count++;
                });
                return count;
            },
            
            get totalAlerts() {
                return this.missingUuidCount + this.mismatchCount;
            },
            
            toggleAll() {
                if (this.selectAll) {
                    this.selectedIds = this.allCheckboxes.map(cb => cb.value);
                } else {
                    this.selectedIds = [];
                }
            },
            
            formatMoney(amount) {
                return Number(amount).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            },
            
            init() {
                // Ensure bulk modal markup is teleported or works fine inline.
                // We placed it outside the main data loop, but we can access it using Alpine teleport if needed, or just let Alpine handle it.
                // Since Alpine handles nested state resolution, if modal is inside x-data bounds it works.
                // I've moved the modal into the main layout root or just bound to document body.
                // Wait, if the modal is placed outside `x-data="bulkAudit()"`, the modal's internal x-model won't bind.
                // To fix this, I made sure the `x-data="bulkAudit()"` wrapper actually wraps the END elements too? 
                // Ah, I ended `</div>` on line 428 in the file content previously? 
                // We will append the modal to the body by Alpine teleport, wait teleport is not standard in older alpine 3 without plugin.
                // It's safest to just rely on the existing DOM. The modal is placed right below the main container. Let's make sure it's accessible.
                
                init() {
                    // Modal is now inline.
                }
            }
        }));
    });
</script>

    <!-- Bulk Main Action Modal logic moved into the component above -->


    

    <script>
        let searchTimeout;
        function debounceSubmit(input) {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                input.form.submit();
            }, 500);
        }
    </script>
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('bulkAuditIndex', () => ({
                selectedIds: [],
                openModal: false,
                confirmed: false,
                selectedAction: '',
                selectAll: false,
                
                // Track metadata manually because DOM inputs can be detached during re-render
                metadata: [],
                
                toggleAll() {
                    if (this.selectAll) {
                        this.selectedIds = [];
                        this.metadata = [];
                        document.querySelectorAll('.cc-group-checkbox').forEach(cb => {
                            cb.checked = true;
                            this.toggleGroupData(cb);
                        });
                    } else {
                        document.querySelectorAll('.cc-group-checkbox').forEach(cb => cb.checked = false);
                        this.selectedIds = [];
                        this.metadata = [];
                    }
                },
                
                get selectedGroupCount() {
                    return this.selectedIds.length;
                },
                
                get totalAmount() {
                    return this.metadata.reduce((sum, item) => sum + parseFloat(item.amount || 0), 0);
                },
                
                get missingUuidCount() {
                    return this.metadata.reduce((sum, item) => sum + parseInt(item.hasUuid || 0), 0);
                },
                
                get mismatchCount() {
                    return this.metadata.reduce((sum, item) => sum + parseInt(item.mismatch || 0), 0);
                },
                
                get totalAlerts() {
                    return this.missingUuidCount + this.mismatchCount;
                },
                
                toggleGroupData(target) {
                    const idsArr = JSON.parse(target.dataset.ids || "[]");
                    const amount = parseFloat(target.dataset.amount || 0);
                    const hasUuid = parseInt(target.dataset.hasUuid || 0);
                    const mismatch = parseInt(target.dataset.mismatch || 0);
                    
                    if (target.checked) {
                        idsArr.forEach(id => {
                            if (!this.selectedIds.includes(String(id))) this.selectedIds.push(String(id));
                        });
                        this.metadata.push({ idsArr, amount, hasUuid, mismatch });
                    } else {
                        this.selectedIds = this.selectedIds.filter(id => !idsArr.map(String).includes(String(id)));
                        this.metadata = this.metadata.filter(m => JSON.stringify(m.idsArr) !== JSON.stringify(idsArr));
                    }
                },
                
                formatMoney(amount) {
                    return Number(amount).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                },
                
                downloadCaratula() {
                    if (this.selectedIds.length === 0) return;
                    const ids = this.selectedIds.join(',');
                    window.location.href = `{{ route('reimbursements.download_caratula') }}?ids=${ids}`;
                },

                init() {
                    // Modal is now inline, no need to move it.
                }
            }));

            Alpine.data('bulkAudit', () => ({
                selectedIds: [],
                openModal: false,
                confirmed: false,
                selectedAction: '',
                selectAll: false,
                
                toggleAll() {
                    if (this.selectAll) {
                        const checkboxes = document.querySelectorAll('.reimbursement-checkbox');
                        checkboxes.forEach(cb => {
                            if (!this.selectedIds.includes(String(cb.value))) {
                                this.selectedIds.push(String(cb.value));
                            }
                        });
                    } else {
                        this.selectedIds = [];
                    }
                },
                
                get selectedCount() {
                    return this.selectedIds.length;
                },
                
                get totalAmount() {
                    let sum = 0;
                    this.selectedIds.forEach(id => {
                        const el = document.querySelector(`input[type="checkbox"][value="${id}"]`);
                        if (el) sum += parseFloat(el.dataset.amount || 0);
                    });
                    return sum;
                },
                
                get missingUuidCount() {
                    let count = 0;
                    this.selectedIds.forEach(id => {
                        const el = document.querySelector(`input[type="checkbox"][value="${id}"]`);
                        if (el && el.dataset.hasUuid === '0') count++;
                    });
                    return count;
                },
                
                get mismatchCount() {
                    let count = 0;
                    this.selectedIds.forEach(id => {
                        const el = document.querySelector(`input[type="checkbox"][value="${id}"]`);
                        if (el && el.dataset.mismatch === '1') count++;
                    });
                    return count;
                },
                
                get totalAlerts() {
                    return this.missingUuidCount + this.mismatchCount;
                },
                
                formatMoney(amount) {
                    return Number(amount).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                },

                downloadCaratula() {
                    if (this.selectedIds.length === 0) return;
                    const ids = this.selectedIds.join(',');
                    window.location.href = `{{ route('reimbursements.download_caratula') }}?ids=${ids}`;
                },
                
                init() {
                    // Modal is now inline.
                }
            }));
        });
    </script>
