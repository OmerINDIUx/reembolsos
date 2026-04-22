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
                        $allIdentities = collect([$user])->concat($user->substitutingFor()->with('originalUser')->get()->pluck('originalUser')->filter());
                        $canManage = $allIdentities->contains(fn($identity) => $identity->isAdmin() || $identity->isAdminView() || $identity->isCxp() || $identity->isTreasury() || $identity->isDireccion() || $identity->isDirector() || $identity->isControlObra() || $identity->isExecutiveDirector() || $identity->hasPendingApprovals());
                        $defaultTab = $canManage ? 'management' : 'active';
                    @endphp
                    <div class="flex flex-col md:flex-row justify-between items-center mb-6 space-y-4 md:space-y-0">
                        <h3 class="text-lg font-medium">Listado de Reembolsos</h3>
                        <div class="flex space-x-2">
                            @if($user->canPerform('reimbursements.export'))
                            <button type="button" x-data @click="$dispatch('open-caratula-pdf-modal')" class="inline-flex items-center px-4 py-2 bg-emerald-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-emerald-500 focus:bg-emerald-700 active:bg-emerald-900 transition ease-in-out duration-150">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                Descargar Carátulas (PDF)
                            </button>
                            @endif
                            @if($user->canPerform('reimbursements.bulk_approve'))
                            <button type="button" x-data @click="$dispatch('open-bulk-approval-modal')" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-500 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                                Aprobación Masiva (CSV)
                            </button>
                            @endif
                             <a href="{{ route('reimbursements.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                                 Nuevo Reembolso
                            </a>

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
                            <div class="col-span-1 md:col-span-3">
                                <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Buscador General</label>
                                <input type="text" name="search" id="search" value="{{ request('search') }}" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="Busca cualquier dato del reembolso...">
                            </div>

                            <!-- Cost Center Filter -->
                            <div class="col-span-1 md:col-span-3">
                                <label for="cost_center_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Centro de Costos / Obra</label>
                                <select name="cost_center_id" id="cost_center_id" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    <option value="">Todos los permitidos</option>
                                    @foreach($authorizedCCs as $acc)
                                        <option value="{{ $acc->id }}" {{ request('cost_center_id') == $acc->id ? 'selected' : '' }}>{{ $acc->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Week From -->
                            <div class="col-span-1 md:col-span-2">
                                <label for="from_week" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Semana Desde</label>
                                <select name="from_week" id="from_week" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    <option value="">Cualquiera</option>
                                    @foreach($availableWeeks as $aw)
                                        <option value="{{ $aw }}" {{ request('from_week') == $aw ? 'selected' : '' }}>Semana {{ $aw }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Week To -->
                            <div class="col-span-1 md:col-span-2">
                                <label for="to_week" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Semana Hasta</label>
                                <select name="to_week" id="to_week" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    <option value="">Cualquiera</option>
                                    @foreach($availableWeeks as $aw)
                                        <option value="{{ $aw }}" {{ request('to_week') == $aw ? 'selected' : '' }}>Semana {{ $aw }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Buttons -->
                            <div class="col-span-1 md:col-span-2 flex justify-end items-end space-x-2">
                                <a href="{{ route('reimbursements.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-200 dark:bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-gray-700 dark:text-gray-200 uppercase tracking-widest hover:bg-gray-300 dark:hover:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150 h-[38px]">
                                    Limpiar
                                </a>
                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150 h-[38px]">
                                    Filtrar
                                </button>
                                
                                @if(Auth::user()->canPerform('reimbursements.export'))
                                <div class="ml-2 pl-2 border-l border-gray-300 dark:border-gray-600 flex items-center">
                                    <button type="button" x-data @click="$dispatch('open-export-modal')" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-500 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150 h-[38px]">
                                        Exportar
                                    </button>
                                </div>
                                @endif
                            </div>
                        </form>
                    </div>

                    @php
                        $tab = request('tab', $defaultTab);
                        $isGroupedView = ($tab === 'management' || $tab === 'weekly_summary' || $tab === 'active' || $tab === 'history' || $tab === 'global_history' || $tab === 'audit');
                    @endphp

                    <div x-data="bulkAuditIndex()" class="relative border-transparent">
                        {{-- Modal included here to ensure it is always in the same scope --}}
                        @include('reimbursements.partials.bulk-index-modal')

                        <div id="results-container">
                            @if($isGroupedView)
                                @php
                                    $user = Auth::user();
                                    $substitutes = $user->substitutingFor()->with('originalUser')->get()->keyBy('original_user_id');
                                    
                                    $groupedData = [];
                                    foreach($reimbursements as $item) {
                                        $week = $item->week;
                                        if ($tab === 'management' || $tab === 'weekly_summary') {
                                            $targetUserId = $item->currentStep->user_id ?? null;
                                        } else {
                                            $targetUserId = $item->user_id;
                                        }

                                        if ($targetUserId === $user->id) {
                                            $ctx = ($tab === 'management' || $tab === 'weekly_summary') ? 'Mis Pendientes' : 'Mis Reembolsos';
                                        } elseif ($substitutes->has($targetUserId)) {
                                            $ctx = 'En sustitución de ' . ($substitutes[$targetUserId]->originalUser->name ?? 'Usuario');
                                        } else {
                                            if ($tab === 'management' || $tab === 'weekly_summary') {
                                                $ctx = $item->currentStep->user->name ?? ($item->user->name ?? 'Sin Usuario');
                                            } else {
                                                $ctx = $item->user->name ?? 'Sin Usuario';
                                            }
                                        }
                                        
                                        $groupKey = $week . '|||' . $ctx;
                                if (!isset($groupedData[$groupKey])) {
                                    $groupedData[$groupKey] = [
                                        'week' => $week,
                                        'context' => $ctx,
                                        'items' => collect()
                                    ];
                                }
                                $groupedData[$groupKey]['items']->push($item);
                                    }
                                @endphp

                            <div class="space-y-6">
                                @if($tab !== 'management')
                                <!-- Action Bar for Group (Inline) -->
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
                                @endif
                                @forelse($groupedData as $groupKey => $group)
                                    @php
                                        $userName = $group['context'];
                                        $userItems = $group['items'];
                                        $week = $group['week'];
                                    @endphp
                                    <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                                        {{-- Header Responsable --}}
                                        <div class="bg-indigo-50/50 dark:bg-indigo-900/20 px-8 py-5 border-b border-gray-100 dark:border-gray-800 flex justify-between items-center">
                                            <div class="flex items-center space-x-4">
                                                <div class="w-10 h-10 bg-indigo-600 rounded-xl flex items-center justify-center shadow-lg shadow-indigo-200 dark:shadow-none">
                                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                                </div>
                                                <div>
                                                    <h3 class="text-xs font-black text-indigo-600 dark:text-indigo-400 uppercase tracking-widest italic">Semana {{ $week }}</h3>
                                                    <p class="text-xl font-black text-gray-900 dark:text-white leading-none mt-1">{{ $userName }}</p>
                                                </div>
                                            </div>
                                            <div class="text-right">
                                                <h3 class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Total en Grupo</h3>
                                                <p class="text-2xl font-black text-indigo-700 dark:text-indigo-400 leading-none mt-1">${{ number_format($userItems->sum('total'), 2) }}</p>
                                            </div>
                                        </div>

                                        {{-- Lista de Lotes (Semana + CC) --}}
                                        <div class="p-4 space-y-2">
                                            @php
                                                $groupedByBatch = $userItems->groupBy(function($item) {
                                                    return $item->costCenter->name ?? 'Sin Centro de Costos';
                                                });
                                            @endphp
                                            @foreach($groupedByBatch as $batchName => $batchItems)
                                                @php
                                                    $first = $batchItems->first();
                                                    $week = $first->week;
                                                    $ccName = $first->costCenter->name ?? 'Sin Centro de Costos';
                                                    $cc = $first->costCenter;
                                                    $internalId = ($cc->abbreviation ?? 'SCC') . '-' . $week;
                                                    $invoiceCount = $batchItems->whereNotNull('uuid')->count();
                                                    $ticketCount = $batchItems->whereNull('uuid')->count();
                                                    
                                                    $mismatchCount = 0;
                                                    foreach($batchItems as $rcr) {
                                                        $val = $rcr->validation_data ?? [];
                                                        if (!($val['uuid_match'] ?? true) || !($val['total_match'] ?? true)) {
                                                            $mismatchCount++;
                                                        }
                                                    }
                                                    $idsJson = json_encode($batchItems->pluck('id'));
                                                    $totalAmount = $batchItems->sum('total');
                                                @endphp
                                                <a href="{{ route('reimbursements.audit', ['week' => $week, 'cc' => $ccName, 'tab' => $tab]) }}" 
                                                   class="flex flex-col md:flex-row items-start md:items-center justify-between p-4 bg-gray-50 dark:bg-gray-900/30 hover:bg-white dark:hover:bg-gray-800 rounded-2xl border border-transparent hover:border-indigo-100 dark:hover:border-indigo-900 hover:shadow-md transition-all group no-underline space-y-3 md:space-y-0">
                                                    <div class="flex items-center space-x-4">
                                                        @if($tab !== 'management')
                                                        <div class="flex items-center justify-center border-r border-gray-200 dark:border-gray-700 pr-4" @click.stop>
                                                            <input type="checkbox"
                                                                   data-ids="{{ $idsJson }}"
                                                                   data-amount="{{ $totalAmount }}"
                                                                   data-has-uuid="{{ $ticketCount }}"
                                                                   data-mismatch="{{ $mismatchCount }}"
                                                                   class="cc-group-checkbox w-6 h-6 rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 transition-all duration-200 cursor-pointer" 
                                                                   @change="toggleGroupData($event.target)" />
                                                        </div>
                                                        @endif
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
                                                        <div class="text-right ml-4">
                                                            <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest block">Total</span>
                                                            <span class="text-lg font-black text-gray-900 dark:text-white">${{ number_format($totalAmount, 2) }}</span>
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
                                <span>{{ $r->true_folio }}</span>
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
                                                <span class="px-2 w-fit inline-flex text-[10px] leading-5 font-black uppercase rounded-full 
                                                    {{ $r->status === 'aprobado' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : '' }}
                                                    {{ $r->status === 'rechazado' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300' : '' }}
                                                    {{ $r->status === 'requiere_correccion' ? 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300' : '' }}
                                                    {{ $r->status === 'pendiente' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300' : '' }}
                                                    {{ $r->status === 'pendiente_pago' ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-300' : '' }}
                                                    {{ $r->status === 'borrador' ? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' : '' }}
                                                    {{ !in_array($r->status, ['aprobado', 'rechazado', 'requiere_correccion', 'pendiente', 'pendiente_pago', 'borrador']) ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300' : '' }}
                                                ">
                                                    @if($r->status === 'aprobado') Cuentas por Pagar 
                                                    @elseif($r->status === 'pendiente') {{ $r->currentStep->name ?? 'En Proceso' }}
                                                    @elseif($r->status === 'pendiente_pago') Cuentas por Pagar
                                                    @elseif($r->status === 'requiere_correccion') Corregir
                                                    @else {{ ucfirst(str_replace('_', ' ', $r->status)) }} @endif
                                                </span>
                                                <span class="text-[10px] text-gray-400 font-medium italic">
                                                    @if($r->status === 'pendiente' && $r->currentStep) 
                                                        En: {{ $r->currentStep->user->name ?? 'Por asignar' }}
                                                        @php
                                                            $isSubstituteApproval = false;
                                                            if ($r->currentStep && $r->currentStep->user_id !== Auth::id()) {
                                                                $isSubstituteApproval = Auth::user()->substitutingFor()->where('original_user_id', $r->currentStep->user_id)->exists() || Auth::user()->isAdmin();
                                                            }
                                                        @endphp
                                                        @if($isSubstituteApproval)
                                                            <span class="block mt-1 text-indigo-500 font-bold bg-indigo-50 dark:bg-indigo-900/30 px-2 py-0.5 rounded border border-indigo-200 dark:border-indigo-800 text-[9px] w-fit">A nombre de {{ $r->currentStep->user->name }}</span>
                                                        @endif
                                                    @elseif($r->status === 'pendiente_pago')
                                                        Listo para liquidación final
                                                    @elseif($r->status === 'aprobado') 
                                                        Cuentas por Pagar
                                                    @elseif($r->status === 'requiere_correccion')
                                                        Esperando ajuste del solicitante
                                                    @elseif($r->status === 'rechazado')
                                                        Cancelado definitivamente
                                                    @else
                                                        {{ str_replace('_', ' ', $r->status) }}
                                                    @endif
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                            @php
                                                $user = Auth::user();
                                                $canApproveCurr = $r->canBeApprovedBy($user) && !in_array($r->status, ['aprobado', 'rechazado', 'borrador']);
                                            @endphp

                                            @if($user->id === $r->user_id || $user->isAdmin() || $user->isAdminView() || $canApproveCurr)
                                                <a href="{{ route('reimbursements.show', $r->id) }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-600">Ver</a>
                                                
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
                        @endif

                        @if(isset($weeksPaginator))
                            <div class="mt-8 px-4 py-3 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-700 sm:rounded-b-lg">
                                {{ $weeksPaginator->links() }}
                            </div>
                        @elseif(!isset($weeklySummary) && $reimbursements instanceof \Illuminate\Pagination\AbstractPaginator)
                            <div class="mt-8 px-4 py-3 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-700 sm:rounded-b-lg">
                                {{ $reimbursements->links() }}
                            </div>
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
                                    <p class="text-[11px] text-gray-500 dark:text-gray-400 mb-4 bg-gray-50 dark:bg-gray-700/30 p-2 rounded-md">
                                        Sube el archivo CSV exportado del sistema. El sistema buscará el <strong>Folio</strong>, y confirmará con el <strong>Monto (Total)</strong> o el <strong>UUID</strong> para validar tus tickets sin factura automáticamente y prepararlos para el Pago.
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
            
            window.exportData = function(weekValue = null, type = 'excel') {
                const fromDateEl = document.getElementById('from_date');
                const toDateEl = document.getElementById('to_date');
                const fromDate = fromDateEl ? fromDateEl.value : null;
                const toDate = toDateEl ? toDateEl.value : null;
                
                const params = new URLSearchParams(new FormData(form));
                if (weekValue) {
                    params.set('export_week', weekValue);
                } else if (!(fromDate || toDate)) {
                    // Check if there are week filters instead
                    const fromWeek = document.getElementById('from_week')?.value;
                    const toWeek = document.getElementById('to_week')?.value;
                    if (!fromWeek && !toWeek) {
                        alert('Por favor selecciona una semana o un rango de fechas válido.');
                        return;
                    }
                }
                
                const route = type === 'xml' ? "{{ route('reimbursements.export_xml') }}" : "{{ route('reimbursements.export') }}";
                window.location.href = route + "?" + params.toString();
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
    <!-- Bulk Main Action Modal logic moved into the component above -->

    
    <!-- Export Modal -->
    <div x-data="{ open: false, selectedWeek: '' }" 
         @open-export-modal.window="open = true" 
         x-show="open" 
         class="fixed z-50 inset-0 overflow-y-auto" 
         style="display: none;">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="open = false" aria-hidden="true"></div>
            <div class="inline-block bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:max-w-lg sm:w-full dark:bg-gray-800">
                <div class="px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-green-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-2">Exportar Reembolsos</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Selecciona la semana fiscal que deseas exportar a Excel.</p>
                            
                            @php
                                $exportWeeks = $availableWeeks;
                            @endphp
                            
                            <select x-model="selectedWeek" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-green-500 focus:ring-green-500">
                                <option value="">Selecciona una semana...</option>
                                @foreach($exportWeeks as $ew)
                                    <option value="{{ $ew }}">Semana {{ $ew }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse dark:bg-gray-900/50">
                    <button type="button" @click="exportData(selectedWeek, 'excel'); open = false" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">Excel (CSV)</button>
                    <button type="button" @click="exportData(selectedWeek, 'xml'); open = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">XMLs (ZIP)</button>
                    <button type="button" @click="open = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

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
                    const ids = this.selectedIds.join(',');
                    window.location.href = `{{ route('reimbursements.download_caratula') }}?ids=${ids}`;
                },
                
                init() {
                    // Modal now handled inline with the partial.
                }
            }));
        });
    </script>
    <!-- Caratula PDF Modal -->
    <div x-data="{
        open: false, week: '', cost_center_id: '', loading: false, progress: 0,
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
                                $pdfWeeks = $availableWeeks;
                            @endphp
                            <select x-model="week" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 text-sm font-bold">
                                <option value="">Todas las semanas</option>
                                @foreach($pdfWeeks as $pw)
                                    <option value="{{ $pw }}">Semana {{ $pw }}</option>
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
                                    <option value="{{ $pcc->id }}">{{ $pcc->name }}</option>
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
