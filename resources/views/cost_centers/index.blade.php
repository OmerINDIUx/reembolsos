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
                        @if(Auth::user()->hasRole('admin', 'director_ejecutivo', 'direccion'))
                        <a href="{{ route('cost_centers.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                            Nuevo Centro de Costos
                        </a>
                        @endif
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

                    @if (session('error'))
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                            <span class="block sm:inline">{{ session('error') }}</span>
                        </div>
                    @endif

                    <!-- Tabs Navigation -->
                    <div class="mb-6 border-b border-gray-200 dark:border-gray-700">
                        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                            <a href="{{ route('cost_centers.index', array_merge(request()->query(), ['tab' => 'active'])) }}" 
                               class="{{ request('tab', 'active') === 'active' 
                                    ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' 
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }} 
                                    whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-all">
                                Centros Activos
                                <span class="ml-2 py-0.5 px-2.5 rounded-full text-xs font-medium {{ request('tab', 'active') === 'active' ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-100 text-gray-600' }}">
                                    {{ request('tab', 'active') === 'active' ? $costCenters->total() : '' }}
                                </span>
                            </a>
                            <a href="{{ route('cost_centers.index', array_merge(request()->query(), ['tab' => 'history'])) }}" 
                               class="{{ request('tab') === 'history' 
                                    ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' 
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }} 
                                    whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-all">
                                Historial (Inactivos)
                                <span class="ml-2 py-0.5 px-2.5 rounded-full text-xs font-medium {{ request('tab') === 'history' ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-100 text-gray-600' }}">
                                    {{ request('tab') === 'history' ? $costCenters->total() : '' }}
                                </span>
                            </a>
                        </nav>
                    </div>

                    <div id="results-container">
                        <div class="overflow-x-auto relative shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Centro de Costos</th>
                                    <th scope="col" class="px-6 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Estatus Actual</th>
                                    <th scope="col" class="px-6 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Monto Ejecutado vs Presupuesto</th>
                                    <th scope="col" class="px-6 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Progreso / Análisis</th>
                                    <th scope="col" class="px-6 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Flujo de Aprobación</th>
                                    @if(Auth::user()->hasRole('admin', 'admin_view', 'director_ejecutivo', 'direccion'))
                                    <th scope="col" class="relative px-6 py-3 text-right"></th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach ($costCenters as $cc)
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <a href="{{ route('cost_centers.show', $cc->id) }}" class="flex items-center group">
                                            <div>
                                                <div class="text-sm font-black text-gray-900 dark:text-white group-hover:text-indigo-600 transition-colors underline decoration-dotted decoration-indigo-200 underline-offset-4">{{ $cc->name }}</div>
                                                <div class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">{{ $cc->abbreviation }}</div>
                                                <div class="text-[9px] text-indigo-500 font-black uppercase mt-1">Benef: {{ $cc->beneficiary->name ?? 'S/A' }}</div>
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
                                    @if(Auth::user()->hasRole('admin', 'admin_view', 'director_ejecutivo', 'direccion'))
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-xs font-semibold uppercase tracking-widest">
                                        <div class="flex justify-end items-center space-x-4">
                                            @if(Auth::user()->hasRole('admin', 'director_ejecutivo', 'direccion'))
                                                @if($cc->is_active)
                                                    <a href="{{ route('cost_centers.edit', $cc->id) }}" class="text-indigo-600 hover:text-indigo-900 transition-colors">
                                                        Editar
                                                    </a>

                                                    <form action="{{ route('cost_centers.toggle_status', $cc->id) }}" method="POST" class="inline">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="text-amber-600 hover:text-amber-900 transition-colors">
                                                            Desactivar
                                                        </button>
                                                    </form>

                                                    @if($cc->reimbursements_count == 0)
                                                    <form action="{{ route('cost_centers.destroy', $cc->id) }}" method="POST" class="inline" onsubmit="return confirm('¿Estás seguro de eliminar permanentemente este centro de costos?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-red-600 hover:text-red-900 transition-colors">
                                                            Eliminar
                                                        </button>
                                                    </form>
                                                    @endif
                                                @else
                                                    <form action="{{ route('cost_centers.toggle_status', $cc->id) }}" method="POST" class="inline">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="text-emerald-600 hover:text-emerald-900 transition-colors">
                                                            Reactivar
                                                        </button>
                                                    </form>

                                                    @if($cc->reimbursements_count == 0)
                                                    <form action="{{ route('cost_centers.destroy', $cc->id) }}" method="POST" class="inline" onsubmit="return confirm('¿Estás seguro de eliminar permanentemente este centro de costos del historial?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-red-600 hover:text-red-900 transition-colors">
                                                            Eliminar
                                                        </button>
                                                    </form>
                                                    @endif
                                                @endif
                                            @else
                                                <span class="text-gray-400 italic">Lectura</span>
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
