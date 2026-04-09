@props(['action', 'periods'])

<div x-data="{ 
    periodType: '{{ request('period_type', 'week') }}'
}" class="bg-white dark:bg-gray-800 rounded-[2rem] shadow-xl border border-gray-100 dark:border-gray-700 p-8 mb-8 mt-4 animate-fadeIn border-t-4 border-t-indigo-500">
    <form action="{{ $action }}" method="GET" class="flex flex-col xl:flex-row items-start xl:items-end gap-6">
        @foreach(request()->except(['period_type', 'period_week', 'period_month', 'period_quarter', 'period_year']) as $key => $val)
            @if(!is_array($val))
                <input type="hidden" name="{{ $key }}" value="{{ $val }}">
            @endif
        @endforeach

        <!-- Selector de Tipo -->
        <div class="w-full xl:w-72">
            <label class="block text-[10px] font-black uppercase text-gray-400 tracking-[0.2em] mb-3 ml-1">Filtrar por Periodo</label>
            <div class="relative group">
                <select name="period_type" x-model="periodType" class="w-full bg-gray-50 dark:bg-gray-900 border-2 border-gray-100 dark:border-gray-700 rounded-2xl py-3.5 px-5 text-sm font-bold focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all appearance-none cursor-pointer">
                    <option value="week">Semanas (Folio Fiscal)</option>
                    <option value="month">Mes Específico</option>
                    <option value="quarter">Trimestre Histórico</option>
                    <option value="year">Año Completo</option>
                </select>
                <div class="absolute right-5 top-1/2 -translate-y-1/2 pointer-events-none text-indigo-500">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"></path></svg>
                </div>
            </div>
        </div>

        <!-- Selector de Valor Dinámico -->
        <div class="w-full xl:w-72">
            <label class="block text-[10px] font-black uppercase text-gray-400 tracking-[0.2em] mb-3 ml-1">Seleccionar Valor</label>
            
            <div class="relative group" style="min-height: 52px;">
                <!-- Week -->
                <div x-show="periodType === 'week'">
                    <select name="period_week" class="w-full bg-gray-50 dark:bg-gray-900 border-2 border-gray-100 dark:border-gray-700 rounded-2xl py-3.5 px-5 text-sm font-bold focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all appearance-none">
                        <option value="">-- Todas las Semanas --</option>
                        @foreach($periods['weeks'] as $week)
                            <option value="{{ $week }}" {{ request('period_week') == $week ? 'selected' : '' }}>Semana {{ $week }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Month -->
                <div x-show="periodType === 'month'" x-cloak>
                    <input type="month" name="period_month" value="{{ request('period_month') }}" class="w-full bg-gray-50 dark:bg-gray-900 border-2 border-gray-100 dark:border-gray-700 rounded-2xl py-3.5 px-5 text-sm font-bold focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all">
                </div>

                <!-- Quarter -->
                <div x-show="periodType === 'quarter'" x-cloak>
                    <select name="period_quarter" class="w-full bg-gray-50 dark:bg-gray-900 border-2 border-gray-100 dark:border-gray-700 rounded-2xl py-3.5 px-5 text-sm font-bold focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all appearance-none">
                        <option value="">-- Seleccionar Trimestre Histórico --</option>
                        @php $selectedQuarter = request('period_quarter'); @endphp
                        @foreach($periods['quarters'] as $q)
                            <option value="{{ $q['value'] }}" {{ $selectedQuarter == $q['value'] ? 'selected' : '' }}>
                                {{ $q['label'] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Year -->
                <div x-show="periodType === 'year'" x-cloak>
                    <select name="period_year" class="w-full bg-gray-50 dark:bg-gray-900 border-2 border-gray-100 dark:border-gray-700 rounded-2xl py-3.5 px-5 text-sm font-bold focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all appearance-none">
                        @foreach($periods['years'] as $year)
                            <option value="{{ $year }}" {{ request('period_year') == $year ? 'selected' : '' }}>Año {{ $year }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Shared Icon for non-month types (absolute positioned) -->
                <div x-show="periodType !== 'month'" class="absolute right-5 top-1/2 -translate-y-1/2 pointer-events-none text-indigo-500">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"></path></svg>
                </div>
            </div>
        </div>

        <!-- Botones de Acción -->
        <div class="flex gap-3 w-full xl:w-auto xl:ml-auto">
            <button type="submit" class="flex-1 xl:flex-none bg-indigo-600 hover:bg-indigo-700 text-white font-black px-10 py-4 rounded-2xl text-[10px] uppercase tracking-[0.2em] shadow-xl shadow-indigo-500/20 transition-all hover:-translate-y-1 active:scale-95 flex items-center justify-center">
                <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                Aplicar Filtro
            </button>
            
            <a href="{{ $action }}" class="bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-500 dark:text-gray-400 font-black px-8 py-4 rounded-2xl text-[10px] uppercase tracking-[0.2em] transition-all flex items-center justify-center">
                Limpiar
            </a>
        </div>
    </form>
</div>
