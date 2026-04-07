<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Reembolsos') }}
        </h2>
    </x-slot>

    <style>
        [x-cloak] { display: none !important; }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    @php
                        $user = Auth::user();
                        $canManage = $user->isAdmin() || $user->isAdminView() || $user->isCxp() || $user->isTreasury() || $user->isDireccion() || $user->isDirector() || $user->isControlObra() || $user->isExecutiveDirector();
                        $defaultTab = $canManage ? 'management' : 'active';
                    @endphp
                    <div class="flex flex-col md:flex-row justify-between items-center mb-6 space-y-4 md:space-y-0">
                        <h3 class="text-lg font-medium">Listado de Reembolsos</h3>
                        <div class="flex space-x-2">
                            @if($user->isAdmin() || $user->isTreasury() || $user->isCxp())
                            <button type="button" x-data @click="$dispatch('open-bulk-approval-modal')" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-500 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                                Aprobación Masiva (CSV)
                            </button>
                            @endif
                            @if(Auth::user()->isAdmin())
                             <a href="{{ route('reimbursements.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                                 Nuevo Reembolso (Admin)
                            </a>
                            @else
                            <span class="inline-flex items-center px-4 py-2 bg-gray-100 dark:bg-gray-700 border border-transparent rounded-md font-semibold text-xs text-gray-500 dark:text-gray-400 uppercase tracking-widest cursor-not-allowed">
                                Recepción Cerrada
                            </span>
                            @endif

                        </div>
                    </div>

                    <!-- Tabs -->
                    <div class="mb-4 border-b border-gray-200 dark:border-gray-700">
                        <ul class="flex flex-wrap -mb-px text-sm font-medium text-center" id="myTab" data-tabs-toggle="#myTabContent" role="tablist">
                            @php
                                // User and permissions already defined above
                            @endphp

                            @if($canManage)
                            <li class="mr-2" role="presentation">
                                <a href="{{ route('reimbursements.index', array_merge(request()->except('tab', 'page'), ['tab' => 'management'])) }}" class="inline-block p-4 border-b-2 rounded-t-lg {{ request('tab', $defaultTab) == 'management' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-500' : 'border-transparent hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300 dark:hover:border-gray-300 text-gray-500 dark:text-gray-400' }}" id="management-tab" type="button" role="tab" aria-controls="management" aria-selected="false">Módulo de Gestión</a>
                            </li>
                            <li class="mr-2" role="presentation">
                                <a href="{{ route('reimbursements.index', array_merge(request()->except('tab', 'page'), ['tab' => 'global_history'])) }}" class="inline-block p-4 border-b-2 rounded-t-lg {{ request('tab') == 'global_history' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-500' : 'border-transparent hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300 dark:hover:border-gray-300 text-gray-500 dark:text-gray-400' }}" id="global-history-tab" type="button" role="tab" aria-controls="global_history" aria-selected="false">
                                    {{ ($user->isAdmin() || $user->isAdminView()) ? 'Todos los Reembolsos (Global)' : 'Historial Global (Rechazados)' }}
                                </a>
                            </li>
                            @endif

                            <li class="mr-2" role="presentation">
                                <a href="{{ route('reimbursements.index', array_merge(request()->except('tab', 'page'), ['tab' => 'active'])) }}" class="inline-block p-4 border-b-2 rounded-t-lg {{ request('tab', $defaultTab) == 'active' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-500' : 'border-transparent hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300 dark:hover:border-gray-300 text-gray-500 dark:text-gray-400' }}" id="active-tab" type="button" role="tab" aria-controls="active" aria-selected="false">Mis Reembolsos</a>
                            </li>
                            <li class="mr-2" role="presentation">
                                <a href="{{ route('reimbursements.index', array_merge(request()->except('tab', 'page'), ['tab' => 'history'])) }}" class="inline-block p-4 border-b-2 rounded-t-lg {{ request('tab') == 'history' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-500' : 'border-transparent hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300 dark:hover:border-gray-300 text-gray-500 dark:text-gray-400' }}" id="history-tab" type="button" role="tab" aria-controls="history" aria-selected="false">Mis Pagados/Rechazados</a>
                            </li>
                        </ul>
                    </div>

                    <!-- Global Folio Search (Rastreador) -->
                    @if($canManage && (request('tab', $defaultTab) === 'management' || request('tab') === 'global_history'))
                        <div class="mb-6 p-4 bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-800 rounded-xl flex flex-col md:flex-row items-center gap-4">
                            <div class="flex-shrink-0">
                                <span class="text-indigo-600 dark:text-indigo-400 font-bold text-sm uppercase tracking-wider">Rastreador de Folio (Global):</span>
                            </div>
                            <form action="{{ route('reimbursements.index') }}" method="GET" class="flex-1 w-full flex gap-2">
                                <input type="hidden" name="tab" value="{{ request('tab', $defaultTab) }}">
                                <input type="text" name="global_search" value="{{ $globalSearch ?? '' }}" placeholder="Ingresa folio completo o UUID..." class="flex-1 border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors font-bold text-sm">RASTREAR</button>
                                @if(isset($globalSearch))
                                    <a href="{{ route('reimbursements.index', ['tab' => request('tab', $defaultTab)]) }}" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 transition-colors font-bold text-sm uppercase">Limpiar</a>
                                @endif
                            </form>
                        </div>
                    @endif

                    @if(isset($globalSearch))
                        <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
                            Mostrando resultados para el folio buscado: <span class="font-bold text-indigo-600 dark:text-indigo-400">{{ $globalSearch }}</span>
                        </div>
                    @endif

                    @if(session('bulk_errors_categorized'))
                        <div class="mb-6 p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-100 dark:border-amber-800 rounded-lg">
                            <h4 class="text-sm font-bold text-amber-800 dark:text-amber-300 mb-3 flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                Resumen de incidencias en la carga masiva:
                            </h4>
                            
                            @php $ecat = session('bulk_errors_categorized'); @endphp
                            
                            @if(count($ecat['not_found']) > 0)
                            <div class="mb-3">
                                <span class="text-[11px] font-bold text-red-700 dark:text-red-400 uppercase tracking-wider">● No encontrados (Folio/UUID no coinciden):</span>
                                <ul class="list-disc list-inside text-[10px] text-red-600 dark:text-red-400 mt-1 ml-2">
                                    @foreach($ecat['not_found'] as $err) <li>{{ $err }}</li> @endforeach
                                </ul>
                            </div>
                            @endif

                            @if(count($ecat['invalid_status']) > 0)
                            <div class="mb-3">
                                <span class="text-[11px] font-bold text-orange-700 dark:text-orange-400 uppercase tracking-wider">● Fuera de flujo o perfil (Requieren aprobación previa):</span>
                                <ul class="list-disc list-inside text-[10px] text-orange-600 dark:text-orange-400 mt-1 ml-2">
                                    @foreach($ecat['invalid_status'] as $err) <li>{{ $err }}</li> @endforeach
                                </ul>
                            </div>
                            @endif

                            @if(count($ecat['already_approved']) > 0)
                            <div>
                                <span class="text-[11px] font-bold text-gray-700 dark:text-gray-400 uppercase tracking-wider">● Ya aprobados previamente (Duplicados):</span>
                                <ul class="list-disc list-inside text-[10px] text-gray-600 dark:text-gray-400 mt-1 ml-2">
                                    @foreach($ecat['already_approved'] as $err) <li>{{ $err }}</li> @endforeach
                                </ul>
                            </div>
                            @endif
                        </div>
                    @endif

                    @if(session('bulk_errors'))
                        <div class="mb-4 p-4 bg-red-50 dark:bg-red-900/20 border border-red-100 dark:border-red-800 rounded-lg text-xs text-red-700 dark:text-red-400">
                            {{ session('bulk_errors') }}
                        </div>
                    @endif

                    <!-- Search & Filter Form -->
                    <div class="mb-6 bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                        <form id="filter-form" action="{{ route('reimbursements.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-6 gap-4" novalidate>
                            <input type="hidden" name="tab" value="{{ request('tab', $defaultTab) }}">
                            <input type="hidden" name="sort_by" id="input-sort-by" value="{{ request('sort_by', 'created_at') }}">
                            <input type="hidden" name="sort_order" id="input-sort-order" value="{{ request('sort_order', 'desc') }}">
                            
                            <!-- Search Input -->
                            <div class="col-span-1 md:col-span-2">
                                <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Buscar (Folio, UUID, Emisor, Título)</label>
                                <input type="text" name="search" id="search" value="{{ request('search') }}" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="Buscar...">
                            </div>

                            <!-- Status Filter -->
                            <div>
                                <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Estatus</label>
                                <select name="status" id="status" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    <option value="">Todos</option>
                                    <option value="pendiente" {{ request('status') == 'pendiente' ? 'selected' : '' }}>1. Pendiente Director</option>
                                    <option value="aprobado_director" {{ request('status') == 'aprobado_director' ? 'selected' : '' }}>2. Aprobado Director</option>
                                    <option value="aprobado_control" {{ request('status') == 'aprobado_control' ? 'selected' : '' }}>3. Aprobado Control de Obra</option>
                                    <option value="aprobado_ejecutivo" {{ request('status') == 'aprobado_ejecutivo' ? 'selected' : '' }}>4. Aprobado Dir. Ejecutivo</option>
                                    <option value="aprobado_cxp" {{ request('status') == 'aprobado_cxp' ? 'selected' : '' }}>5. Aprobado Subdirección</option>
                                    <option value="aprobado_direccion" {{ request('status') == 'aprobado_direccion' ? 'selected' : '' }}>6. Aprobado Dirección</option>
                                    <option value="aprobado" {{ request('status') == 'aprobado' ? 'selected' : '' }}>7. Pagado (Final)</option>
                                    <option value="requiere_correccion" {{ request('status') == 'requiere_correccion' ? 'selected' : '' }}>Requiere Corrección</option>
                                    <option value="rechazado" {{ request('status') == 'rechazado' ? 'selected' : '' }}>Rechazado</option>
                                </select>
                            </div>

                            <!-- Type Filter -->
                            <div>
                                <label for="type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tipo</label>
                                <select name="type" id="type" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    <option value="">Todos</option>
                                    <option value="reembolso" {{ request('type') == 'reembolso' ? 'selected' : '' }}>Reembolso</option>
                                    <option value="viaje" {{ request('type') == 'viaje' ? 'selected' : '' }}>Viaje</option>
                                    <option value="comida" {{ request('type') == 'comida' ? 'selected' : '' }}>Comida</option>
                                    <option value="fondo_fijo" {{ request('type') == 'fondo_fijo' ? 'selected' : '' }}>Fondo Fijo</option>
                                </select>
                            </div>

                            <!-- Date From -->
                            <div>
                                <label for="from_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Desde (Creación/Expedición)</label>
                                <input type="date" name="from_date" id="from_date" value="{{ request('from_date') }}" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            </div>

                            <!-- Date To -->
                            <div>
                                <label for="to_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Hasta (Creación/Expedición)</label>
                                <input type="date" name="to_date" id="to_date" value="{{ request('to_date') }}" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            </div>

                            <!-- Buttons -->
                            <div class="col-span-1 md:col-span-6 flex justify-end space-x-2 mt-2">
                                <a href="{{ route('reimbursements.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-200 dark:bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-gray-700 dark:text-gray-200 uppercase tracking-widest hover:bg-gray-300 dark:hover:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                                    Limpiar
                                </a>
                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                                    Filtrar
                                </button>
                                <button type="button" onclick="exportData()" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-500 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                                    Exportar
                                </button>
                            </div>
                        </form>
                    </div>

                    @php
                        $tab = request('tab', $defaultTab);
                        $isGroupedView = ($tab === 'management' || $tab === 'weekly_summary');
                    @endphp

                    <div id="results-container">
                        @if($isGroupedView)
                            @php
                                $groupedByWeek = $reimbursements->groupBy('week');
                            @endphp

                            <div>
                            <div class="space-y-6">
                                @forelse($groupedByWeek as $week => $weekItems)
                                    <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                                        {{-- Header Semana --}}
                                        <div class="bg-indigo-50/50 dark:bg-indigo-900/20 px-8 py-5 border-b border-gray-100 dark:border-gray-800 flex justify-between items-center">
                                            <div class="flex items-center space-x-4">
                                                <div class="w-10 h-10 bg-indigo-600 rounded-xl flex items-center justify-center shadow-lg shadow-indigo-200 dark:shadow-none">
                                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                                </div>
                                                <div>
                                                    <h3 class="text-xs font-black text-indigo-600 dark:text-indigo-400 uppercase tracking-widest italic">Semana Fiscal</h3>
                                                    <p class="text-xl font-black text-gray-900 dark:text-white leading-none mt-1">{{ $week }}</p>
                                                </div>
                                            </div>
                                            <div class="text-right">
                                                <h3 class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Total Semana</h3>
                                                <p class="text-2xl font-black text-indigo-700 dark:text-indigo-400 leading-none mt-1">${{ number_format($weekItems->sum('total'), 2) }}</p>
                                            </div>
                                        </div>

                                        {{-- Lista de Centros de Costos --}}
                                        <div class="p-4 space-y-2">
                                            @php
                                                $groupedByCC = $weekItems->groupBy(function($item) {
                                                    return $item->costCenter->name ?? 'Sin Centro de Costos';
                                                });
                                            @endphp
                                            @foreach($groupedByCC as $ccName => $ccItems)
                                                @php
                                                    $cc = $ccItems->first()->costCenter;
                                                    $internalId = ($cc->abbreviation ?? 'SCC') . '-' . $week;
                                                    $invoiceCount = $ccItems->whereNotNull('uuid')->count();
                                                    $ticketCount = $ccItems->where('folio', 'SIN-FACTURA')->count();
                                                    $userCount = $ccItems->pluck('user_id')->unique()->count();
                                                @endphp
                                                <a href="{{ route('reimbursements.audit', ['week' => $week, 'cc' => $ccName]) }}" 
                                                   class="flex flex-col md:flex-row items-start md:items-center justify-between p-4 bg-gray-50 dark:bg-gray-900/30 hover:bg-white dark:hover:bg-gray-800 rounded-2xl border border-transparent hover:border-indigo-100 dark:hover:border-indigo-900 hover:shadow-md transition-all group no-underline space-y-3 md:space-y-0">
                                                    <div class="flex items-center space-x-4">
                                                        <div class="flex flex-col">
                                                            <span class="text-[10px] font-black text-indigo-500 uppercase tracking-widest italic opacity-70">{{ $internalId }}</span>
                                                            <span class="text-sm font-black text-gray-700 dark:text-gray-300 uppercase tracking-tight group-hover:text-indigo-600 dark:group-hover:text-indigo-400">{{ $ccName }}</span>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="flex flex-wrap items-center gap-4 md:gap-8">
                                                        <div class="flex flex-col items-center">
                                                            <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest">Facturas</span>
                                                            <span class="text-sm font-black text-gray-900 dark:text-white">{{ $invoiceCount }}</span>
                                                        </div>
                                                        <div class="flex flex-col items-center">
                                                            <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest">Tickets</span>
                                                            <span class="text-sm font-black text-gray-900 dark:text-white">{{ $ticketCount }}</span>
                                                        </div>
                                                        <div class="flex flex-col items-center">
                                                            <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest">Solicitantes</span>
                                                            <span class="text-sm font-black text-gray-900 dark:text-white">{{ $userCount }}</span>
                                                        </div>
                                                        <div class="text-right ml-4">
                                                            <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest block">Total</span>
                                                            <span class="text-lg font-black text-gray-900 dark:text-white">${{ number_format($ccItems->sum('total'), 2) }}</span>
                                                        </div>
                                                        <div class="p-2 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700 group-hover:bg-indigo-600 transition-colors">
                                                            <svg class="w-4 h-4 text-gray-400 group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                                        </div>
                                                    </div>
                                                </a>
                                            @endforeach
                                        </div>
                                    </div>
                                @empty
                                    <div class="p-20 text-center bg-gray-50 dark:bg-gray-800 rounded-3xl border-2 border-dashed border-gray-200 dark:border-gray-700">
                                        <p class="text-gray-400 font-black uppercase tracking-widest text-sm">Sin datos para procesar en el flujo seleccionado</p>
                                    </div>
                                @endforelse
                            </div>

                            </div>

                        @else
                            <div class="overflow-x-auto relative shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            <button type="button" class="sort-header flex items-center group focus:outline-none" data-sort="folio">
                                                Folio / UUID
                                                <span class="ml-1">
                                                    @if(request('sort_by') == 'folio')
                                                        @if(request('sort_order', 'desc') == 'asc')
                                                            <svg class="w-3 h-3 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
                                                        @else
                                                            <svg class="w-3 h-3 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                                        @endif
                                                    @else
                                                        <svg class="w-3 h-3 opacity-0 group-hover:opacity-50 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5 5 5-5"></path></svg>
                                                    @endif
                                                </span>
                                            </button>
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            <button type="button" class="sort-header flex items-center group focus:outline-none" data-sort="fecha">
                                                Fecha
                                                <span class="ml-1">
                                                    @if(request('sort_by') == 'fecha')
                                                        @if(request('sort_order', 'desc') == 'asc')
                                                            <svg class="w-3 h-3 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
                                                        @else
                                                            <svg class="w-3 h-3 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                                        @endif
                                                    @else
                                                        <svg class="w-3 h-3 opacity-0 group-hover:opacity-50 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5 5 5-5"></path></svg>
                                                    @endif
                                                </span>
                                            </button>
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            <button type="button" class="sort-header flex items-center group focus:outline-none" data-sort="nombre_emisor">
                                                Emisor
                                                <span class="ml-1">
                                                    @if(request('sort_by') == 'nombre_emisor')
                                                        @if(request('sort_order', 'desc') == 'asc')
                                                            <svg class="w-3 h-3 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
                                                        @else
                                                            <svg class="w-3 h-3 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                                        @endif
                                                    @else
                                                        <svg class="w-3 h-3 opacity-0 group-hover:opacity-50 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5 5 5-5"></path></svg>
                                                    @endif
                                                </span>
                                            </button>
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            <button type="button" class="sort-header flex items-center group focus:outline-none" data-sort="total">
                                                Total
                                                <span class="ml-1">
                                                    @if(request('sort_by') == 'total')
                                                        @if(request('sort_order', 'desc') == 'asc')
                                                            <svg class="w-3 h-3 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
                                                        @else
                                                            <svg class="w-3 h-3 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                                        @endif
                                                    @else
                                                        <svg class="w-3 h-3 opacity-0 group-hover:opacity-50 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5 5 5-5"></path></svg>
                                                    @endif
                                                </span>
                                            </button>
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Solicitante / Centro de Costos
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            <button type="button" class="sort-header flex items-center group focus:outline-none" data-sort="status">
                                                Estatus / Ubicación
                                                <span class="ml-1">
                                                    @if(request('sort_by') == 'status')
                                                        @if(request('sort_order', 'desc') == 'asc')
                                                            <svg class="w-3 h-3 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
                                                        @else
                                                            <svg class="w-3 h-3 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                                        @endif
                                                    @else
                                                        <svg class="w-3 h-3 opacity-0 group-hover:opacity-50 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5 5 5-5"></path></svg>
                                                    @endif
                                                </span>
                                            </button>
                                        </th>
                                        <th scope="col" class="relative px-6 py-3">
                                            <span class="sr-only">Acciones</span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach ($reimbursements as $r)
                                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                            <div class="flex flex-col">
                                <span>{{ $r->folio ? $r->folio : substr($r->uuid, 0, 8).'...' }}</span>
                                <span class="text-xs text-gray-500 font-normal">
                                    {{ ucfirst(str_replace('_', ' ', $r->type ?? 'Reembolso')) }}
                                </span>
                            </div>
                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            {{ $r->fecha ? \Carbon\Carbon::parse($r->fecha)->format('d/m/Y') : '-' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            {{ \Illuminate\Support\Str::limit($r->nombre_emisor, 20) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            ${{ number_format($r->total, 2) }} {{ $r->moneda }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            <div class="flex flex-col">
                                                <span class="font-bold">{{ $r->user->name ?? 'N/A' }}</span>
                                                <span class="text-xs">{{ $r->costCenter->name ?? 'Sin C.C.' }}</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            <div class="flex flex-col space-y-1">
                                                <span class="px-2 w-fit inline-flex text-xs leading-5 font-semibold rounded-full 
                                                    {{ $r->status === 'aprobado' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : '' }}
                                                    {{ $r->status === 'rechazado' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300' : '' }}
                                                    {{ $r->status === 'requiere_correccion' ? 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300' : '' }}
                                                    {{ $r->status === 'aprobado_director' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300' : '' }}
                                                    {{ $r->status === 'aprobado_control' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300' : '' }}
                                                    {{ $r->status === 'aprobado_ejecutivo' ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-300' : '' }}
                                                    {{ $r->status === 'aprobado_cxp' ? 'bg-cyan-100 text-cyan-800 dark:bg-cyan-900 dark:text-cyan-300' : '' }}
                                                    {{ $r->status === 'aprobado_direccion' ? 'bg-teal-100 text-teal-800 dark:bg-teal-900 dark:text-teal-300' : '' }}
                                                    {{ $r->status === 'pendiente' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300' : '' }}
                                                    {{ $r->status === 'en_evento' ? 'bg-slate-100 text-slate-800 dark:bg-slate-900 dark:text-slate-300 border border-dashed border-slate-300' : '' }}
                                                ">
                                                    @if($r->status === 'aprobado') Pagado 
                                                    @elseif($r->status === 'aprobado_cxp') Aprobado Subdirección
                                                    @elseif($r->status === 'aprobado_direccion') Aprobado Dirección
                                                    @elseif($r->status === 'en_evento') En Cola (Evento)
                                                    @else {{ ucfirst(str_replace('_', ' ', $r->status)) }} @endif
                                                </span>
                                                <span class="text-[10px] text-gray-400 font-medium italic">
                                                    @if($r->status === 'pendiente') 
                                                        En: {{ $r->costCenter->director->name ?? 'Director' }} (N1)
                                                    @elseif($r->status === 'aprobado_director') 
                                                        En: {{ $r->costCenter->controlObra->name ?? 'Control de Obra' }} (N2)
                                                    @elseif($r->status === 'aprobado_control') 
                                                        En: {{ $r->costCenter->directorEjecutivo->name ?? 'Dir. Ejecutivo' }} (N3)
                                                    @elseif($r->status === 'aprobado_ejecutivo') 
                                                        En: Subdirección
                                                    @elseif($r->status === 'aprobado_cxp') 
                                                        En: Dirección
                                                    @elseif($r->status === 'aprobado_direccion') 
                                                        En: Cuentas por Pagar
                                                    @elseif($r->status === 'aprobado') Finalizado
                                                    @elseif($r->status === 'en_evento') Esperando cierre de viaje
                                                    @else Rechazado/Corregir
                                                    @endif
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                            @php
                                                $user = Auth::user();
                                                $isOwnerOrDesignatedApprover = $r->user_id === $user->id || 
                                                    ($user->isAdmin() || $user->isCxp() || $user->isDireccion() || $user->isTreasury() || $user->isAdminView()) ||
                                                    ($user->isDirector() && $r->costCenter->director_id === $user->id) ||
                                                    ($user->isControlObra() && $r->costCenter->control_obra_id === $user->id) ||
                                                    ($user->isExecutiveDirector() && $r->costCenter->director_ejecutivo_id === $user->id);
                                            @endphp

                                            @if($isOwnerOrDesignatedApprover)
                                                <a href="{{ route('reimbursements.show', $r->id) }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-600">Ver</a>
                                                
                                                @php
                                                    $canApproveCurr = ($user->isDirector() && $r->status === 'pendiente' && $r->costCenter->director_id === $user->id) ||
                                                                     ($user->isControlObra() && $r->status === 'aprobado_director' && $r->costCenter->control_obra_id === $user->id) ||
                                                                     ($user->isExecutiveDirector() && $r->status === 'aprobado_control' && $r->costCenter->director_ejecutivo_id === $user->id) ||
                                                                     ($user->isCxp() && $r->status === 'aprobado_ejecutivo') ||
                                                                     ($user->isDireccion() && $r->status === 'aprobado_cxp') ||
                                                                     (($user->isTreasury() || $user->isAdmin()) && $r->status === 'aprobado_direccion');
                                                @endphp

                                                @if($canApproveCurr)
                                                    <form action="{{ route('reimbursements.update', $r->id) }}" method="POST" class="inline">
                                                        @csrf
                                                        @method('PUT')
                                                        <input type="hidden" name="status" value="aprobado">
                                                        <button type="submit" class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-600 ml-2">Aprobar</button>
                                                    </form>
                                                    <button type="button" x-data @click="$dispatch('open-rejection-modal', { url: '{{ route('reimbursements.update', $r->id) }}' })" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-600 ml-2">Rechazar</button>
                                                @endif
                                            @else
                                                <span class="text-gray-400 italic text-xs">Consulta (Sin Permiso)</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if(!isset($weeklySummary))
                        <div class="mt-8 px-4 py-3 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-700 sm:rounded-b-lg">
                            {{ $reimbursements->links() }}
                        </div>
                        @endif
                        @endif
                    </div>
                 </div>
            </div>
        </div>
    </div>
    
    <!-- Bulk Approval Modal -->
    <div x-data="{ open: false }" 
         @open-bulk-approval-modal.window="open = true" 
         x-show="open" 
         class="fixed z-10 inset-0 overflow-y-auto" 
         style="display: none;">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity" aria-hidden="true" @click="open = false">
                <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form action="{{ route('reimbursements.bulk_approve') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-green-100 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100">
                                    Aprobación Masiva de Reembolsos
                                </h3>
                                <div class="mt-2">
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
                                        Sube el archivo CSV exportado del sistema. El sistema buscará el <strong>Folio</strong> y el <strong>UUID</strong> para marcar los reembolsos como <strong>Pagados</strong> (Aprobación Final).
                                    </p>
                                    
                                    <div class="space-y-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Archivo CSV</label>
                                            <input type="file" name="csv_file" accept=".csv" class="mt-1 block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-gray-700 dark:file:text-gray-300" required>
                                        </div>
                                        
                                        <div class="p-3 bg-blue-50 dark:bg-blue-900/30 border border-blue-100 dark:border-blue-800 rounded-lg text-[11px] text-blue-700 dark:text-blue-300">
                                            <strong>Nota:</strong> Esta acción es definitiva y notificará a los usuarios que su reembolso ha sido pagado.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Procesar Aprobación
                        </button>
                        <button type="button" @click="open = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>    

    <!-- Rejection Modal -->
    <div x-data="{ open: false, actionUrl: '', reasons: [
        'Falta comprobante fiscal (XML/PDF)',
        'El monto no coincide con la factura',
        'Gasto no autorizado',
        'Fuera de política de viáticos',
        'Duplicado de solicitud',
        'Error en centro de costos',
        'Falta justificación detallada',
        'Fecha fuera del periodo permitido',
        'Excede el límite de gastos',
        'Otro'
    ] }" 
         @open-rejection-modal.window="open = true; actionUrl = $event.detail.url" 
         x-show="open" 
         class="fixed z-10 inset-0 overflow-y-auto" 
         style="display: none;">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity" aria-hidden="true" @click="open = false">
                <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form :action="actionUrl" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100" id="modal-title">
                                    Rechazar Reembolso
                                </h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                                        Por favor, seleccione una razón para rechazar este reembolso.
                                    </p>
                                    <label for="rejection_reason" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Razón de Rechazo</label>
                                    <select name="rejection_reason" id="rejection_reason" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>
                                        <option value="">Seleccione una opción</option>
                                        <template x-for="reason in reasons" :key="reason">
                                            <option :value="reason" x-text="reason"></option>
                                        </template>
                                    </select>
                                    
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mt-4">Tipo de Rechazo</label>
                                    <div class="mt-2 space-y-2">
                                        <div class="flex items-center">
                                            <input id="rechazo_correccion" name="status" type="radio" value="requiere_correccion" class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 dark:bg-gray-700 dark:border-gray-600" required>
                                            <label for="rechazo_correccion" class="ml-3 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                                Requiere Corrección (El usuario podrá actualizar archivos y reenviar)
                                            </label>
                                        </div>
                                        <div class="flex items-center">
                                            <input id="rechazo_definitivo" name="status" type="radio" value="rechazado" class="focus:ring-red-500 h-4 w-4 text-red-600 border-gray-300 dark:bg-gray-700 dark:border-gray-600">
                                            <label for="rechazo_definitivo" class="ml-3 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                                Rechazo Definitivo (No se podrá modificar)
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <label for="rejection_comment" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mt-4">Comentario Adicional (Opcional)</label>
                                    <textarea name="rejection_comment" id="rejection_comment" rows="3" class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 mt-1 block w-full sm:text-sm border-gray-300 dark:bg-gray-700 dark:text-gray-300 rounded-md"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Confirmar Rechazo
                        </button>
                        <button type="button" @click="open = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('filter-form');
            const container = document.getElementById('results-container');
            
            window.exportData = function() {
                const fromDate = document.getElementById('from_date').value;
                const toDate = document.getElementById('to_date').value;
                
                /* 
                if (!fromDate || !toDate) {
                    alert('Por favor selecciona un rango de fechas (Desde y Hasta) para exportar. \n\nNota: La búsqueda incluye tanto la fecha de creación del reembolso como la fecha de expedición del XML.');
                    return;
                }
                */
                
                const params = new URLSearchParams(new FormData(form)).toString();
                window.location.href = "{{ route('reimbursements.export') }}?" + params;
            }
            
            // Function to handle fetching and updating
            function fetchResults(url) {
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
                    
                    window.history.pushState({}, '', url);
                    
                    attachPaginationListeners();
                    attachSortListeners();
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
                    if (this.type === 'date') return;
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(() => {
                        submitFilter();
                    }, 500);
                });
                
                input.addEventListener('change', function() {
                    submitFilter();
                });
            });

            // Listen to selects
            form.querySelectorAll('select').forEach(select => {
                select.addEventListener('change', function() {
                    submitFilter();
                });
            });
            
            // Handle Sorting
            function attachSortListeners() {
                container.querySelectorAll('.sort-header').forEach(header => {
                    header.addEventListener('click', function() {
                        const sortBy = this.getAttribute('data-sort');
                        const sortByInput = document.getElementById('input-sort-by');
                        const sortOrderInput = document.getElementById('input-sort-order');
                        
                        let newOrder = 'asc';
                        if (sortBy === sortByInput.value) {
                            newOrder = sortOrderInput.value === 'asc' ? 'desc' : 'asc';
                        }
                        
                        sortByInput.value = sortBy;
                        sortOrderInput.value = newOrder;
                        
                        submitFilter();
                    });
                });
            }
            
            // Handle Pagination Clicks
            function attachPaginationListeners() {
                const links = container.querySelectorAll('a.page-link, .pagination a'); 
                links.forEach(link => {
                     link.addEventListener('click', function(e) {
                         e.preventDefault();
                         fetchResults(this.href);
                     });
                });
            }
            
            // Initial attach
            attachPaginationListeners();
            attachSortListeners();
        });
    </script>
</x-app-layout>
