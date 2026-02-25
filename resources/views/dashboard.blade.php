<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Panel') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <!-- Welcome Section -->
            <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h3 class="text-3xl font-extrabold text-gray-900 dark:text-white">¡Hola, {{ Auth::user()->name }}! 👋</h3>
                    <p class="text-gray-500 dark:text-gray-400 mt-1">Hoy es {{ now()->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY') }}</p>
                </div>
                
                @if(!Auth::user()->isAdminView())
                <div class="flex gap-3">
                    <a href="{{ route('reimbursements.create') }}" class="inline-flex items-center px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl transition-all shadow-lg hover:shadow-indigo-500/30">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        Nuevo Reembolso
                    </a>
                </div>
                @endif
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
                
                @if(Auth::user()->isDirector() || Auth::user()->isControlObra() || Auth::user()->isExecutiveDirector())
                    <!-- Managers Specific Stats (Director, Control, Ejecutivo) -->
                    <div class="bg-gradient-to-br from-amber-50 to-white dark:from-amber-900/10 dark:to-gray-800 p-6 rounded-2xl shadow-sm border border-amber-100 dark:border-amber-900/30 relative overflow-hidden group">
                        <div class="relative z-10">
                            <p class="text-amber-600 dark:text-amber-400 text-xs font-black uppercase tracking-widest mb-1">Por Aprobar ({{ $stats['approval_level_label'] ?? 'N/A' }})</p>
                            <h4 class="text-4xl font-black text-gray-900 dark:text-white">{{ $stats['pending_approvals_count'] ?? 0 }}</h4>
                            <p class="text-amber-700 dark:text-amber-300 font-bold mt-1 text-sm">${{ number_format($stats['pending_approvals_amount'] ?? 0, 2) }}</p>
                        </div>
                        <svg class="absolute -right-4 -bottom-4 w-24 h-24 text-amber-500/10 group-hover:scale-110 transition-transform duration-500" fill="currentColor" viewBox="0 0 20 20"><path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"></path><path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"></path></svg>
                    </div>

                    <div class="bg-gradient-to-br from-blue-50 to-white dark:from-blue-900/10 dark:to-gray-800 p-6 rounded-2xl shadow-sm border border-blue-100 dark:border-blue-900/30 relative overflow-hidden group">
                        <div class="relative z-10">
                            <p class="text-blue-600 dark:text-blue-400 text-xs font-black uppercase tracking-widest mb-1">Mis Pendientes</p>
                            <h4 class="text-4xl font-black text-gray-900 dark:text-white">{{ $stats['my_pending_count'] ?? 0 }}</h4>
                        </div>
                        <svg class="absolute -right-4 -bottom-4 w-24 h-24 text-blue-500/10 group-hover:scale-110 transition-transform duration-500" fill="currentColor" viewBox="0 0 24 24"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>

                    <div class="bg-gradient-to-br from-emerald-50 to-white dark:from-emerald-900/10 dark:to-gray-800 p-6 rounded-2xl shadow-sm border border-emerald-100 dark:border-emerald-900/30 relative overflow-hidden group">
                        <div class="relative z-10">
                            <p class="text-emerald-600 dark:text-emerald-400 text-xs font-black uppercase tracking-widest mb-1">Mis Pagados</p>
                            <h4 class="text-4xl font-black text-gray-900 dark:text-white">{{ $stats['my_approved_count'] ?? 0 }}</h4>
                            <p class="text-emerald-700 dark:text-emerald-300 font-bold mt-1 text-sm">${{ number_format($stats['my_total_reimbursed'] ?? 0, 2) }}</p>
                        </div>
                        <svg class="absolute -right-4 -bottom-4 w-24 h-24 text-emerald-500/10 group-hover:scale-110 transition-transform duration-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                    </div>

                @else
                    <!-- Admin / Subdirección / Standard User Stats -->
                    <div class="bg-gradient-to-br from-indigo-50 to-white dark:from-indigo-900/10 dark:to-gray-800 p-6 rounded-2xl shadow-sm border border-indigo-100 dark:border-indigo-900/30 relative overflow-hidden group">
                        <div class="relative z-10">
                            <p class="text-indigo-600 dark:text-indigo-400 text-xs font-black uppercase tracking-widest mb-1">En Proceso</p>
                            <h4 class="text-4xl font-black text-gray-900 dark:text-white">{{ $stats['pending_count'] ?? 0 }}</h4>
                            <p class="text-indigo-700 dark:text-indigo-300 font-bold mt-1 text-sm truncate">
                                ${{ number_format($stats['total_amount_pending'] ?? $stats['total_pending_amount'] ?? 0, 2) }}
                            </p>
                        </div>
                        <svg class="absolute -right-4 -bottom-4 w-24 h-24 text-indigo-500/10 group-hover:scale-110 transition-transform duration-500" fill="currentColor" viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    </div>

                    <div class="bg-gradient-to-br from-emerald-50 to-white dark:from-emerald-900/10 dark:to-gray-800 p-6 rounded-2xl shadow-sm border border-emerald-100 dark:border-emerald-900/30 relative overflow-hidden group">
                        <div class="relative z-10">
                            <p class="text-emerald-600 dark:text-emerald-400 text-xs font-black uppercase tracking-widest mb-1">Total Pagados</p>
                            <h4 class="text-4xl font-black text-gray-900 dark:text-white">{{ $stats['approved_count'] ?? 0 }}</h4>
                            <p class="text-emerald-700 dark:text-emerald-300 font-bold mt-1 text-sm truncate">
                                ${{ number_format($stats['total_amount_approved'] ?? $stats['total_approved_amount'] ?? 0, 2) }}
                            </p>
                        </div>
                        <svg class="absolute -right-4 -bottom-4 w-24 h-24 text-emerald-500/10 group-hover:scale-110 transition-transform duration-500" fill="currentColor" viewBox="0 0 24 24"><path d="M12 8c-1.657 0-3 1.343-3 3s1.343 3 3 3 3-1.343 3-3-1.343-3-3-3z"></path><path fill-rule="evenodd" d="M12 2C6.477 2 2 6.477 2 12s4.477 10 10 10 10-4.477 10-10S17.523 2 12 2zM7.001 5a1 1 0 011-1h8a1 1 0 110 2h-8a1 1 0 01-1-1zm0 14a1 1 0 110-2h8a1 1 0 110 2h-8z" clip-rule="evenodd"></path></svg>
                    </div>

                    @if(isset($stats['correction_count']) && $stats['correction_count'] > 0)
                    <div class="bg-gradient-to-br from-orange-50 to-white dark:from-orange-900/10 dark:to-gray-800 p-6 rounded-2xl shadow-sm border border-orange-200 dark:border-orange-900/30 relative overflow-hidden group">
                        <div class="relative z-10">
                            <p class="text-orange-600 dark:text-orange-400 text-xs font-black uppercase tracking-widest mb-1">Para Corregir</p>
                            <h4 class="text-4xl font-black text-gray-900 dark:text-white">{{ $stats['correction_count'] }}</h4>
                        </div>
                        <svg class="absolute -right-4 -bottom-4 w-24 h-24 text-orange-500/10 animate-pulse" fill="currentColor" viewBox="0 0 24 24"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                    </div>
                    @endif

                    <div class="bg-gradient-to-br from-rose-50 to-white dark:from-rose-900/10 dark:to-gray-800 p-6 rounded-2xl shadow-sm border border-rose-100 dark:border-rose-900/30 relative overflow-hidden group">
                        <div class="relative z-10">
                            <p class="text-rose-600 dark:text-rose-400 text-xs font-black uppercase tracking-widest mb-1">Rechazados</p>
                            <h4 class="text-4xl font-black text-gray-900 dark:text-white">{{ $stats['rejected_count'] ?? 0 }}</h4>
                        </div>
                        <svg class="absolute -right-4 -bottom-4 w-24 h-24 text-rose-500/10 group-hover:scale-110 transition-transform duration-500" fill="currentColor" viewBox="0 0 24 24"><path d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                @endif
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Recent Activity Table -->
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                        <div class="px-6 py-5 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Actividad Reciente</h3>
                            <a href="{{ route('reimbursements.index') }}" class="text-sm font-bold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 uppercase tracking-wider">Ver Todo &rarr;</a>
                        </div>
                        
                        @if($recentReimbursements->count() > 0)
                            <div class="overflow-x-auto text-sm">
                                <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700">
                                    <thead class="bg-gray-50/50 dark:bg-gray-900/50">
                                        <tr>
                                            <th class="px-6 py-4 text-left font-black text-gray-400 uppercase tracking-widest text-[10px]">Detalle / Tipo</th>
                                            <th class="px-6 py-4 text-left font-black text-gray-400 uppercase tracking-widest text-[10px]">Solicitante</th>
                                            <th class="px-6 py-4 text-left font-black text-gray-400 uppercase tracking-widest text-[10px]">Total</th>
                                            <th class="px-6 py-4 text-left font-black text-gray-400 uppercase tracking-widest text-[10px]">Estatus</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-50 dark:divide-gray-700">
                                        @foreach($recentReimbursements as $reimbursement)
                                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-900/50 transition-colors cursor-pointer" onclick="window.location='{{ route('reimbursements.show', $reimbursement) }}'">
                                                <td class="px-6 py-4">
                                                    <div class="font-bold text-gray-900 dark:text-white">{{ $reimbursement->folio ?? Str::limit($reimbursement->uuid, 8) ?? 'S/F' }}</div>
                                                    <div class="text-xs text-indigo-500 font-medium">{{ ucfirst($reimbursement->type) }}</div>
                                                </td>
                                                <td class="px-6 py-4">
                                                    <div class="text-gray-900 dark:text-white font-medium">{{ $reimbursement->user->name ?? 'Usuario' }}</div>
                                                    <div class="text-[10px] text-gray-400 uppercase font-black">{{ $reimbursement->costCenter->code ?? '' }}</div>
                                                </td>
                                                <td class="px-6 py-4 font-black text-gray-900 dark:text-white">
                                                    ${{ number_format($reimbursement->total, 2) }}
                                                </td>
                                                <td class="px-6 py-4">
                                                    @php
                                                        $statusColors = [
                                                            'aprobado' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400',
                                                            'rechazado' => 'bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-400',
                                                            'requiere_correccion' => 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
                                                            'default' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400'
                                                        ];
                                                        $color = $statusColors[$reimbursement->status] ?? $statusColors['default'];
                                                    @endphp
                                                    <span class="px-3 py-1 inline-flex text-[10px] leading-4 font-black rounded-full uppercase tracking-widest {{ $color }}">
                                                        @if($reimbursement->status === 'aprobado') Pagado 
                                                        @elseif($reimbursement->status === 'aprobado_cxp') Aprobado Subdirección
                                                        @elseif($reimbursement->status === 'aprobado_direccion') Aprobado Dirección
                                                        @else {{ str_replace('_', ' ', $reimbursement->status) }} @endif
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="p-12 text-center">
                                <svg class="w-16 h-16 text-gray-200 dark:text-gray-700 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                <p class="text-gray-500 dark:text-gray-400">No hay actividad reciente.</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Side Widget Area -->
                <div class="space-y-6">
                    <!-- Unread Notifications -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                        <div class="px-6 py-5 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Notificaciones</h3>
                            <a href="{{ route('notifications.index') }}" class="text-[10px] font-black text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 uppercase tracking-widest">Ver Todas</a>
                        </div>
                        <div class="p-2">
                            @forelse($notifications as $notification)
                                <a href="{{ route('notifications.mark_read', $notification->id) }}" class="flex items-start p-3 hover:bg-gray-50 dark:hover:bg-gray-900/50 rounded-xl transition-all group">
                                    <div class="flex-shrink-0 w-8 h-8 bg-indigo-50 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 rounded-lg flex items-center justify-center mr-3 mt-1 group-hover:bg-indigo-600 group-hover:text-white transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs font-bold text-gray-900 dark:text-white line-clamp-1 truncate">{{ $notification->data['title'] ?? 'Nueva Notificación' }}</p>
                                        <p class="text-[10px] text-gray-500 dark:text-gray-400 line-clamp-2 mt-0.5">{{ Str::limit($notification->data['message'] ?? '', 60) }}</p>
                                        <p class="text-[9px] text-gray-400 font-medium mt-1">{{ $notification->created_at->diffForHumans() }}</p>
                                    </div>
                                </a>
                            @empty
                                <div class="py-8 text-center">
                                    <p class="text-[11px] font-black text-gray-400 uppercase tracking-tighter">Sin pendientes nuevos</p>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <!-- App Shortcuts -->
                    <div class="bg-indigo-600 rounded-2xl p-6 text-white shadow-xl shadow-indigo-600/20 relative overflow-hidden">
                        <div class="relative z-10">
                            <h4 class="text-lg font-black mb-2 leading-tight">¿Necesitas ayuda con tus gastos?</h4>
                            <p class="text-indigo-100 text-xs mb-6 font-medium leading-relaxed">Recuerda subir tus archivos XML y PDF juntos para una validación inmediata.</p>
                            @if(!Auth::user()->isAdminView())
                            <a href="{{ route('reimbursements.create') }}" class="inline-flex items-center px-4 py-2 bg-white text-indigo-600 font-black rounded-xl text-[10px] uppercase tracking-widest hover:bg-indigo-50 transition-colors">
                                Iniciar Proceso &rarr;
                            </a>
                            @else
                            <a href="{{ route('reimbursements.index') }}" class="inline-flex items-center px-4 py-2 bg-white text-indigo-600 font-black rounded-xl text-[10px] uppercase tracking-widest hover:bg-indigo-50 transition-colors">
                                Ver Reembolsos &rarr;
                            </a>
                            @endif
                        </div>
                        <svg class="absolute -right-8 -bottom-8 w-40 h-40 text-indigo-500/20" fill="currentColor" viewBox="0 0 24 24"><path d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
