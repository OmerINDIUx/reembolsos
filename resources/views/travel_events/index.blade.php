<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h2 class="font-black text-3xl text-gray-900 dark:text-white leading-tight uppercase tracking-tighter">
                    Viajes y Eventos
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 font-medium">Control de presupuestos y justificaciones para eventos especiales.</p>
            </div>
            
            <a href="{{ route('travel_events.create') }}" class="inline-flex items-center px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-black uppercase tracking-widest rounded-xl transition-all shadow-lg hover:shadow-indigo-500/30">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Nuevo Registro
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            @if(session('success'))
                <div class="mb-8 p-4 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-100 dark:border-emerald-800 rounded-2xl flex items-center text-emerald-600 dark:text-emerald-400 text-sm font-bold uppercase tracking-wide">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    {{ session('success') }}
                </div>
            @endif

            <!-- Table View -->
            <div class="bg-white dark:bg-gray-800 rounded-[2.5rem] shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700 text-sm">
                        <thead class="bg-gray-50/50 dark:bg-gray-900/50">
                            <tr>
                                <th class="px-8 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest">Código / Nombre</th>
                                <th class="px-8 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest">Centro de Costos</th>
                                <th class="px-8 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest">Ubicación / Fechas</th>
                                <th class="px-8 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest">Creador / Director</th>
                                <th class="px-8 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest">Estatus</th>
                                <th class="px-8 py-4 text-right text-[10px] font-black text-gray-400 uppercase tracking-widest">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-700">
                            @forelse($travelEvents as $event)
                            <tr class="hover:bg-gray-50/30 dark:hover:bg-gray-900/10 transition-colors">
                                <td class="px-8 py-5">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600 dark:text-indigo-400 font-black">
                                            {{ substr($event->name, 0, 1) }}
                                        </div>
                                        <div>
                                            <div class="text-sm font-black text-gray-900 dark:text-white uppercase tracking-tight">{{ $event->name }}</div>
                                            <div class="text-[10px] font-bold text-indigo-500 uppercase">{{ $event->code }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-8 py-5">
                                    <div class="text-xs font-black text-indigo-600 dark:text-indigo-400 uppercase">{{ $event->costCenter->name ?? 'No Asignado' }}</div>
                                    <div class="text-[9px] text-gray-400 font-bold uppercase">Línea de Aprobación</div>
                                </td>
                                <td class="px-8 py-5">
                                    <div class="text-[10px] text-gray-400 font-medium">
                                        {{ $event->start_date ? \Carbon\Carbon::parse($event->start_date)->format('d M') : '...' }} 
                                        - 
                                        {{ $event->end_date ? \Carbon\Carbon::parse($event->end_date)->format('d M Y') : '...' }}
                                    </div>
                                </td>
                                <td class="px-8 py-5">
                                    <div class="text-xs font-bold text-gray-900 dark:text-white">{{ $event->user->name ?? 'Sistema' }}</div>
                                    <div class="text-[10px] text-amber-500 font-black uppercase">Dir: {{ $event->director->name ?? '-' }}</div>
                                </td>
                                <td class="px-8 py-5">
                                    <span class="px-3 py-1 inline-flex text-[9px] leading-4 font-black rounded-full uppercase tracking-widest
                                        {{ $event->status === 'active' ? 'bg-indigo-100 text-indigo-800' : '' }}
                                        {{ $event->status === 'completed' ? 'bg-emerald-100 text-emerald-800' : '' }}
                                        {{ $event->status === 'cancelled' ? 'bg-rose-100 text-rose-800' : '' }}
                                    ">
                                        {{ $event->status }}
                                    </span>
                                </td>
                                <td class="px-8 py-5 text-right space-x-2">
                                    <a href="{{ route('travel_events.edit', $event) }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 font-black text-xs uppercase underline underline-offset-4 decoration-dotted">Editar</a>
                                    
                                    <form action="{{ route('travel_events.destroy', $event) }}" method="POST" class="inline" onsubmit="return confirm('¿Seguro que deseas eliminar este registro?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-500 hover:text-red-700 font-black text-xs uppercase">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="px-8 py-12 text-center">
                                    <div class="flex flex-col items-center">
                                        <svg class="w-12 h-12 text-gray-200 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        <p class="text-xs font-black uppercase text-gray-400 tracking-widest">No hay viajes o eventos registrados aún.</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                @if($travelEvents->hasPages())
                <div class="px-8 py-4 bg-gray-50/50 dark:bg-gray-900/50 border-t border-gray-100 dark:border-gray-700">
                    {{ $travelEvents->links() }}
                </div>
                @endif
            </div>
            
        </div>
    </div>
</x-app-layout>
