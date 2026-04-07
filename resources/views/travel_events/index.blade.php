<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Viajes y Eventos') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Registros de Viajes</h3>
                <a href="{{ route('travel_events.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 transition-all shadow-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Nuevo Registro
                </a>
            </div>
            
            @if(session('success'))
                <div class="mb-8 p-4 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-100 dark:border-emerald-800 rounded-2xl flex items-center text-emerald-600 dark:text-emerald-400 text-sm font-bold uppercase tracking-wide">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    {{ session('success') }}
                </div>
            @endif

            <!-- Table View -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg border border-gray-200 dark:border-gray-700">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Código / Nombre</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Centro de Costos</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Ubicación / Fechas</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Creador / Director</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Estatus</th>
                                <th class="relative px-6 py-3"><span class="sr-only">Acciones</span></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($travelEvents as $event)
                            <tr class="hover:bg-gray-50/30 dark:hover:bg-gray-900/10 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600 dark:text-indigo-400 font-black">
                                            {{ substr($event->name, 0, 1) }}
                                        </div>
                                        <div>
                                            <a href="{{ route('travel_events.show', $event) }}" class="text-sm font-black text-gray-900 dark:text-white uppercase tracking-tight hover:text-indigo-600 transition-colors">{{ $event->name }}</a>
                                            <div class="flex items-center gap-2 mt-0.5">
                                                <div class="text-[10px] font-bold text-indigo-500 uppercase">{{ $event->code }}</div>
                                                <span class="text-[8px] px-1.5 py-0.5 bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 rounded font-black uppercase tracking-tighter">{{ $event->trip_type }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-xs font-black text-indigo-600 dark:text-indigo-400 uppercase">{{ $event->costCenter->name ?? 'No Asignado' }}</div>
                                    <div class="text-[9px] text-gray-400 font-bold uppercase">Línea de Aprobación</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="font-bold text-gray-700 dark:text-gray-300 text-[10px] uppercase mb-1">{{ $event->location ?? 'S/D' }}</div>
                                    <div class="text-[10px] text-gray-400 font-medium">
                                        {{ $event->start_date ? \Carbon\Carbon::parse($event->start_date)->format('d M') : '...' }} 
                                        - 
                                        {{ $event->end_date ? \Carbon\Carbon::parse($event->end_date)->format('d M Y') : '...' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-xs font-bold text-gray-900 dark:text-white">{{ $event->user->name ?? 'Sistema' }}</div>
                                    <div class="text-[10px] text-amber-500 font-black uppercase tracking-tight">Autoriza: {{ $event->director->name ?? '-' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex flex-col gap-2">
                                        <span class="px-3 py-1 inline-flex text-[9px] leading-4 font-black rounded-full uppercase tracking-widest justify-center
                                            {{ $event->status === 'active' ? 'bg-indigo-100 text-indigo-800' : '' }}
                                            {{ $event->status === 'completed' ? 'bg-emerald-100 text-emerald-800' : '' }}
                                            {{ $event->status === 'cancelled' ? 'bg-rose-100 text-rose-800' : '' }}
                                        ">
                                            {{ $event->status }}
                                        </span>
                                        @if($event->approval_evidence_path)
                                            <span class="text-[8px] font-black text-indigo-500 uppercase tracking-widest text-center">Con Evidencia</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-8 py-5 text-right space-x-3">
                                    <a href="{{ route('travel_events.show', $event) }}" class="text-gray-400 hover:text-indigo-600 transition-colors" title="Ver Detalles">
                                        <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                    </a>
                                    <a href="{{ route('travel_events.edit', $event) }}" class="text-gray-400 hover:text-indigo-600 transition-colors" title="Editar">
                                        <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                    </a>
                                    
                                    <form id="delete-event-form-{{ $event->id }}" action="{{ route('travel_events.destroy', $event) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" 
                                            onclick="confirmDeleteEvent({{ $event->id }})"
                                            class="text-gray-400 hover:text-red-600 transition-colors" title="Eliminar">
                                            <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
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

        <script>
            function confirmDeleteEvent(id) {
                Swal.fire({
                    title: '<span class="text-xl font-black uppercase tracking-tight text-red-600">¿Eliminar Registro?</span>',
                    html: '<p class="text-sm font-medium text-gray-600 dark:text-gray-400 mt-2">Esta acción no se puede deshacer. Se eliminará el registro del viaje y se desvincularán sus facturas.</p>',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'SÍ, ELIMINAR',
                    cancelButtonText: 'CANCELAR',
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#9ca3af',
                    reverseButtons: true,
                    customClass: {
                        popup: 'rounded-[1.5rem] border-none shadow-2xl dark:bg-gray-800',
                        confirmButton: 'rounded-xl px-8 py-3 font-black text-xs uppercase tracking-widest',
                        cancelButton: 'rounded-xl px-8 py-3 font-black text-xs uppercase tracking-widest'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        document.getElementById('delete-event-form-' + id).submit();
                    }
                });
            }
        </script>
    </div>
</x-app-layout>
