<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Centros de Costos') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="flex flex-col md:flex-row justify-between items-center mb-6 space-y-4 md:space-y-0">
                        <h3 class="text-lg font-medium">Panel de Control de Reembolsos por Centro de Costos</h3>
                        @if(Auth::user()->isAdmin())
                        <a href="{{ route('cost_centers.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                            Nuevo Centro de Costos
                        </a>
                        @endif
                    </div>

                    <!-- Statistics Panel -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
                        <div class="bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-800 p-4 rounded-xl shadow-sm overflow-hidden relative group">
                             <div class="absolute -right-4 -top-4 opacity-10 group-hover:opacity-20 transition-opacity">
                                <svg class="w-24 h-24 text-indigo-600" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
                             </div>
                            <h4 class="text-xs uppercase tracking-wider text-indigo-600 dark:text-indigo-400 font-bold mb-1">Reembolsos Pendientes</h4>
                            <div class="flex items-baseline space-x-2">
                                <span class="text-2xl font-bold text-gray-900 dark:text-white">{{ $globalStats['total_pending_count'] }}</span>
                                <span class="text-xs text-gray-500">solicitudes</span>
                            </div>
                        </div>

                        <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-100 dark:border-amber-800 p-4 rounded-xl shadow-sm overflow-hidden relative group">
                             <div class="absolute -right-4 -top-4 opacity-10 group-hover:opacity-20 transition-opacity">
                                <svg class="w-24 h-24 text-amber-600" fill="currentColor" viewBox="0 0 24 24"><path d="M11.88 3L4.97 10h13.82l-6.91-7zM5 12v9h14v-9H5zm7 7a2 2 0 110-4 2 2 0 010 4z"/></svg>
                             </div>
                            <h4 class="text-xs uppercase tracking-wider text-amber-600 dark:text-amber-400 font-bold mb-1">Monto en Aprobación</h4>
                            <div class="flex items-baseline space-x-2">
                                <span class="text-2xl font-bold text-gray-900 dark:text-white">${{ number_format($globalStats['total_pending_amount'], 2) }}</span>
                                <span class="text-xs text-gray-500">MXN</span>
                            </div>
                        </div>

                        <div class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-100 dark:border-emerald-800 p-4 rounded-xl shadow-sm overflow-hidden relative group">
                             <div class="absolute -right-4 -top-4 opacity-10 group-hover:opacity-20 transition-opacity">
                                <svg class="w-24 h-24 text-emerald-600" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14.5l-4-4 1.41-1.41L11 13.67l7.09-7.09L19.5 8 11 16.5z"/></svg>
                             </div>
                            <h4 class="text-xs uppercase tracking-wider text-emerald-600 dark:text-emerald-400 font-bold mb-1">Total Pagado (Histórico)</h4>
                            <div class="flex items-baseline space-x-2">
                                <span class="text-2xl font-bold text-gray-900 dark:text-white">${{ number_format($globalStats['total_approved_amount'], 2) }}</span>
                                <span class="text-xs text-gray-500">MXN</span>
                            </div>
                        </div>

                        <div class="bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-800 p-4 rounded-xl shadow-sm overflow-hidden relative group">
                             <div class="absolute -right-4 -top-4 opacity-10 group-hover:opacity-20 transition-opacity">
                                <svg class="w-24 h-24 text-indigo-600" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
                             </div>
                            <h4 class="text-xs uppercase tracking-wider text-indigo-600 dark:text-indigo-400 font-bold mb-1">Presupuesto Total Mensual</h4>
                            <div class="flex items-baseline space-x-2">
                                <span class="text-2xl font-bold text-gray-900 dark:text-white">${{ number_format($globalStats['total_budget'], 2) }}</span>
                                <span class="text-xs text-gray-500">MXN</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Search & Filter Form -->
                    <div class="mb-6 bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                        <form id="filter-form" action="{{ route('cost_centers.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <!-- Search Input -->
                            <div class="col-span-1 md:col-span-3">
                                <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Buscar (Código, Nombre, Descripción)</label>
                                <input type="text" name="search" id="search" value="{{ request('search') }}" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="Buscar...">
                            </div>

                            <!-- Buttons -->
                            <div class="col-span-1 flex justify-end items-end space-x-2">
                                <a href="{{ route('cost_centers.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-200 dark:bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-gray-700 dark:text-gray-200 uppercase tracking-widest hover:bg-gray-300 dark:hover:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150 h-10">
                                    Limpiar
                                </a>
                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150 h-10">
                                    Filtrar
                                </button>
                            </div>
                        </form>
                    </div>

                    @if (session('success'))
                        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                            <span class="block sm:inline">{{ session('success') }}</span>
                        </div>
                    @endif

                    <div id="results-container">
                        <div class="overflow-x-auto relative shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Centro de Costos (Click p/ Dashboard)</th>
                                    <th scope="col" class="px-6 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Estatus Actual</th>
                                    <th scope="col" class="px-6 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Monto Ejecutado vs Presupuesto</th>
                                    <th scope="col" class="px-6 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Progreso / Análisis</th>
                                    <th scope="col" class="px-6 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Flujo de Aprobación</th>
                                    @if(Auth::user()->isAdmin() || Auth::user()->isAdminView())
                                    <th scope="col" class="relative px-6 py-3 text-right"></th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach ($costCenters as $cc)
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <a href="{{ route('cost_centers.show', $cc->id) }}" class="flex items-center group">
                                            <div class="flex-shrink-0 h-10 w-10 flex items-center justify-center rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-400 group-hover:bg-indigo-100 dark:group-hover:bg-indigo-900/50 group-hover:text-indigo-600 transition-all">
                                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-black text-gray-900 dark:text-white group-hover:text-indigo-600 transition-colors underline decoration-dotted decoration-indigo-200 underline-offset-4">{{ $cc->code }}</div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400 truncate max-w-[150px]">{{ $cc->name }}</div>
                                            </div>
                                        </a>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <div class="flex flex-col items-center space-y-1">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $cc->pending_count > 0 ? 'bg-amber-100 text-amber-800' : 'bg-gray-100 text-gray-800' }}">
                                                {{ $cc->pending_count }} Pendientes
                                            </span>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800">
                                                {{ $cc->approved_count }} Pagados
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-left">
                                        <div class="flex flex-col space-y-3">
                                            @php 
                                            $paidPercentage = $cc->budget > 0 ? (($cc->approved_total ?? 0) / $cc->budget) * 100 : 0;
                                            $pendingPercentage = $cc->budget > 0 ? (($cc->pending_total ?? 0) / $cc->budget) * 100 : 0;
                                            $totalSpent = ($cc->approved_total ?? 0) + ($cc->pending_total ?? 0);
                                            $totalPercentage = $cc->budget > 0 ? ($totalSpent / $cc->budget) * 100 : 0;
                                            @endphp

                                            <!-- Bar 1: Paid -->
                                            <div>
                                                <div class="flex justify-between items-center mb-1">
                                                    <span class="text-[9px] font-black uppercase text-emerald-600 dark:text-emerald-400">Pagado: ${{ number_format($cc->approved_total ?? 0, 2) }}</span>
                                                    <span class="text-[9px] font-bold text-gray-400">{{ number_format($paidPercentage, 1) }}%</span>
                                                </div>
                                                <div class="w-full bg-gray-100 dark:bg-gray-700 h-1.5 rounded-full overflow-hidden">
                                                    <div class="h-full bg-emerald-500 transition-all duration-500" style="width: {{ min(100, $paidPercentage) }}%"></div>
                                                </div>
                                            </div>

                                            <!-- Bar 2: Pending -->
                                            <div>
                                                <div class="flex justify-between items-center mb-1">
                                                    <span class="text-[9px] font-black uppercase text-amber-600 dark:text-amber-400">En Proceso: ${{ number_format($cc->pending_total ?? 0, 2) }}</span>
                                                    <span class="text-[9px] font-bold text-gray-400">{{ number_format($pendingPercentage, 1) }}%</span>
                                                </div>
                                                <div class="w-full bg-gray-100 dark:bg-gray-700 h-1.5 rounded-full overflow-hidden">
                                                    <div class="h-full bg-amber-500 transition-all duration-500" style="width: {{ min(100, $pendingPercentage) }}%"></div>
                                                </div>
                                            </div>

                                            <div class="pt-1 border-t border-gray-100 dark:border-gray-700/50 flex justify-between items-center">
                                                <span class="text-[10px] font-black uppercase {{ $totalPercentage > 100 ? 'text-red-500' : 'text-gray-500' }}">
                                                    Total: ${{ number_format($totalSpent, 2) }} / ${{ number_format($cc->budget, 2) }}
                                                </span>
                                                <span class="text-[10px] font-black {{ $totalPercentage > 100 ? 'text-red-600' : 'text-indigo-600' }}">
                                                    {{ number_format($totalPercentage, 1) }}% Gasto
                                                </span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-xs text-center">
                                        <div class="flex flex-col items-center space-y-2">
                                            @if($cc->pending_count > 0)
                                                <div class="flex flex-wrap gap-1 justify-center max-w-[200px]">
                                                    @isset($stepBreakdown[$cc->id])
                                                        @foreach($stepBreakdown[$cc->id] as $stepData)
                                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 border border-indigo-100 dark:border-indigo-800" title="{{ $stepData->currentStep->name ?? 'Paso #'.$stepData->current_step_id }}">
                                                                {{ $stepData->count }}
                                                                <span class="ml-1 opacity-70">{{ \Illuminate\Support\Str::limit($stepData->currentStep->name ?? 'Pend.', 8) }}</span>
                                                            </span>
                                                        @endforeach
                                                    @endisset
                                                </div>
                                                
                                                @if($cc->oldest_pending)
                                                    @php
                                                        $daysOld = (int) \Carbon\Carbon::parse($cc->oldest_pending)->diffInDays();
                                                        $statusColor = $daysOld > 7 ? 'text-red-500' : ($daysOld > 3 ? 'text-amber-500' : 'text-gray-400');
                                                    @endphp
                                                    <div class="flex items-center space-x-1 {{ $statusColor }} font-medium">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                                                        <span>{{ $daysOld }} d el más viejo</span>
                                                    </div>
                                                @endif
                                            @endif
                                            
                                            <div class="mt-1 pt-1 border-t border-gray-100 dark:border-gray-700/50 flex items-center justify-center space-x-1 text-[9px] font-bold text-indigo-500 uppercase">
                                                <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M13 10V3L4 14h7v7l9-11h-7z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                                                <span>{{ number_format($cc->avg_approval_days ?? 0, 1) }} d prom. aprobación</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-xs text-center">
                                        <div class="flex flex-col items-center group">
                                            <span class="text-xl font-black text-indigo-600 dark:text-indigo-400 group-hover:scale-110 transition-transform">{{ $cc->approval_steps_count }}</span>
                                            <span class="text-[9px] font-bold text-gray-400 uppercase tracking-widest mt-1">Niveles de Flujo</span>
                                        </div>
                                    </td>
                                    @if(Auth::user()->isAdmin() || Auth::user()->isAdminView())
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex justify-end space-x-3">
                                            @if(Auth::user()->isAdmin())
                                            <a href="{{ route('cost_centers.edit', $cc->id) }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 bg-indigo-50 dark:bg-indigo-900/40 p-2 rounded-lg transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            </a>
                                            <form action="{{ route('cost_centers.destroy', $cc->id) }}" method="POST" class="inline" onsubmit="return confirm('¿Estás seguro de eliminar este centro de costos?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 bg-red-50 dark:bg-red-900/40 p-2 rounded-lg transition-colors">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                </button>
                                            </form>
                                            @else
                                            <span class="text-gray-400 italic text-xs">Lectura</span>
                                            @endif
                                        </div>
                                    </td>
                                    @endif
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-8 px-4 py-3 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-700 sm:rounded-b-lg">
                        {{ $costCenters->links() }}
                    </div>
                    </div> <!-- End results-container -->
                </div>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('filter-form');
            const container = document.getElementById('results-container');
            
            // Function to handle fetching and updating
            function fetchResults(url) {
                // simple opacity fade for feedback
                container.style.opacity = '0.5';
                
                fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newContent = doc.getElementById('results-container').innerHTML;
                    container.innerHTML = newContent;
                    container.style.opacity = '1';
                    
                    // Update URL without reload
                    window.history.pushState({}, '', url);
                    
                    // Re-attach pagination listeners
                    attachPaginationListeners();
                })
                .catch(error => {
                    console.error('Error:', error);
                    container.style.opacity = '1';
                });
            }
            
            // Handle Form Submit (Manual Filter)
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                submitFilter();
            });

            function submitFilter() {
                const url = new URL(form.action);
                const params = new URLSearchParams(new FormData(form));
                url.search = params.toString();
                fetchResults(url);
            }

            // Real-time Search Logic with Debounce
            let debounceTimer;
            
            // Listen to inputs
            form.querySelectorAll('input').forEach(input => {
                input.addEventListener('input', function() {
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(() => {
                        submitFilter();
                    }, 500); // 500ms delay
                });
            });

            // Listen to selects (immediate trigger) - though CostCenters currently only has input
            form.querySelectorAll('select').forEach(select => {
                select.addEventListener('change', function() {
                    submitFilter();
                });
            });
            
            // Handle Pagination Clicks
            function attachPaginationListeners() {
                const links = container.querySelectorAll('a.page-link, .pagination a'); // Adapt selector to Laravel's pagination classes
                links.forEach(link => {
                     link.addEventListener('click', function(e) {
                         e.preventDefault();
                         fetchResults(this.href);
                     });
                });
            }
            
            // Initial attach
            attachPaginationListeners();
        });
    </script>
</x-app-layout>
