<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Panel') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
    @php
        $user = Auth::user();
        // Updated Full Access: Admin, CXP (N4), Subdir (N5), Direcc/Tes (N6)
        // Removed N3 (Executive Director) from full global metrics
        $hasFullAccess = $user->isAdmin() || $user->isAdminView() || $user->isCxp() || $user->isDireccion() || $user->isTreasury();
    @endphp

    <div class="py-12">
                <div>
                    <h3 class="text-3xl font-extrabold text-gray-900 dark:text-white">¡Hola, {{ Auth::user()->name }}! 👋</h3>
                    <div class="flex items-center gap-2 mt-1">
                        <p class="text-gray-500 dark:text-gray-400 font-medium">Hoy es {{ now()->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY') }}</p>
                        <span class="px-2 py-0.5 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded-lg text-[10px] font-black uppercase tracking-wider border border-indigo-100 dark:border-indigo-800">
                            Semana {{ now()->addDays(2)->format('W') }}
                        </span>
                    </div>
                </div>
                
                @if(!Auth::user()->isAdminView())
                <div class="flex gap-3">
                    <a href="{{ route('reimbursements.create') }}" class="inline-flex items-center px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl transition-all shadow-lg hover:shadow-indigo-500/30">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        Nuevo Reembolso
                    </a>
                </div>
                @endif
            </div>

            <!-- Stats Grid (Dynamic based on Role) -->
            <div class="mb-10">
                <h4 class="text-[10px] font-black uppercase text-gray-400 tracking-[0.2em] mb-4">
                    {{ isset($stats['management']) ? 'Resumen de Gestión / Operación' : 'Mi Resumen Personal' }}
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    @php
                        $displayStats = isset($stats['management']) ? $stats['management'] : $stats['personal'];
                        $isManagement = isset($stats['management']);
                    @endphp

                    <!-- En Proceso / En Tránsito -->
                    <div class="bg-gradient-to-br from-indigo-50 to-white dark:from-indigo-900/10 dark:to-gray-800 p-6 rounded-2xl shadow-sm border border-indigo-100 dark:border-indigo-900/30 relative overflow-hidden group">
                        <div class="relative z-10">
                            <p class="text-indigo-600 dark:text-indigo-400 text-xs font-black uppercase tracking-widest mb-1">
                                {{ $isManagement ? 'En Tránsito (Global)' : 'Mis Pendientes' }}
                            </p>
                            <h4 class="text-4xl font-black text-gray-900 dark:text-white">{{ $displayStats['pending_count'] }}</h4>
                            <p class="text-indigo-700 dark:text-indigo-300 font-bold mt-1 text-sm truncate">
                                ${{ number_format($displayStats['pending_amount'], 2) }}
                            </p>
                            @if($isManagement && isset($stats['personal']) && $stats['personal']['pending_count'] > 0)
                                <p class="text-[9px] text-gray-400 mt-2 italic">Tus personales: {{ $stats['personal']['pending_count'] }} (${{ number_format($stats['personal']['pending_amount'], 2) }})</p>
                            @endif
                        </div>
                        <svg class="absolute -right-4 -bottom-4 w-24 h-24 text-indigo-500/10 group-hover:scale-110 transition-transform duration-500" fill="currentColor" viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    </div>

                    <!-- Total Pagados -->
                    <div class="bg-gradient-to-br from-emerald-50 to-white dark:from-emerald-900/10 dark:to-gray-800 p-6 rounded-2xl shadow-sm border border-emerald-100 dark:border-emerald-900/30 relative overflow-hidden group">
                        <div class="relative z-10">
                            <p class="text-emerald-600 dark:text-emerald-400 text-xs font-black uppercase tracking-widest mb-1">
                                {{ $isManagement ? 'Pagados (Global)' : 'Mis Pagados' }}
                            </p>
                            <h4 class="text-4xl font-black text-gray-900 dark:text-white">{{ $displayStats['approved_count'] }}</h4>
                            <p class="text-emerald-700 dark:text-emerald-300 font-bold mt-1 text-sm truncate">
                                ${{ number_format($displayStats['approved_amount'], 2) }}
                            </p>
                        </div>
                        <svg class="absolute -right-4 -bottom-4 w-24 h-24 text-emerald-500/10 group-hover:scale-110 transition-transform duration-500" fill="currentColor" viewBox="0 0 24 24"><path d="M12 8c-1.657 0-3 1.343-3 3s1.343 3 3 3 3-1.343 3-3-1.343-3-3-3z"></path><path fill-rule="evenodd" d="M12 2C6.477 2 2 6.477 2 12s4.477 10 10 10 10-4.477 10-10S17.523 2 12 2zM7.001 5a1 1 0 011-1h8a1 1 0 110 2h-8a1 1 0 01-1-1zm0 14a1 1 0 110-2h8a1 1 0 110 2h-8z" clip-rule="evenodd"></path></svg>
                    </div>

                    <!-- Rechazados -->
                    <div class="bg-gradient-to-br from-rose-50 to-white dark:from-rose-900/10 dark:to-gray-800 p-6 rounded-2xl shadow-sm border border-rose-100 dark:border-rose-900/30 relative overflow-hidden group">
                        <div class="relative z-10">
                            <p class="text-rose-600 dark:text-rose-400 text-xs font-black uppercase tracking-widest mb-1">
                                {{ $isManagement ? 'Rechazados (Global)' : 'Mis Rechazados' }}
                            </p>
                            <h4 class="text-4xl font-black text-gray-900 dark:text-white">{{ $displayStats['rejected_count'] ?? 0 }}</h4>
                        </div>
                        <svg class="absolute -right-4 -bottom-4 w-24 h-24 text-rose-500/10 group-hover:scale-110 transition-transform duration-500" fill="currentColor" viewBox="0 0 24 24"><path d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>

                    <!-- Alert Card (Correction or Status Label) -->
                    @if(!$isManagement)
                        @if($stats['personal']['correction_count'] > 0)
                        <div class="bg-gradient-to-br from-orange-50 to-white dark:from-orange-900/10 dark:to-gray-800 p-6 rounded-2xl border border-orange-200 dark:border-orange-900/30 relative overflow-hidden group">
                            <div class="relative z-10">
                                <p class="text-orange-600 dark:text-orange-400 text-xs font-black uppercase tracking-widest mb-1">Para Corregir</p>
                                <h4 class="text-4xl font-black text-gray-900 dark:text-white">{{ $stats['personal']['correction_count'] }}</h4>
                            </div>
                            <svg class="absolute -right-4 -bottom-4 w-24 h-24 text-orange-500/10 animate-pulse" fill="currentColor" viewBox="0 0 24 24"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                        </div>
                        @else
                        <div class="bg-gray-50/50 dark:bg-gray-800/30 p-6 rounded-2xl border border-dashed border-gray-200 dark:border-gray-700 flex items-center justify-center">
                            <p class="text-[10px] font-black text-gray-300 uppercase tracking-widest">Sin alertas</p>
                        </div>
                        @endif
                    @else
                        <!-- Management Level Label -->
                        <div class="bg-indigo-900 p-6 rounded-2xl relative overflow-hidden flex flex-col justify-center">
                            <p class="text-indigo-400 text-[10px] font-black uppercase tracking-widest mb-1">Alcance Actual</p>
                            <h4 class="text-xl font-black text-white leading-tight">
                                {{ Auth::user()->role_name ?? 'Gestión' }} 
                                <span class="text-indigo-500 text-xs block opacity-60">Nivel de Supervisión Activo</span>
                            </h4>
                        </div>
                    @endif
                </div>
            </div>


            <!-- Enhanced Insights Section -->
            <div class="mb-12">
                <!-- Row 1: High Level Metrics -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                    @if($hasFullAccess)
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-[1.5rem] shadow-sm border border-gray-100 dark:border-gray-700">
                        <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-2">Crecimiento Semanal</p>
                        <div class="flex items-center gap-3">
                            <h5 class="text-2xl font-black text-gray-900 dark:text-white">{{ number_format($analytics['week_growth'], 1) }}%</h5>
                            @if($analytics['week_growth'] > 0)
                                <span class="bg-rose-100 text-rose-600 px-2 py-0.5 rounded-lg text-[10px] font-bold">▲ Subió</span>
                            @else
                                <span class="bg-emerald-100 text-emerald-600 px-2 py-0.5 rounded-lg text-[10px] font-bold">▼ Bajó</span>
                            @endif
                        </div>
                        <p class="text-[9px] text-gray-400 mt-2">Vs. semana anterior</p>
                    </div>
                    @endif

                    <div class="bg-white dark:bg-gray-800 p-6 rounded-[1.5rem] shadow-sm border border-gray-100 dark:border-gray-700">
                        <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-2">Ticket Promedio</p>
                        <h5 class="text-2xl font-black text-gray-900 dark:text-white">${{ number_format($analytics['avg_ticket'], 2) }}</h5>
                        <p class="text-[9px] text-gray-400 mt-2">Por cada comprobante</p>
                    </div>

                    @if($hasFullAccess)
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-[1.5rem] shadow-sm border border-gray-100 dark:border-gray-700">
                        <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-2">Recuperación de IVA/Imp</p>
                        <h5 class="text-2xl font-black text-emerald-600">${{ number_format($analytics['tax_summary']->taxes ?? 0, 2) }}</h5>
                        <p class="text-[9px] text-gray-400 mt-2">Deducible identificado</p>
                    </div>
                    @endif

                    <div class="bg-white dark:bg-gray-800 p-6 rounded-[1.5rem] shadow-sm border border-gray-100 dark:border-gray-700">
                        <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-2">Total en Tránsito</p>
                        <h5 class="text-2xl font-black text-indigo-600">${{ number_format($displayStats['pending_amount'] ?? 0, 2) }}</h5>
                        <p class="text-[9px] text-gray-400 mt-2">Esperando aprobación final</p>
                    </div>
                </div>

                <!-- Row 2: Deep Dive Analytics -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
                    <!-- Main Trend Chart -->
                    @if($hasFullAccess)
                    <div class="lg:col-span-2 bg-white dark:bg-gray-800 p-8 rounded-[2rem] shadow-sm border border-gray-100 dark:border-gray-700">
                        <div class="flex items-center justify-between mb-8">
                            <div>
                                <h4 class="text-gray-900 dark:text-white font-black text-xl uppercase tracking-tighter">Tendencia de Dinámica de Gasto</h4>
                                <p class="text-xs text-gray-400 font-bold uppercase tracking-widest">Actividad diaria vs semanal</p>
                            </div>
                            <div class="flex gap-2">
                                <span class="w-3 h-3 rounded-full bg-indigo-500"></span>
                                <span class="w-3 h-3 rounded-full bg-indigo-200"></span>
                            </div>
                        </div>
                        <div class="h-[350px]">
                            <canvas id="dynamicsChart"></canvas>
                        </div>
                    </div>
                    @endif

                    <!-- Status Doughnut Chart (Visible to all, wider if full chart is hidden) -->
                    <div class="{{ $hasFullAccess ? 'lg:col-span-1' : 'lg:col-span-3' }} bg-white dark:bg-gray-800 p-8 rounded-[2rem] shadow-sm border border-gray-100 dark:border-gray-700 flex flex-col">
                        <div class="mb-4 text-center">
                            <h4 class="text-gray-900 dark:text-white font-black text-lg uppercase tracking-tighter">Resumen por Estatus</h4>
                            <p class="text-xs text-gray-400 font-bold uppercase tracking-widest">Distribución de capital</p>
                        </div>
                        <div class="relative flex-1 min-h-[300px] flex items-center justify-center">
                            <canvas id="statusDoughnutChart"></canvas>
                            <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                                <span class="text-[11px] font-extrabold uppercase text-gray-400 tracking-widest">En Proceso</span>
                                <span class="text-2xl font-black text-gray-900 dark:text-white leading-tight">
                                    ${{ number_format($analytics['status_breakdown']->whereNotIn('status', ['aprobado', 'rechazado'])->sum('amount'), 0) }}
                                </span>
                            </div>
                        </div>
                        <div class="mt-4 pt-4 border-t border-gray-50 dark:border-gray-700">
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                                @foreach($analytics['status_breakdown'] as $s)
                                    <div class="flex items-center gap-1.5 {{ $s->amount == 0 ? 'opacity-30' : 'opacity-100' }}">
                                        <span class="w-2 h-2 rounded-full shadow-sm" style="background-color: 
                                            @if($s->status == 'aprobado') #64b032 
                                            @elseif($s->status == 'rechazado') #ff3000 
                                            @elseif($s->status == 'requiere_correccion') #ffa608
                                            @elseif($s->status == 'aprobado_director') #3385fa
                                            @elseif($s->status == 'aprobado_control') #66a3fb
                                            @elseif($s->status == 'aprobado_ejecutivo') #004bb3
                                            @elseif($s->status == 'aprobado_cxp') #003380
                                            @elseif($s->status == 'aprobado_direccion') #ff8c00
                                            @else #0066f9 @endif"></span>
                                        <span class="text-[8px] font-black text-gray-500 dark:text-gray-400 uppercase truncate" title="{{ $s->label }}">
                                            @if($s->status == 'aprobado') Pagado 
                                            @elseif($s->status == 'aprobado_director') N2: Dir
                                            @elseif($s->status == 'aprobado_control') N3: Ctrl
                                            @elseif($s->status == 'aprobado_ejecutivo') N4: Ejec.
                                            @elseif($s->status == 'aprobado_cxp') N5: Subdir.
                                            @elseif($s->status == 'aprobado_direccion') N6: Direcc.
                                            @elseif($s->status == 'pendiente') N1: Pend.
                                            @elseif($s->status == 'rechazado') Recha.
                                            @elseif($s->status == 'requiere_correccion') Correg.
                                            @else {{ $s->label }} @endif
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Row 3: Metrics Portfolio (Visible only to Full Access) -->
                @if($hasFullAccess)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 mb-8">
                    <!-- Category Matrix -->
                    <div class="bg-white dark:bg-gray-800 p-8 rounded-[2rem] shadow-sm border border-gray-100 dark:border-gray-700 flex flex-col">
                        <h4 class="text-gray-900 dark:text-white font-black text-lg mb-6 uppercase tracking-tighter">¿En qué gastamos?</h4>
                        <div class="flex-1 space-y-4">
                            @foreach($analytics['category_breakdown']->take(5) as $cat)
                                @php
                                    $maxCat = $analytics['category_breakdown']->max('amount') ?: 1;
                                    $percent = ($cat->amount / $maxCat) * 100;
                                @endphp
                                <div>
                                    <div class="flex justify-between items-center mb-1">
                                        <span class="text-[10px] font-black uppercase text-gray-500 dark:text-gray-400 truncate w-2/3">{{ $cat->category }}</span>
                                        <span class="text-[10px] font-black text-gray-900 dark:text-white">${{ number_format($cat->amount, 0) }}</span>
                                    </div>
                                    <div class="w-full bg-gray-50 dark:bg-gray-900 rounded-full h-1 overflow-hidden">
                                        <div class="bg-indigo-600 h-full rounded-full" style="width: {{ $percent }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <!-- Top Spenders -->
                    <div class="bg-white dark:bg-gray-800 p-8 rounded-[2rem] shadow-sm border border-gray-100 dark:border-gray-700">
                        <h4 class="text-gray-900 dark:text-white font-black text-lg mb-6 uppercase tracking-tighter">Top Solicitantes</h4>
                        <div class="space-y-5">
                            @foreach($analytics['top_spenders'] as $spender)
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 bg-indigo-50 dark:bg-indigo-900/40 rounded-full flex items-center justify-center text-indigo-600 font-black text-xs mr-3">
                                            {{ substr($spender->user->name ?? '?', 0, 1) }}
                                        </div>
                                        <div>
                                            <p class="text-xs font-black text-gray-900 dark:text-white truncate">{{ explode(' ', $spender->user->name ?? 'Usuario')[0] }}</p>
                                            <p class="text-[9px] text-gray-400 font-bold">{{ $spender->count }} reembolsos</p>
                                        </div>
                                    </div>
                                    <span class="text-xs font-black text-gray-900 dark:text-white">${{ number_format($spender->amount, 0) }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Approval Efficiency -->
                    <div class="bg-gray-900 rounded-[2rem] p-8 text-white relative overflow-hidden group">
                        <div class="relative z-10">
                            <h4 class="text-xl font-black mb-1 uppercase tracking-tighter">Velocidad de Flujo</h4>
                            <p class="text-[10px] text-indigo-300 font-bold mb-8 uppercase tracking-widest">¿Qué tan rápido aprobamos?</p>
                            
                            <div class="space-y-6">
                                @forelse($analytics['avg_time_by_cost_center'] as $cc)
                                    <div>
                                        <div class="flex justify-between items-end mb-2">
                                            <span class="text-[10px] font-black text-gray-400 truncate uppercase">{{ $cc->costCenter->code ?? 'CC' }}</span>
                                            <span class="text-xs font-black text-indigo-400">{{ number_format($cc->avg_hours / 24, 1) }} d</span>
                                        </div>
                                        <div class="w-full bg-white/10 rounded-full h-1">
                                            <div class="bg-indigo-500 h-full rounded-full transition-all duration-1000" style="width: {{ min(($cc->avg_hours / 24) * 5, 100) }}%"></div>
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-xs text-center text-gray-500 py-10">Sin datos históricos suficientes</p>
                                @endforelse
                            </div>
                        </div>
                        <svg class="absolute -right-10 -bottom-10 w-40 h-40 text-white/5 group-hover:rotate-12 transition-transform duration-1000" fill="currentColor" viewBox="0 0 24 24"><path d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    </div>

                    <!-- Tax Snapshot -->
                    <div class="bg-emerald-600 rounded-[2rem] p-8 text-white">
                        <h4 class="text-xl font-black mb-1 uppercase tracking-tighter text-emerald-100">Capital Recuperable</h4>
                        <p class="text-[10px] text-emerald-200 font-bold mb-8 uppercase tracking-widest">Identificación de Gastos Fiscales</p>
                        
                        <div class="mb-8">
                            <h5 class="text-4xl font-black">${{ number_format($analytics['tax_summary']->taxes ?? 0, 2) }}</h5>
                            <p class="text-[10px] font-bold text-emerald-100 mt-1 uppercase tracking-widest">Total IVA identificado en XMLs</p>
                        </div>

                        <div class="space-y-3">
                            <div class="flex justify-between text-[10px] font-black border-b border-emerald-500/30 pb-2">
                                <span class="text-emerald-100 uppercase">Subtotal</span>
                                <span>${{ number_format($analytics['tax_summary']->subtotal ?? 0, 2) }}</span>
                            </div>
                            <div class="flex justify-between text-[10px] font-black border-b border-emerald-500/30 pb-2">
                                <span class="text-emerald-100 uppercase">Impuestos</span>
                                <span>${{ number_format($analytics['tax_summary']->taxes ?? 0, 2) }}</span>
                            </div>
                            <div class="flex justify-between text-[10px] font-black pt-2">
                                <span class="text-emerald-100 uppercase">Impacto Total</span>
                                <span class="text-xl">${{ number_format($analytics['tax_summary']->total ?? 0, 2) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            </div><!-- End Insights Section -->

            <!-- Bottom Section Grid (Table + Sidebar) -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-10">
                <!-- Recent Activity Table (2 Columns) -->
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-white dark:bg-gray-800 rounded-[2rem] shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                        <div class="px-8 py-6 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center">
                            <h3 class="text-lg font-black text-gray-900 dark:text-white uppercase tracking-tighter">Últimos Movimientos</h3>
                            <a href="{{ route('reimbursements.index') }}" class="text-[10px] font-black text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 uppercase tracking-widest bg-indigo-50 dark:bg-indigo-900/30 px-4 py-2 rounded-full transition-all">Ver Historial Completo &rarr;</a>
                        </div>
                        
                        @if($recentReimbursements->count() > 0)
                            <div class="overflow-x-auto text-sm">
                                <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700">
                                    <thead class="bg-gray-50/50 dark:bg-gray-900/50">
                                        <tr>
                                            <th class="px-8 py-4 text-left font-black text-gray-400 uppercase tracking-widest text-[10px]">Identificador</th>
                                            <th class="px-8 py-4 text-left font-black text-gray-400 uppercase tracking-widest text-[10px]">Solicitante / C.C.</th>
                                            <th class="px-8 py-4 text-left font-black text-gray-400 uppercase tracking-widest text-[10px]">Importe</th>
                                            <th class="px-8 py-4 text-left font-black text-gray-400 uppercase tracking-widest text-[10px]">Estatus Actual</th>
                                            <th class="px-8 py-4 text-right font-black text-gray-400 uppercase tracking-widest text-[10px]"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-50 dark:divide-gray-700">
                                        @foreach($recentReimbursements as $reimbursement)
                                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-900/10 transition-colors cursor-pointer" onclick="window.location='{{ route('reimbursements.show', $reimbursement) }}'">
                                                <td class="px-8 py-5">
                                                    <div class="font-black text-gray-900 dark:text-white">{{ $reimbursement->folio ?? Str::limit($reimbursement->uuid, 8) ?? 'S/F' }}</div>
                                                    <div class="text-[10px] text-indigo-500 font-black uppercase tracking-wider">{{ str_replace('_', ' ', $reimbursement->type) }}</div>
                                                </td>
                                                <td class="px-8 py-5">
                                                    <div class="text-gray-900 dark:text-white font-bold">{{ $reimbursement->user->name ?? 'N/A' }}</div>
                                                    <div class="text-[10px] text-gray-400 uppercase font-black tracking-widest">{{ $reimbursement->costCenter->code ?? 'S/C' }}</div>
                                                </td>
                                                <td class="px-8 py-5 font-black text-gray-900 dark:text-white">
                                                    ${{ number_format($reimbursement->total, 2) }}
                                                </td>
                                                <td class="px-8 py-5">
                                                    <span class="px-3 py-1 inline-flex text-[9px] leading-4 font-black rounded-full uppercase tracking-widest
                                                        {{ $reimbursement->status === 'aprobado' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300' : '' }}
                                                        {{ $reimbursement->status === 'rechazado' ? 'bg-rose-100 text-rose-800 dark:bg-rose-900/50 dark:text-rose-300' : '' }}
                                                        {{ $reimbursement->status === 'requiere_correccion' ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300' : '' }}
                                                        {{ in_array($reimbursement->status, ['pendiente', 'aprobado_director', 'aprobado_control', 'aprobado_ejecutivo', 'aprobado_cxp', 'aprobado_direccion']) ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/50 dark:text-indigo-300' : '' }}
                                                    ">
                                                        @if($reimbursement->status === 'aprobado') Pagado 
                                                        @elseif($reimbursement->status === 'aprobado_cxp') Aprob. Subdir.
                                                        @elseif($reimbursement->status === 'aprobado_direccion') Aprob. Direcc.
                                                        @else {{ str_replace('_', ' ', $reimbursement->status) }} @endif
                                                    </span>
                                                </td>
                                                <td class="px-8 py-5 text-right whitespace-nowrap">
                                                    <span class="text-indigo-600 dark:text-indigo-400">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @if($recentReimbursements instanceof \Illuminate\Pagination\LengthAwarePaginator)
                            <div class="p-6 bg-gray-50/50 border-t border-gray-100 dark:bg-gray-900/50 dark:border-gray-700">
                                {{ $recentReimbursements->links() }}
                            </div>
                            @endif
                        @else
                            <div class="p-16 text-center">
                                <div class="bg-gray-50 dark:bg-gray-900/50 w-20 h-20 rounded-[2rem] flex items-center justify-center mx-auto mb-6">
                                    <svg class="w-10 h-10 text-gray-200 dark:text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                </div>
                                <p class="text-gray-500 dark:text-gray-400 font-bold">No se han encontrado registros recientes.</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Side Widget Area (1 Column) -->
                <div class="space-y-8">
                    <!-- Unread Notifications -->
                    <div class="bg-white dark:bg-gray-800 rounded-[2rem] shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                        <div class="px-8 py-6 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center">
                            <h3 class="text-lg font-black text-gray-900 dark:text-white uppercase tracking-tighter">Alertas</h3>
                            <a href="{{ route('notifications.index') }}" class="text-[10px] font-black text-indigo-600 uppercase tracking-widest underline decoration-2 underline-offset-4">Ver Todo</a>
                        </div>
                        <div class="p-4 space-y-2">
                            @forelse($notifications as $notification)
                                <a href="{{ route('notifications.mark_read', $notification->id) }}" class="flex items-start p-4 hover:bg-gray-50 dark:hover:bg-gray-900/50 rounded-2xl transition-all group border border-transparent hover:border-gray-100 dark:hover:border-gray-700">
                                    <div class="flex-shrink-0 w-10 h-10 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded-xl flex items-center justify-center mr-4 group-hover:scale-110 transition-transform">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-[11px] font-black text-gray-900 dark:text-white uppercase tracking-tighter">{{ $notification->data['title'] ?? 'Recordatorio' }}</p>
                                        <p class="text-[10px] text-gray-500 dark:text-gray-400 font-medium line-clamp-2 mt-1">{{ Str::limit($notification->data['message'] ?? '', 80) }}</p>
                                        <p class="text-[9px] text-gray-300 font-black uppercase mt-2">{{ $notification->created_at->diffForHumans() }}</p>
                                    </div>
                                </a>
                            @empty
                                <div class="py-12 text-center">
                                    <div class="w-12 h-12 bg-gray-50 dark:bg-gray-900 rounded-full flex items-center justify-center mx-auto mb-4">
                                        <svg class="w-6 h-6 text-gray-200 dark:text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"></path></svg>
                                    </div>
                                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Sin notificaciones</p>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <!-- App Shortcuts -->
                    <div class="bg-gradient-to-br from-indigo-600 to-indigo-800 rounded-[2.5rem] p-8 text-white shadow-2xl shadow-indigo-600/40 relative overflow-hidden group">
                        <div class="relative z-10">
                            <h4 class="text-2xl font-black mb-4 leading-none tracking-tighter">¿Nuevo Gasto?</h4>
                            <p class="text-indigo-100 text-xs mb-8 font-bold leading-relaxed opacity-80 group-hover:opacity-100 transition-opacity">Sube tus archivos XML y PDF juntos para automatizar tu reembolso en segundos.</p>
                            @if(!Auth::user()->isAdminView())
                            <a href="{{ route('reimbursements.create') }}" class="inline-flex items-center justify-center w-full px-6 py-4 bg-white text-indigo-600 font-black rounded-2xl text-xs uppercase tracking-widest hover:bg-indigo-50 transition-all transform hover:-translate-y-1 shadow-lg">
                                Iniciar Solicitud &rarr;
                            </a>
                            @else
                            <a href="{{ route('reimbursements.index') }}" class="inline-flex items-center justify-center w-full px-6 py-4 bg-white text-indigo-600 font-black rounded-2xl text-xs uppercase tracking-widest hover:bg-indigo-50 transition-all transform hover:-translate-y-1 shadow-lg">
                                Ver Catálogo &rarr;
                            </a>
                            @endif
                        </div>
                        <svg class="absolute -right-12 -bottom-12 w-48 h-48 text-white/5 opacity-40 group-hover:scale-110 transition-transform duration-700" fill="currentColor" viewBox="0 0 24 24"><path d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                    </div>
                </div>
            </div><!-- End Grid -->
        </div><!-- End Max-Width Container -->
    </div><!-- End Py-12 -->

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('dynamicsChart');
            if (ctx) {
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: {!! json_encode($analytics['daily_activity']->pluck('date')->map(fn($d) => \Carbon\Carbon::parse($d)->format('d M'))) !!},
                        datasets: [{
                            label: 'Gasto Diario ($)',
                            data: {!! json_encode($analytics['daily_activity']->pluck('amount')) !!},
                            borderColor: '#4f46e5',
                            backgroundColor: 'rgba(79, 70, 229, 0.1)',
                            borderWidth: 4,
                            fill: true,
                            tension: 0.4,
                            pointRadius: 4,
                            pointBackgroundColor: '#4f46e5'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: { color: 'rgba(0,0,0,0.05)' },
                                ticks: { font: { size: 10, weight: '900' } }
                            },
                            x: {
                                grid: { display: false },
                                ticks: { font: { size: 10, weight: '700' } }
                            }
                        }
                    }
                });
            }

            // Status Doughnut Chart
            const statusCtx = document.getElementById('statusDoughnutChart');
            if (statusCtx) {
                const statusData = {!! json_encode($analytics['status_breakdown']) !!};
                const detailedItems = {!! json_encode($analytics['detailed_items']) !!};
                
                const statusConfig = {
                    'aprobado': { label: 'Pagado', color: '#64b032' },
                    'rechazado': { label: 'Rechazado', color: '#ff3000' },
                    'requiere_correccion': { label: 'Corregir', color: '#ffa608' },
                    'pendiente': { label: 'N1: Pendiente', color: '#0066f9' },
                    'aprobado_director': { label: 'N2: Aprob. Dir', color: '#3385fa' },
                    'aprobado_control': { label: 'N3: Aprob. Ctrl', color: '#66a3fb' },
                    'aprobado_ejecutivo': { label: 'Aprob. Ejecut.', color: '#004bb3' },
                    'aprobado_cxp': { label: 'Aprob. Subdir.', color: '#003380' },
                    'aprobado_direccion': { label: 'Aprob. Direcc.', color: '#ff8c00' }
                };

                const labels = [];
                const counts = [];
                const amounts = [];
                const colors = [];

                // Filter out statuses with zero items
                statusData.forEach(item => {
                    if (item.count > 0) {
                        const config = statusConfig[item.status] || { label: item.status, color: '#9ca3af' };
                        labels.push(config.label);
                        counts.push(item.count);
                        amounts.push(item.amount);
                        colors.push(config.color);
                    }
                });

                new Chart(statusCtx, {
                    type: 'doughnut',
                    data: {
                        labels: labels,
                        amounts: amounts, // Custom property for tooltips
                        datasets: [{
                            data: counts, // USE COUNT FOR PROPORTIONS
                            backgroundColor: colors,
                            borderWidth: 4,
                            borderColor: document.documentElement.classList.contains('dark') ? '#1f2937' : '#ffffff',
                            hoverOffset: 20,
                            borderRadius: 10,
                            spacing: 5
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '80%',
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const label = context.label || '';
                                        const count = context.raw;
                                        const amount = context.chart.data.amounts[context.dataIndex];
                                        const amountFormatted = new Intl.NumberFormat('es-MX', { 
                                            style: 'currency', 
                                            currency: 'MXN' 
                                        }).format(amount);
                                        return `${label}: ${count} solicitudes (${amountFormatted})`;
                                    }
                                }
                            }
                        }
                    }
                });
            }
        });
    </script>
    @endpush
</x-app-layout>

