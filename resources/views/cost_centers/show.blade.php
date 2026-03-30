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

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            
            <!-- Summary Stats -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Pending Count -->
                <a href="{{ route('reimbursements.index', ['cost_center_id' => $costCenter->id, 'tab' => 'management']) }}" class="bg-white dark:bg-gray-800 p-6 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 relative overflow-hidden group hover:border-indigo-300 transition-all">
                    <div class="relative z-10">
                        <p class="text-[10px] font-black uppercase tracking-[0.2em] text-amber-500 mb-1">Pendientes Pago / Tránsito</p>
                        <h4 class="text-4xl font-black text-gray-900 dark:text-white leading-none">{{ $stats['pending_count'] }}</h4>
                        <p class="text-xs font-bold text-gray-400 mt-2">Monto: <span class="text-gray-900 dark:text-white">${{ number_format($stats['pending_amount'], 2) }}</span></p>
                    </div>
                    <div class="absolute -right-4 -bottom-4 opacity-5 group-hover:scale-110 transition-transform duration-500">
                        <svg class="w-32 h-32 text-amber-600" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
                    </div>
                </a>

                <!-- Approved History -->
                <a href="{{ route('reimbursements.index', ['cost_center_id' => $costCenter->id, 'tab' => 'global_history', 'status' => 'aprobado']) }}" class="bg-white dark:bg-gray-800 p-6 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 relative overflow-hidden group hover:border-emerald-300 transition-all text-left">
                    <div class="relative z-10">
                        <p class="text-[10px] font-black uppercase tracking-[0.2em] text-emerald-500 mb-1">Total Pagado</p>
                        <h4 class="text-4xl font-black text-gray-900 dark:text-white leading-none">${{ number_format($stats['approved_amount'], 0) }}</h4>
                        <p class="text-xs font-bold text-gray-400 mt-2">Histórico: <span class="text-gray-900 dark:text-white">{{ $stats['approved_count'] }}</span> pagos</p>
                    </div>
                    <div class="absolute -right-4 -bottom-4 opacity-5 group-hover:scale-110 transition-transform duration-500">
                        <svg class="w-32 h-32 text-emerald-600" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14.5l-4-4 1.41-1.41L11 13.67l7.09-7.09L19.5 8 11 16.5z"/></svg>
                    </div>
                </a>

                <!-- Corrections & Rejections -->
                <div class="bg-white dark:bg-gray-800 p-6 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 relative overflow-hidden">
                    <div class="relative z-10 grid grid-cols-2 gap-2 h-full">
                        <a href="{{ route('reimbursements.index', ['cost_center_id' => $costCenter->id, 'status' => 'requiere_correccion']) }}" class="flex flex-col justify-center border-r border-gray-50 dark:border-gray-700 pr-2 hover:bg-orange-50 dark:hover:bg-orange-900/10 transition-colors">
                            <p class="text-[9px] font-black uppercase text-orange-500 mb-1">Corregir</p>
                            <h4 class="text-2xl font-black text-gray-900 dark:text-white">{{ $stats['correction_count'] }}</h4>
                        </a>
                        <a href="{{ route('reimbursements.index', ['cost_center_id' => $costCenter->id, 'status' => 'rechazado']) }}" class="flex flex-col justify-center pl-2 hover:bg-rose-50 dark:hover:bg-rose-900/10 transition-colors">
                            <p class="text-[9px] font-black uppercase text-rose-500 mb-1">Rechazos</p>
                            <h4 class="text-2xl font-black text-gray-900 dark:text-white">{{ $stats['rejected_count'] }}</h4>
                        </a>
                    </div>
                </div>

                <!-- Efficiency -->
                <div class="bg-indigo-600 p-6 rounded-3xl shadow-xl shadow-indigo-500/20 text-white relative overflow-hidden group">
                    <div class="relative z-10">
                        <p class="text-[10px] font-black uppercase tracking-[0.2em] text-indigo-200 mb-1">Velocidad de Flujo</p>
                        <h4 class="text-4xl font-black leading-none">{{ number_format($stats['avg_approval_days'], 1) }} d</h4>
                        <p class="text-xs font-bold text-indigo-100 mt-2 opacity-80">Tiempo promedio de aprobación</p>
                    </div>
                    <div class="absolute -right-4 -bottom-4 opacity-10 group-hover:scale-110 transition-transform duration-500">
                        <svg class="w-32 h-32 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    </div>
                </div>

                <!-- Manager List -->
                <div class="bg-gray-900 p-6 rounded-3xl shadow-sm text-white relative overflow-hidden group">
                    <div class="relative z-10 flex flex-col justify-between h-full">
                        <p class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-400 mb-4">Firmas Autorizadas</p>
                        <div class="flex -space-x-3 overflow-hidden mb-4">
                            @foreach(['director', 'controlObra', 'directorEjecutivo', 'accountant', 'direccion', 'tesoreria'] as $role)
                                @if($costCenter->$role)
                                    <div class="inline-block h-10 w-10 rounded-full ring-4 ring-gray-900 bg-indigo-500 flex items-center justify-center text-xs font-black uppercase text-white shadow-lg" title="{{ $costCenter->$role->name }}">
                                        {{ substr($costCenter->$role->name, 0, 1) }}
                                    </div>
                                @endif
                            @endforeach
                        </div>
                        <p class="text-[9px] text-gray-500 font-bold uppercase tracking-widest">{{ $costCenter->approvalSteps->count() }} niveles configurados</p>
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

                <!-- Monthly Trend Chart -->
                <div class="bg-white dark:bg-gray-800 rounded-[2.5rem] shadow-sm border border-gray-100 dark:border-gray-700 p-8">
                    <h3 class="text-xl font-black text-gray-900 dark:text-white uppercase tracking-tighter mb-8">
                        Historial de Gasto Mensual
                    </h3>
                    <div class="h-[250px]">
                        <canvas id="monthlyTrendChart"></canvas>
                    </div>
                </div>

                <!-- Top Spenders -->
                <div class="bg-white dark:bg-gray-800 rounded-[2.5rem] shadow-sm border border-gray-100 dark:border-gray-700 p-8">
                    <h3 class="text-xl font-black text-gray-900 dark:text-white uppercase tracking-tighter mb-8">
                        Principales Solicitantes
                    </h3>
                    <div class="space-y-6">
                        @forelse($topSpenders as $s)
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-900/40 flex items-center justify-center text-indigo-600 dark:text-indigo-400 font-black">
                                    {{ substr($s->user->name ?? '?', 0, 1) }}
                                </div>
                                <div class="ml-4 text-xs">
                                    <p class="font-black text-gray-900 dark:text-white">{{ $s->user->name ?? 'N/A' }}</p>
                                    <p class="text-gray-400 font-bold uppercase tracking-widest">{{ $s->count }} solicitudes</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-black text-gray-900 dark:text-white">${{ number_format($s->amount, 0) }}</p>
                            </div>
                        </div>
                        @empty
                            <p class="text-xs text-center text-gray-400 py-10">Sin datos</p>
                        @endforelse
                    </div>
                </div>

                <!-- Status Distribution -->
                <div class="bg-white dark:bg-gray-800 rounded-[2.5rem] shadow-sm border border-gray-100 dark:border-gray-700 p-8 flex flex-col items-center">
                    <h3 class="text-xl font-black text-gray-900 dark:text-white uppercase tracking-tighter mb-4 w-full text-left">
                        Estatus de Capital
                    </h3>
                    <div class="relative w-full h-[200px] flex items-center justify-center">
                        <canvas id="statusDoughnutChart"></canvas>
                        <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                            <span class="text-[8px] font-black uppercase text-gray-400 tracking-widest leading-none">Global</span>
                            <h4 class="text-lg font-black text-gray-900 dark:text-white leading-tight">
                                ${{ number_format($statusBreakdown->sum('amount'), 0) }}
                            </h4>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-8">
                <!-- Recent Activity -->
                <div class="bg-white dark:bg-gray-800 rounded-[2.5rem] shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden flex flex-col">
                    <div class="px-8 py-6 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center">
                        <h3 class="text-xl font-black text-gray-900 dark:text-white uppercase tracking-tighter">Actividad Reciente</h3>
                    </div>
                    <div class="overflow-x-auto flex-1 text-sm">
                        @if($recentReimbursements->count() > 0)
                        <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700">
                            <thead>
                                <tr class="bg-gray-50/30 dark:bg-gray-900/10">
                                    <th class="px-8 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest">Folio</th>
                                    <th class="px-8 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest">Solicitante</th>
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
                                    <td class="px-8 py-4 font-bold text-gray-900 dark:text-white">{{ explode(' ', $r->user->name)[0] }}</td>
                                    <td class="px-8 py-4 font-black text-gray-900 dark:text-white">${{ number_format($r->total, 2) }}</td>
                                    <td class="px-8 py-4">
                                        <span class="px-2.5 py-1 inline-flex text-[9px] leading-4 font-black rounded-lg uppercase tracking-widest
                                            {{ $r->status === 'aprobado' ? 'bg-emerald-100 text-emerald-800' : 'bg-indigo-100 text-indigo-800' }}">
                                            {{ $r->status === 'aprobado' ? 'Pagado' : Str::limit($r->status, 10) }}
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                        @else
                            <div class="flex flex-col items-center justify-center p-20">
                                <div class="bg-gray-50 dark:bg-gray-900 w-16 h-16 rounded-3xl flex items-center justify-center mb-4">
                                    <svg class="w-8 h-8 text-gray-200 dark:text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                </div>
                                <p class="text-xs font-black uppercase text-gray-300 tracking-widest">Sin registros activos</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
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
