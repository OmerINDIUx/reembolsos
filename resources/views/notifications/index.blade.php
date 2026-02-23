<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Mis Notificaciones') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-medium">Todas las notificaciones</h3>
                        @if(Auth::user()->unreadNotifications->count() > 0)
                        <form action="{{ route('notifications.mark_all') }}" method="POST">
                            @csrf
                            <button type="submit" class="text-sm text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300 font-semibold transition">
                                Marcar todas como leídas
                            </button>
                        </form>
                        @endif
                    </div>

                        <div class="space-y-6">
                            @php $lastDate = null; @endphp
                            @foreach($notifications as $notification)
                                @php 
                                    $date = $notification->created_at->timezone(config('app.timezone'));
                                    $currDate = $date->format('Y-m-d'); 
                                @endphp
                                
                                @if($lastDate !== $currDate)
                                    <div class="flex items-center mt-8 mb-4">
                                        <div class="flex-grow border-t border-gray-200 dark:border-gray-700"></div>
                                        <span class="px-4 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">
                                            @if($date->isToday()) Hoy
                                            @elseif($date->isYesterday()) Ayer
                                            @else {{ $date->translatedFormat('d \d\e F, Y') }}
                                            @endif
                                        </span>
                                        <div class="flex-grow border-t border-gray-200 dark:border-gray-700"></div>
                                    </div>
                                    @php $lastDate = $currDate; @endphp
                                @endif

                                <div class="group relative p-5 rounded-2xl transition-all border {{ $notification->unread() ? 'bg-indigo-50/50 border-indigo-200 dark:bg-indigo-900/20 dark:border-indigo-800 shadow-sm' : 'bg-white border-gray-100 dark:bg-gray-800 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600' }}">
                                    @if($notification->unread())
                                        <div class="absolute top-5 left-2 w-1.5 h-1.5 bg-indigo-600 rounded-full"></div>
                                    @endif

                                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 pl-4">
                                        <div class="flex items-start flex-grow">
                                            <div class="flex-shrink-0 mr-4 bg-white dark:bg-gray-900 p-2.5 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
                                                @php $type = $notification->data['type'] ?? 'info'; @endphp
                                                @if($type === 'success')
                                                    <svg class="h-6 w-6 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                                @elseif($type === 'danger')
                                                    <svg class="h-6 w-6 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                                @elseif($type === 'warning')
                                                    <svg class="h-6 w-6 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                                                @else
                                                    <svg class="h-6 w-6 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                                @endif
                                            </div>
                                            <div>
                                                <p class="text-sm font-bold {{ $notification->unread() ? 'text-gray-900 dark:text-gray-100' : 'text-gray-600 dark:text-gray-400' }}">
                                                    {{ $notification->data['message'] ?? 'Nueva notificación' }}
                                                </p>
                                                <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-[10px] font-black uppercase tracking-widest text-gray-500">
                                                    <span class="flex items-center">
                                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" /></svg>
                                                        Folio: <span class="text-indigo-600 dark:text-indigo-400 ml-1">{{ $notification->data['reimbursement_folio'] ?? 'N/A' }}</span>
                                                    </span>
                                                    <span class="flex items-center">
                                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                                        {{ $notification->created_at->diffForHumans() }} ({{ $notification->created_at->format('H:i') }})
                                                    </span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="flex items-center space-x-3 w-full md:w-auto self-end md:self-center">
                                            @if(isset($notification->data['url']))
                                                <a href="{{ route('notifications.mark_read', $notification->id) }}" class="inline-flex items-center px-5 py-2.5 bg-indigo-600 text-white rounded-xl shadow-lg shadow-indigo-500/30 text-xs font-black uppercase tracking-widest hover:bg-indigo-700 transition transform hover:scale-105">
                                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                                    Ver Detalle
                                                </a>
                                            @elseif($notification->unread())
                                                <a href="{{ route('notifications.mark_read', $notification->id) }}" class="inline-flex items-center px-4 py-2 text-xs font-black uppercase tracking-widest text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded-xl transition">
                                                    Marcar como leída
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-6">
                            {{ $notifications->links() }}
                        </div>
                    @else
                        <div class="text-center py-10">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">No hay notificaciones</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Cuando tengas notificaciones nuevas aparecerán aquí.</p>
                        </div>
                    @endif

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
