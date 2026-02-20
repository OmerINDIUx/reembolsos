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

                    @if($notifications->count() > 0)
                        <div class="space-y-4">
                            @foreach($notifications as $notification)
                                <div class="p-4 rounded-lg flex flex-col md:flex-row justify-between items-start md:items-center border {{ $notification->unread() ? 'bg-indigo-50 border-indigo-200 dark:bg-indigo-900/30 dark:border-indigo-800' : 'bg-white border-gray-200 dark:bg-gray-800 dark:border-gray-700' }}">
                                    
                                    <div class="flex items-start mb-4 md:mb-0">
                                        <div class="flex-shrink-0 mr-4 mt-1">
                                            @if($notification->data['type'] === 'success')
                                                <svg class="h-6 w-6 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                            @elseif($notification->data['type'] === 'danger')
                                                <svg class="h-6 w-6 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                            @elseif($notification->data['type'] === 'warning')
                                                <svg class="h-6 w-6 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                                            @else
                                                <svg class="h-6 w-6 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                            @endif
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium {{ $notification->unread() ? 'text-gray-900 dark:text-gray-100' : 'text-gray-600 dark:text-gray-400' }}">
                                                {{ $notification->data['message'] ?? 'Nueva notificación' }}
                                            </p>
                                            <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                                                Folio: {{ $notification->data['reimbursement_folio'] ?? 'N/A' }} | {{ $notification->created_at->diffForHumans() }} ({{ $notification->created_at->format('d/m/Y H:i') }})
                                            </p>
                                        </div>
                                    </div>

                                    <div class="flex items-center space-x-3 w-full md:w-auto mt-2 md:mt-0">
                                        @if(isset($notification->data['url']))
                                            <a href="{{ route('notifications.mark_read', $notification->id) }}" class="inline-flex items-center px-3 py-1 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-xs font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition">
                                                Ver Reembolso
                                            </a>
                                        @endif
                                        @if($notification->unread() && !isset($notification->data['url']))
                                            <a href="{{ route('notifications.mark_read', $notification->id) }}" class="text-xs text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300 transition">
                                                Marcar leída
                                            </a>
                                        @endif
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
