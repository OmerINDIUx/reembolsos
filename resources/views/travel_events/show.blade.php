<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Detalles del Viaje: ') . $travelEvent->name }}
        </h2>
    </x-slot>

    <div class="py-12 bg-gray-50/50 dark:bg-gray-950/50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="flex justify-between items-center mb-6">
                <div class="flex items-center gap-3">
                    <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest {{ $travelEvent->status == 'active' ? 'bg-green-100 text-green-700' : ($travelEvent->status == 'completed' ? 'bg-blue-100 text-blue-700' : 'bg-red-100 text-red-700') }}">
                        {{ $travelEvent->status == 'active' ? 'Activo' : ($travelEvent->status == 'completed' ? 'Completado' : 'Cancelado') }}
                    </span>
                    <span class="text-xs text-gray-500 font-bold uppercase tracking-widest">{{ $travelEvent->code }}</span>
                </div>
                
                <div class="flex items-center gap-3">
                    <a href="{{ route('travel_events.edit', $travelEvent) }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md text-xs font-semibold uppercase tracking-widest text-white hover:bg-indigo-500 transition-all shadow-sm">
                        Editar
                    </a>
                    <a href="{{ route('travel_events.index') }}" class="p-2 text-gray-400 hover:text-gray-600 bg-white border border-gray-200 rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7 7-7m8 14l-7-7 7-7"></path></svg>
                    </a>
                </div>
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <!-- Main Info Card -->
                <div class="lg:col-span-2 space-y-8">
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-8 md:p-12 overflow-hidden relative">
                        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-32 h-32 bg-indigo-500/5 rounded-full blur-3xl"></div>
                        
                        <div class="relative grid grid-cols-1 md:grid-cols-2 gap-12">
                            <!-- Left Column -->
                            <div class="space-y-8">
                                <div>
                                    <h4 class="text-sm font-semibold text-gray-600 dark:text-gray-400 mb-2 flex items-center gap-2">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                                        Centro de Costos
                                    </h4>
                                    <p class="text-lg font-bold text-gray-900 dark:text-white uppercase">{{ $travelEvent->costCenter->name ?? 'N/A' }}</p>
                                </div>

                                <div>
                                    <h4 class="text-sm font-semibold text-gray-600 dark:text-gray-400 mb-2 flex items-center gap-2">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                        Destino / Ubicación
                                    </h4>
                                    <p class="text-lg font-bold text-gray-900 dark:text-white uppercase">{{ $travelEvent->location ?? 'NO ESPECIFICADO' }}</p>
                                    <span class="inline-block mt-2 px-3 py-1 rounded-lg text-[9px] font-black uppercase tracking-tight bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                        Vuelo {{ ucfirst($travelEvent->trip_type) }}
                                    </span>
                                </div>

                                <div>
                                    <h4 class="text-sm font-semibold text-gray-600 dark:text-gray-400 mb-2 flex items-center gap-2">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                        Periodo de Vigencia
                                    </h4>
                                    <div class="flex items-center gap-4">
                                        <div class="bg-gray-50 dark:bg-gray-900/50 p-3 rounded-2xl border border-gray-100 dark:border-gray-700">
                                            <span class="block text-[8px] text-gray-400 font-black uppercase">Desde</span>
                                            <span class="text-sm font-bold">{{ $travelEvent->start_date ? \Carbon\Carbon::parse($travelEvent->start_date)->format('d/m/Y') : 'N/A' }}</span>
                                        </div>
                                        <div class="bg-gray-50 dark:bg-gray-900/50 p-3 rounded-2xl border border-gray-100 dark:border-gray-700">
                                            <span class="block text-[8px] text-gray-400 font-black uppercase">Hasta</span>
                                            <span class="text-sm font-bold">{{ $travelEvent->end_date ? \Carbon\Carbon::parse($travelEvent->end_date)->format('d/m/Y') : 'N/A' }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Right Column -->
                            <div class="space-y-8">
                                <div>
                                    <h4 class="text-sm font-semibold text-gray-600 dark:text-gray-400 mb-2 flex items-center gap-2">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 013 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                                        Aprobador Principal
                                    </h4>
                                    <div class="flex items-center gap-4">
                                        <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-900/30 rounded-2xl flex items-center justify-center">
                                            <span class="text-indigo-600 dark:text-indigo-400 font-black text-lg">{{ substr($travelEvent->director->name ?? '?', 0, 1) }}</span>
                                        </div>
                                        <div>
                                            <p class="text-lg font-bold text-gray-900 dark:text-white uppercase leading-tight">{{ $travelEvent->director->name ?? 'N/A' }}</p>
                                            <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">Director Responsable</p>
                                        </div>
                                    </div>
                                </div>

                                @if($travelEvent->approval_evidence_path)
                                <div>
                                    <h4 class="text-sm font-semibold text-gray-600 dark:text-gray-400 mb-2">Evidencia de Aprobación</h4>
                                    <a href="{{ Storage::url($travelEvent->approval_evidence_path) }}" target="_blank" class="flex items-center justify-between p-5 bg-indigo-50/50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-800/50 rounded-3xl group hover:bg-indigo-600 transition-all">
                                        <div class="flex items-center gap-4">
                                            <div class="p-3 bg-white dark:bg-gray-800 rounded-2xl shadow-sm text-indigo-600 group-hover:text-indigo-600 transition-all">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                                            </div>
                                            <div>
                                                <p class="text-xs font-black text-indigo-900 dark:text-indigo-200 uppercase tracking-tight group-hover:text-white">Ver Documento</p>
                                                <p class="text-[9px] text-indigo-400 group-hover:text-indigo-200 uppercase font-bold">Respaldo Oficial</p>
                                            </div>
                                        </div>
                                        <svg class="w-5 h-5 text-indigo-300 group-hover:text-white transform group-hover:translate-x-1 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                                    </a>
                                </div>
                                @endif

                                <div>
                                    <h4 class="text-sm font-semibold text-gray-600 dark:text-gray-400 mb-2">Descripción</h4>
                                    <p class="text-gray-600 dark:text-gray-400 text-sm leading-relaxed">{{ $travelEvent->description ?? 'No hay descripción disponible para este evento.' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Facturas en Cola -->
                    @php
                        $queuedFacturas = $travelEvent->reimbursements()->where('status', 'en_evento')->get();
                        $canClose = (Auth::user()->isAdmin() || Auth::id() === $travelEvent->director_id) && $travelEvent->status === 'active';
                    @endphp

                    @if($queuedFacturas->count() > 0 || $travelEvent->status === 'active')
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 overflow-hidden dark:border-gray-700 mt-8">
                        <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-gray-50 dark:bg-gray-900/50">
                            <div>
                                <h3 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                                    <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                    Facturas en Espera de Cierre
                                </h3>
                                <p class="text-xs text-gray-500 mt-1">Estas facturas se enviarán a cobro (ciclo de aprobación) al cerrar el evento.</p>
                            </div>
                            @if($canClose)
                            <form action="{{ route('travel_events.close', $travelEvent) }}" method="POST" onsubmit="return confirm('¿Estás seguro de cerrar este evento? Esto enviará TODAS las facturas listadas al ciclo de cobro y no se podrán agregar más facturas a este evento.');">
                                @csrf
                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-md shadow-sm transition-colors whitespace-nowrap">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    Cerrar Evento y Enviar Facturas
                                </button>
                            </form>
                            @endif
                        </div>
                        
                        @if($queuedFacturas->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                                <thead class="bg-white dark:bg-gray-800">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Creador</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Concepto</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Monto</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Acción</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
                                    @foreach($queuedFacturas as $factura)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/50">
                                        <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-gray-200 font-medium">{{ $factura->user->name ?? 'Usuario' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-gray-500 dark:text-gray-400 capitalize">{{ $factura->category }} <span class="text-xs ml-2 text-indigo-500">{{ $factura->folio }}</span></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-white font-bold">${{ number_format($factura->total, 2) }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right">
                                            <a href="{{ route('reimbursements.show', $factura) }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 text-xs font-semibold">Ver Detalles</a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @else
                        <div class="p-8 text-center text-gray-500 dark:text-gray-400 text-sm">
                            No hay facturas cargadas en la cola de este evento.
                        </div>
                        @endif
                    </div>
                    @endif
                </div>

                <!-- Sidebar Info -->
                <div class="space-y-8">
                    <div class="bg-gray-900 dark:bg-gray-800 rounded-[2.5rem] p-8 shadow-2xl relative overflow-hidden">
                        <div class="absolute top-0 right-0 w-32 h-32 bg-indigo-500/10 rounded-full blur-3xl -mr-16 -mt-16"></div>
                        
                        <h4 class="text-[10px] font-black text-indigo-400 uppercase tracking-widest mb-8">Personal Autorizado</h4>
                        
                        <div class="space-y-4">
                            @forelse($travelEvent->participants as $participant)
                                <div class="flex items-center gap-4 group">
                                    <div class="w-10 h-10 bg-gray-800 dark:bg-gray-700 border border-gray-700 dark:border-gray-600 rounded-xl flex items-center justify-center text-xs font-black text-white group-hover:bg-indigo-600 transition-all">
                                        {{ substr($participant->name, 0, 1) }}
                                    </div>
                                    <div>
                                        <p class="text-xs font-bold text-white uppercase">{{ $participant->name }}</p>
                                        <p class="text-[9px] text-gray-500 uppercase font-black tracking-tight">{{ $participant->role ?? 'Colaborador' }}</p>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-6 opacity-30">
                                    <p class="text-[10px] text-gray-500 font-black uppercase tracking-widest leading-loose">No hay personal autorizado<br>seleccionado aún</p>
                                </div>
                            @endforelse
                        </div>

                        <div class="mt-12 pt-8 border-t border-gray-800">
                            <p class="text-[9px] text-gray-500 font-bold leading-relaxed">Solo los usuarios listados arriba podrán cargar reembolsos asociados a este código de evento.</p>
                        </div>
                    </div>

                    <!-- Creator info -->
                    <div class="px-8 flex items-center justify-between text-gray-400">
                        <div>
                            <span class="block text-[8px] font-black uppercase tracking-widest mb-1">Registrado el</span>
                            <span class="text-[10px] font-bold">{{ $travelEvent->created_at->format('d M, Y') }}</span>
                        </div>
                        <div class="text-right">
                            <span class="block text-[8px] font-black uppercase tracking-widest mb-1">Creado por</span>
                            <span class="text-[10px] font-bold">{{ $travelEvent->user->name ?? 'SISTEMA' }}</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
