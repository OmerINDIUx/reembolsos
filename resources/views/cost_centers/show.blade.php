<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <nav class="flex mb-2" aria-label="Breadcrumb">
                    <ol class="inline-flex items-center space-x-1 md:space-x-3 text-xs font-medium text-gray-500 dark:text-gray-400">
                        <li class="inline-flex items-center">
                            <a href="{{ route('cost_centers.index') }}" class="hover:text-indigo-600 dark:hover:text-indigo-400">Centros de Costos</a>
                        </li>
                        <li>
                            <div class="flex items-center">
                                <svg class="w-3 h-3 mx-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path></svg>
                                <span>Dashboard {{ $costCenter->code }}</span>
                            </div>
                        </li>
                    </ol>
                </nav>
                <h2 class="font-black text-3xl text-gray-900 dark:text-white leading-tight uppercase tracking-tighter">
                    {{ $costCenter->name }}
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 font-medium">Código: <span class="font-bold text-indigo-600 dark:text-indigo-400">{{ $costCenter->code }}</span></p>
            </div>
            
            <div class="flex items-center gap-3">
                @if(Auth::user()->isAdmin())
                <a href="{{ route('cost_centers.edit', $costCenter) }}" class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-black uppercase tracking-widest text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition-all shadow-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                    Configurar Flujo
                </a>
                @endif
                <a href="{{ route('cost_centers.index') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-black uppercase tracking-widest rounded-xl transition-all shadow-lg hover:shadow-indigo-500/30">
                    &larr; Volver
                </a>
            </div>
        </div>
    </x-slot>
    <style>[x-cloak] { display: none !important; }</style>

    <div class="py-12" x-data="{ openRenewModal: false }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            
            <!-- Budget & Performance Statistics -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Total Budget allocation -->
                <div class="bg-gray-900 p-8 rounded-[2.5rem] shadow-xl text-white relative overflow-hidden group">
                    <div class="relative z-10">
                        <p class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-400 mb-2">Presupuesto Total Asignado</p>
                        <h4 class="text-4xl font-black leading-none">${{ number_format($costCenter->budget, 2) }}</h4>
                        <div class="mt-6 flex items-center justify-between">
                            <span class="text-[10px] font-bold text-gray-500 uppercase">Estado Global</span>
                            @if(Auth::user()->isAdmin() || Auth::user()->isControlObra())
                            <button @click="openRenewModal = true" class="bg-indigo-600 hover:bg-indigo-500 text-white px-3 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-widest transition-all shadow-lg shadow-indigo-500/20">
                                + Renovar
                            </button>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Remaining Budget -->
                @php
                    $totalSpent = $stats['approved_amount'] + $stats['pending_amount'];
                    $remainingBudget = $costCenter->budget - $totalSpent;
                    $percentageSpent = $costCenter->budget > 0 ? ($totalSpent / $costCenter->budget) * 100 : 0;
                @endphp
                <div class="bg-white dark:bg-gray-800 p-8 rounded-[2.5rem] shadow-sm border border-gray-100 dark:border-gray-700 relative overflow-hidden group">
                    <div class="relative z-10">
                        <p class="text-[10px] font-black uppercase tracking-[0.2em] {{ $remainingBudget < 0 ? 'text-red-500' : 'text-emerald-500' }} mb-2">Presupuesto Disponible</p>
                        <h4 class="text-4xl font-black text-gray-900 dark:text-white leading-none">${{ number_format($remainingBudget, 2) }}</h4>
                        
                        <div class="mt-6">
                            <div class="flex justify-between items-center mb-1 text-[10px] font-bold uppercase">
                                <span class="text-gray-400">Consumo: {{ number_format($percentageSpent, 1) }}%</span>
                                <span class="text-gray-900 dark:text-white font-black">${{ number_format($totalSpent, 2) }}</span>
                            </div>
                            <div class="w-full bg-gray-100 dark:bg-gray-900 h-2 rounded-full overflow-hidden">
                                <div class="h-full {{ $percentageSpent > 90 ? 'bg-red-500' : ($percentageSpent > 70 ? 'bg-amber-500' : 'bg-emerald-500') }} transition-all duration-1000" style="width: {{ min(100, $percentageSpent) }}%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Efficiency / Flow Stats -->
                <div class="bg-indigo-600 p-8 rounded-[2.5rem] shadow-xl shadow-indigo-500/20 text-white relative overflow-hidden group">
                    <div class="absolute -right-4 -bottom-4 opacity-10 group-hover:scale-110 transition-transform duration-500">
                        <svg class="w-32 h-32 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    </div>
                    <div class="relative z-10">
                        <p class="text-[10px] font-black uppercase tracking-[0.2em] text-indigo-200 mb-1">Velocidad de Flujo</p>
                        <h4 class="text-4xl font-black leading-none">{{ number_format($stats['avg_approval_days'], 1) }} d</h4>
                        <p class="text-xs font-bold text-indigo-100 mt-2 opacity-80 mb-4">Promedio aprobación</p>
                        
                        <div class="flex -space-x-2 overflow-hidden">
                            @foreach(['director', 'controlObra', 'directorEjecutivo', 'accountant', 'direccion', 'tesoreria'] as $role)
                                @if($costCenter->$role)
                                    <div class="inline-block h-8 w-8 rounded-full ring-2 ring-indigo-600 bg-indigo-400 flex items-center justify-center text-[10px] font-black uppercase text-white shadow-lg" title="{{ $costCenter->$role->name }}">
                                        {{ substr($costCenter->$role->name, 0, 1) }}
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <!-- Operational Stats -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Pending Count -->
                <a href="{{ route('reimbursements.index', ['cost_center_id' => $costCenter->id, 'tab' => 'management']) }}" class="bg-white dark:bg-gray-800 p-6 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 relative overflow-hidden group hover:shadow-lg transition-all">
                    <div class="relative z-10 flex items-center">
                        <div class="w-12 h-12 rounded-2xl bg-amber-50 dark:bg-amber-900/30 flex items-center justify-center text-amber-500 mr-4">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-0.5">En Proceso</p>
                            <h4 class="text-xl font-black text-gray-900 dark:text-white">{{ $stats['pending_count'] }} <span class="text-xs text-gray-400">items</span></h4>
                            <p class="text-[10px] font-bold text-amber-500">${{ number_format($stats['pending_amount'], 2) }}</p>
                        </div>
                    </div>
                </a>

                <!-- Approved History -->
                <a href="{{ route('reimbursements.index', ['cost_center_id' => $costCenter->id, 'tab' => 'global_history', 'status' => 'aprobado']) }}" class="bg-white dark:bg-gray-800 p-6 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 relative overflow-hidden group hover:shadow-lg transition-all">
                    <div class="relative z-10 flex items-center">
                        <div class="w-12 h-12 rounded-2xl bg-emerald-50 dark:bg-emerald-900/30 flex items-center justify-center text-emerald-500 mr-4">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-0.5">Pagado (Histórico)</p>
                            <h4 class="text-xl font-black text-gray-900 dark:text-white">${{ number_format($stats['approved_amount'], 0) }}</h4>
                            <p class="text-[10px] font-bold text-emerald-500 uppercase tracking-widest">Aprobados: {{ $stats['approved_count'] }}</p>
                        </div>
                    </div>
                </a>

                <!-- Corrections & Rejections -->
                <div class="bg-white dark:bg-gray-800 p-6 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 relative overflow-hidden">
                    <div class="relative z-10 flex items-center h-full">
                        <div class="w-12 h-12 rounded-2xl bg-rose-50 dark:bg-rose-900/30 flex items-center justify-center text-rose-500 mr-4">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        </div>
                        <div class="grid grid-cols-2 gap-4 flex-1">
                            <a href="{{ route('reimbursements.index', ['cost_center_id' => $costCenter->id, 'status' => 'requiere_correccion']) }}" class="hover:text-amber-600 transition-colors">
                                <p class="text-[9px] font-black uppercase text-gray-400">Corrección</p>
                                <h4 class="text-lg font-black text-gray-900 dark:text-white">{{ $stats['correction_count'] }}</h4>
                            </a>
                            <a href="{{ route('reimbursements.index', ['cost_center_id' => $costCenter->id, 'status' => 'rechazado']) }}" class="hover:text-rose-600 transition-colors">
                                <p class="text-[9px] font-black uppercase text-gray-400">Rechazos</p>
                                <h4 class="text-lg font-black text-gray-900 dark:text-white">{{ $stats['rejected_count'] }}</h4>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <!-- Bottleneck Analysis (Progress) -->
                <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-[2.5rem] shadow-sm border border-gray-100 dark:border-gray-700 p-8">
                    <h3 class="text-xl font-black text-gray-900 dark:text-white uppercase tracking-tighter mb-8 flex items-center">
                        <svg class="w-5 h-5 mr-3 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                        Análisis de Embotellamientos (Flujo Activo)
                    </h3>
                    
                    <div class="space-y-8">
                        @forelse($costCenter->approvalSteps as $step)
                            @php
                                $stepStats = $stepBreakdown->firstWhere('current_step_id', $step->id);
                                $count = $stepStats->count ?? 0;
                                $amount = $stepStats->amount ?? 0;
                                $maxCount = $stepBreakdown->max('count') ?: 1;
                                $width = ($count / $maxCount) * 100;
                            @endphp
                            <div class="relative">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center">
                                        <span class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-xs font-black text-indigo-600 dark:text-indigo-400 mr-4">
                                            {{ $step->order }}
                                        </span>
                                        <div>
                                            <p class="text-xs font-black text-gray-900 dark:text-white uppercase tracking-wider">{{ $step->name }}</p>
                                            <p class="text-[10px] text-gray-400 font-bold">{{ $step->user->name ?? 'Sin asignar' }}</p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-sm font-black text-gray-900 dark:text-white">{{ $count }} <span class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">solicitudes</span></div>
                                        @if($amount > 0)
                                            <div class="text-[10px] font-black text-indigo-600 dark:text-indigo-400">${{ number_format($amount, 2) }}</div>
                                        @endif
                                    </div>
                                </div>
                                <div class="w-full bg-gray-50 dark:bg-gray-900 h-2.5 rounded-full overflow-hidden flex">
                                    <div class="h-full bg-indigo-600 transition-all duration-1000 ease-out rounded-full {{ $count > 0 ? 'shadow-[0_0_10px_rgba(79,70,229,0.3)]' : 'opacity-10' }}" style="width: {{ $count > 0 ? max($width, 5) : 0 }}%"></div>
                                </div>
                            </div>
                        @empty
                            <div class="py-12 text-center text-gray-400 bg-gray-50 dark:bg-gray-900/50 rounded-3xl border border-dashed border-gray-200 dark:border-gray-700">
                                <p class="text-xs font-black uppercase tracking-widest">Flujo no configurado</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Category Matrix -->
                <div class="bg-white dark:bg-gray-800 rounded-[2.5rem] shadow-sm border border-gray-100 dark:border-gray-700 p-8 flex flex-col">
                    <h3 class="text-xl font-black text-gray-900 dark:text-white uppercase tracking-tighter mb-8">
                        Matriz de Gastos
                    </h3>
                    
                    <div class="flex-1 space-y-6">
                        @forelse($categoryBreakdown as $cat)
                            @php
                                $maxAmount = $categoryBreakdown->max('amount') ?: 1;
                                $percent = ($cat->amount / $maxAmount) * 100;
                            @endphp
                            <div>
                                <div class="flex justify-between items-center mb-2">
                                    <span class="text-[10px] font-black uppercase text-gray-500 dark:text-gray-400 truncate w-2/3">{{ $cat->category }}</span>
                                    <span class="text-xs font-black text-gray-900 dark:text-white">${{ number_format($cat->amount, 0) }}</span>
                                </div>
                                <div class="w-full bg-gray-50 dark:bg-gray-900 rounded-full h-1.5 overflow-hidden">
                                    <div class="bg-emerald-500 h-full rounded-full transition-all duration-700" style="width: {{ $percent }}%"></div>
                                </div>
                            </div>
                        @empty
                            <p class="text-xs text-center text-gray-400 py-20">Sin datos de gastos</p>
                        @endforelse
                    </div>
                    
                    <div class="mt-8 pt-6 border-t border-gray-50 dark:border-gray-700">
                        <div class="flex justify-between items-center text-[10px] font-black text-gray-400 uppercase tracking-widest">
                            <span>Total Comprobantes</span>
                            <span class="text-gray-900 dark:text-white">{{ $categoryBreakdown->sum('count') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Recent Activity -->
                <div class="bg-white dark:bg-gray-800 rounded-[2.5rem] shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden flex flex-col">
                    <div class="px-8 py-6 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center bg-gray-50/50 dark:bg-gray-900/10">
                        <h3 class="text-lg font-black text-gray-900 dark:text-white uppercase tracking-tighter">Actividad Reciente</h3>
                    </div>
                    <div class="overflow-x-auto flex-1 text-sm">
                        @if($recentReimbursements->count() > 0)
                        <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700">
                            <thead>
                                <tr class="bg-gray-50/30 dark:bg-gray-900/10">
                                    <th class="px-8 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest">Folio</th>
                                    <th class="px-8 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest">Importe</th>
                                    <th class="px-8 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest">Estatus</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50 dark:divide-gray-700">
                                @foreach($recentReimbursements as $r)
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-900/10 cursor-pointer" onclick="window.location='{{ route('reimbursements.show', $r) }}'">
                                    <td class="px-8 py-4">
                                        <div class="font-black text-gray-900 dark:text-white uppercase">{{ $r->folio }}</div>
                                        <div class="text-[9px] text-gray-400 font-bold italic">{{ $r->created_at->format('d/m/Y') }}</div>
                                    </td>
                                    <td class="px-8 py-4 font-black text-gray-900 dark:text-white">${{ number_format($r->total, 2) }}</td>
                                    <td class="px-8 py-4 text-xs font-bold uppercase tracking-wider text-gray-500">
                                        {{ $r->status }}
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                        @else
                            <div class="flex flex-col items-center justify-center p-20">
                                <p class="text-xs font-black uppercase text-gray-300 tracking-widest">Sin registros recientes</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Budget Renewal History -->
                <div class="bg-white dark:bg-gray-800 rounded-[2.5rem] shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden flex flex-col">
                    <div class="px-8 py-6 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center bg-gray-50/50 dark:bg-gray-900/10">
                        <h3 class="text-lg font-black text-gray-900 dark:text-white uppercase tracking-tighter">Historial de Budget</h3>
                    </div>
                    <div class="overflow-x-auto flex-1 text-sm">
                        <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700">
                            <thead>
                                <tr class="bg-gray-50/30 dark:bg-gray-900/10">
                                    <th class="px-8 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest">Fecha</th>
                                    <th class="px-8 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest">Concepto</th>
                                    <th class="px-8 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest">Importe</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50 dark:divide-gray-700">
                                @forelse($budgetRenewals as $renewal)
                                <tr>
                                    <td class="px-8 py-4">
                                        <div class="font-black text-gray-900 dark:text-white">{{ \Carbon\Carbon::parse($renewal->renewal_date)->format('d/m/Y') }}</div>
                                        <div class="text-[9px] text-gray-400 font-bold uppercase">Por: {{ $renewal->user->name }}</div>
                                    </td>
                                    <td class="px-8 py-4">
                                        <div class="text-[10px] font-bold text-gray-600 dark:text-gray-400 leading-tight uppercase">{{ $renewal->description ?: 'Renovación de presupuesto' }}</div>
                                    </td>
                                    <td class="px-8 py-4">
                                        <div class="text-sm font-black text-emerald-600 dark:text-emerald-400">+${{ number_format($renewal->amount, 2) }}</div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3" class="px-8 py-10 text-center text-gray-400 text-xs font-bold uppercase tracking-widest italic">No hay historial de renovaciones</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    <div x-show="openRenewModal" x-cloak>
        <template x-if="openRenewModal">
            <div class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm" x-cloak x-transition>
                <div @click.away="openRenewModal = false" class="bg-white dark:bg-gray-800 rounded-[2rem] shadow-2xl w-full max-w-md overflow-hidden animate-in zoom-in duration-300">
                    <div class="px-8 py-6 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center bg-gray-50 dark:bg-gray-900/20">
                        <h3 class="text-xl font-black text-gray-900 dark:text-white uppercase tracking-tighter">Renovar Presupuesto</h3>
                        <button @click="openRenewModal = false" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>
                    
                    <form action="{{ route('cost_centers.renew_budget', $costCenter) }}" method="POST" class="p-8 space-y-6">
                        @csrf
                        <div>
                            <label for="amount" class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Importe a Añadir *</label>
                            <div class="relative">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 font-bold">$</span>
                                <input type="number" step="0.01" name="amount" id="amount" class="w-full border-gray-200 dark:border-gray-700 dark:bg-gray-900 rounded-2xl shadow-sm focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all font-bold py-4 pl-8" required placeholder="0.00">
                            </div>
                        </div>

                        <div>
                            <label for="renewal_date" class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Fecha de Renovación *</label>
                            <input type="date" name="renewal_date" id="renewal_date" value="{{ date('Y-m-d') }}" class="w-full border-gray-200 dark:border-gray-700 dark:bg-gray-900 rounded-2xl shadow-sm focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all font-bold py-4 uppercase" required>
                        </div>

                        <div>
                            <label for="description" class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Referencia / Descripción</label>
                            <textarea name="description" id="description" rows="2" class="w-full border-gray-200 dark:border-gray-700 dark:bg-gray-900 rounded-2xl shadow-sm focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all font-medium py-4" placeholder="Ej: Renovación Mayo 2024, Ref 123..."></textarea>
                        </div>

                        <div class="pt-4">
                            <button type="submit" class="w-full py-4 bg-indigo-600 hover:bg-indigo-700 text-white font-black uppercase tracking-widest rounded-2xl shadow-lg shadow-indigo-500/30 transition-all">
                                Confirmar Renovación
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </template>
    </div>
</div>
    @push('scripts')

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Trend Chart
            const ctxTrend = document.getElementById('monthlyTrendChart');
            if (ctxTrend) {
                const labels = {!! json_encode($monthlyTrend->pluck('month')->map(fn($m) => \Carbon\Carbon::parse($m . '-01')->format('M Y'))) !!};
                const data = {!! json_encode($monthlyTrend->pluck('amount')) !!};

                new Chart(ctxTrend, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Gasto Mensual',
                            data: data,
                            backgroundColor: '#4f46e5',
                            borderRadius: 8,
                            hoverBackgroundColor: '#4338ca',
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)', drawBorder: false }, ticks: { font: { size: 9, weight: '700' } } },
                            x: { grid: { display: false }, ticks: { font: { size: 9, weight: '700' } } }
                        }
                    }
                });
            }

            // Status Doughnut Chart
            const ctxStatus = document.getElementById('statusDoughnutChart');
            if (ctxStatus) {
                const statusData = {!! json_encode($statusBreakdown) !!};
                const statusConfig = {
                    'aprobado': { label: 'Pagado', color: '#10b981' },
                    'rechazado': { label: 'Rechazado', color: '#ef4444' },
                    'requiere_correccion': { label: 'Corregir', color: '#f59e0b' },
                    'pendiente': { label: 'Pendiente', color: '#6366f1' },
                    'aprobado_director': { label: 'Aprob. Dir', color: '#818cf8' },
                    'aprobado_control': { label: 'Aprob. Ctrl', color: '#a5b4fc' },
                    'aprobado_ejecutivo': { label: 'Aprob. Ejecut.', color: '#4338ca' },
                    'aprobado_cxp': { label: 'Aprob. Subdir.', color: '#3730a3' },
                    'aprobado_direccion': { label: 'Aprob. Direcc.', color: '#fbbf24' }
                };

                const labels = [];
                const values = [];
                const colors = [];

                statusData.forEach(item => {
                    const config = statusConfig[item.status] || { label: item.status, color: '#9ca3af' };
                    labels.push(config.label);
                    values.push(item.amount);
                    colors.push(config.color);
                });

                new Chart(ctxStatus, {
                    type: 'doughnut',
                    data: {
                        labels: labels,
                        datasets: [{
                            data: values,
                            backgroundColor: colors,
                            borderWidth: 0,
                            cutout: '75%',
                            borderRadius: 5,
                            spacing: 5
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return `${context.label}: $${new Intl.NumberFormat().format(context.raw)}`;
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
