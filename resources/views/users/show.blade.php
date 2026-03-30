<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 rounded-[2rem] bg-indigo-600 flex items-center justify-center text-2xl font-black text-white shadow-xl shadow-indigo-600/20">
                    {{ substr($user->name, 0, 1) }}
                </div>
                <div>
                    <h2 class="font-black text-3xl text-gray-900 dark:text-white leading-tight uppercase tracking-tighter">
                        Perfil de {{ explode(' ', $user->name)[0] }}
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 font-medium">{{ $user->role_name }} <span class="mx-2">•</span> <span class="font-bold text-indigo-500">{{ $user->email }}</span></p>
                </div>
            </div>
            
            <div class="flex items-center gap-3">
                @if(Auth::user()->isAdmin())
                <a href="{{ route('users.edit', $user) }}" class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-black uppercase tracking-widest text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition-all shadow-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                    Editar Usuario
                </a>
                @endif
                <a href="{{ route('users.index') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-black uppercase tracking-widest rounded-xl transition-all shadow-lg hover:shadow-indigo-500/30">
                    &larr; Volver
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            
            <!-- Summary Stats -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Personal Pending -->
                <div class="bg-white dark:bg-gray-800 p-6 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden relative group">
                    <div class="relative z-10">
                        <p class="text-[10px] font-black uppercase tracking-widest text-amber-500 mb-1">Mis Solicitudes Pendientes</p>
                        <h4 class="text-4xl font-black text-gray-900 dark:text-white leading-none">{{ $stats['pending_count'] }}</h4>
                        <p class="text-xs font-bold text-gray-400 mt-2">${{ number_format($stats['pending_amount'], 2) }} <span class="font-normal opacity-60">en tránsito</span></p>
                    </div>
                </div>

                <!-- Personal Approved -->
                <div class="bg-white dark:bg-gray-800 p-6 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden relative group">
                    <div class="relative z-10">
                        <p class="text-[10px] font-black uppercase tracking-widest text-emerald-500 mb-1">Total Recuperado (Pagado)</p>
                        <h4 class="text-4xl font-black text-gray-900 dark:text-white leading-none">${{ number_format($stats['approved_amount'], 0) }}</h4>
                        <p class="text-xs font-bold text-gray-400 mt-2">{{ $stats['approved_count'] }} <span class="font-normal opacity-60">reembolsos liquidados</span></p>
                    </div>
                </div>

                <!-- Approval Tasks (If any) -->
                @if($pendingApprovalsCount > 0)
                <div class="bg-indigo-600 p-6 rounded-3xl shadow-xl shadow-indigo-600/20 text-white relative overflow-hidden animate-pulse">
                    <div class="relative z-10">
                        <p class="text-[10px] font-black uppercase tracking-widest text-indigo-100 mb-1">Tareas de Aprobación</p>
                        <h4 class="text-4xl font-black leading-none">{{ $pendingApprovalsCount }}</h4>
                        <p class="text-xs font-bold text-indigo-100 mt-2 opacity-80 uppercase tracking-widest">Esperando tu firma &rarr;</p>
                    </div>
                </div>
                @else
                <div class="bg-emerald-50/50 dark:bg-emerald-900/10 p-6 rounded-3xl border border-dashed border-emerald-200 dark:border-emerald-800 flex items-center justify-center">
                    <p class="text-[10px] font-black text-emerald-600 uppercase tracking-widest">Al día en aprobaciones</p>
                </div>
                @endif

                <!-- User Info Profile -->
                <div class="bg-gray-900 p-6 rounded-3xl shadow-sm text-white relative overflow-hidden group">
                    <div class="relative z-10">
                        <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-4">Jerarquía</p>
                        <div class="space-y-3">
                            <div class="flex items-center gap-2">
                                <div class="w-2 h-2 rounded-full bg-indigo-500"></div>
                                <p class="text-[10px] font-bold uppercase tracking-widest text-gray-300">Jefe Directo:</p>
                                <p class="text-xs font-black">{{ $user->director->name ?? 'Gerencia General' }}</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="w-2 h-2 rounded-full bg-emerald-500"></div>
                                <p class="text-[10px] font-bold uppercase tracking-widest text-gray-300">Subordinados:</p>
                                <p class="text-xs font-black">{{ $user->subordinates->count() }} personas</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Trend Chart -->
                <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-[2.5rem] shadow-sm border border-gray-100 dark:border-gray-700 p-8">
                    <h3 class="text-xl font-black text-gray-900 dark:text-white uppercase tracking-tighter mb-8">
                        Dinámica Personal de Gasto (Mes a mes)
                    </h3>
                    <div class="h-[300px]">
                        <canvas id="userTrendChart"></canvas>
                    </div>
                </div>

                <!-- Category Breakdown -->
                <div class="bg-white dark:bg-gray-800 rounded-[2.5rem] shadow-sm border border-gray-100 dark:border-gray-700 p-8">
                    <h3 class="text-xl font-black text-gray-900 dark:text-white uppercase tracking-tighter mb-8">
                        Mis Categorías
                    </h3>
                    <div class="space-y-6">
                        @forelse($categoryBreakdown->take(5) as $cat)
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
                                    <div class="bg-indigo-600 h-full rounded-full transition-all duration-700" style="width: {{ $percent }}%"></div>
                                </div>
                            </div>
                        @empty
                            <p class="text-xs text-center text-gray-400 py-10">Sin datos registrados</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Recent Activity Table -->
            <div class="bg-white dark:bg-gray-800 rounded-[2.5rem] shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="px-8 py-6 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center">
                    <h3 class="text-xl font-black text-gray-900 dark:text-white uppercase tracking-tighter">Historial de Solicitudes de {{ explode(' ', $user->name)[0] }}</h3>
                </div>
                <div class="overflow-x-auto text-sm">
                    @if($recentReimbursements->count() > 0)
                    <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700">
                        <thead class="bg-gray-50/50 dark:bg-gray-900/50">
                            <tr>
                                <th class="px-8 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest">ID / Folio</th>
                                <th class="px-8 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest">Centro de Costos</th>
                                <th class="px-8 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest">Importe</th>
                                <th class="px-8 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest">Ubicación Actual</th>
                                <th class="px-8 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest">Estatus</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-700">
                            @foreach($recentReimbursements as $r)
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-900/10 cursor-pointer" onclick="window.location='{{ route('reimbursements.show', $r) }}'">
                                <td class="px-8 py-5">
                                    <div class="font-black text-gray-900 dark:text-white uppercase">{{ $r->folio }}</div>
                                    <div class="text-[9px] text-gray-400 font-bold">{{ $r->created_at->isoFormat('D MMM YYYY') }}</div>
                                </td>
                                <td class="px-8 py-5">
                                    <div class="text-[10px] font-black text-indigo-600 uppercase">{{ $r->costCenter->code ?? 'S/C' }}</div>
                                    <div class="text-[9px] text-gray-400 truncate max-w-[150px] font-medium">{{ $r->costCenter->name ?? '-' }}</div>
                                </td>
                                <td class="px-8 py-5 font-black text-gray-900 dark:text-white">${{ number_format($r->total, 2) }}</td>
                                <td class="px-8 py-5">
                                    @if($r->status === 'aprobado')
                                        <span class="text-[10px] font-bold text-emerald-600">Liquidado / Pagado</span>
                                    @elseif($r->status === 'rechazado')
                                        <span class="text-[10px] font-bold text-rose-600">Rechazado Definitivamente</span>
                                    @else
                                        <span class="text-[10px] font-bold text-gray-900 dark:text-white flex items-center">
                                            <svg class="w-3 h-3 mr-1 text-amber-500" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
                                            {{ $r->currentStep->name ?? 'En Validación' }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-8 py-5">
                                    <span class="px-3 py-1 inline-flex text-[9px] leading-4 font-black rounded-full uppercase tracking-widest
                                        {{ $r->status === 'aprobado' ? 'bg-emerald-100 text-emerald-800' : '' }}
                                        {{ $r->status === 'requiere_correccion' ? 'bg-amber-100 text-amber-800' : '' }}
                                        {{ in_array($r->status, ['pendiente', 'aprobado_director', 'aprobado_control', 'aprobado_ejecutivo', 'aprobado_cxp', 'aprobado_direccion']) ? 'bg-indigo-100 text-indigo-800' : '' }}
                                        {{ $r->status === 'rechazado' ? 'bg-rose-100 text-rose-800' : '' }}
                                    ">
                                        {{ str_replace('_', ' ', $r->status) }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @else
                        <div class="py-20 text-center">
                            <p class="text-xs font-black uppercase text-gray-400 tracking-widest">Este usuario no tiene solicitudes registradas</p>
                        </div>
                    @endif
                </div>
            </div>
            
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctxTrend = document.getElementById('userTrendChart');
            if (ctxTrend) {
                const labels = {!! json_encode($monthlyTrend->pluck('month')->map(fn($m) => \Carbon\Carbon::parse($m . '-01')->format('M Y'))) !!};
                const data = {!! json_encode($monthlyTrend->pluck('amount')) !!};

                new Chart(ctxTrend, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Mi Gasto ($)',
                            data: data,
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
                        plugins: { legend: { display: false } },
                        scales: {
                            y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' }, ticks: { font: { size: 10, weight: '700' } } },
                            x: { grid: { display: false }, ticks: { font: { size: 10, weight: '700' } } }
                        }
                    }
                });
            }
        });
    </script>
    @endpush
</x-app-layout>
