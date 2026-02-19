<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <!-- Welcome Section -->
            <div class="mb-6">
                <h3 class="text-2xl font-bold text-gray-800 dark:text-gray-200">Hola, {{ Auth::user()->name }} 👋</h3>
                <p class="text-gray-600 dark:text-gray-400">Bienvenido al sistema de Reimbursements.</p>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                
                @if(Auth::user()->isDirector())
                    <!-- Director Specific Stats -->
                    
                    <!-- Pending Approvals -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg border-l-4 border-yellow-500">
                        <div class="p-6">
                            <div class="text-gray-500 dark:text-gray-400 text-sm font-medium uppercase tracking-wider">Por Aprobar</div>
                            <div class="mt-2 flex items-baseline">
                                <span class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ $stats['pending_approvals_count'] ?? 0 }}</span>
                                <span class="ml-2 text-sm text-gray-500">Solicitudes</span>
                            </div>
                            <div class="mt-1 text-sm text-yellow-600 font-medium">
                                ${{ number_format($stats['pending_approvals_amount'] ?? 0, 2) }} Pendiente
                            </div>
                        </div>
                    </div>

                    <!-- My Pending Requests -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg border-l-4 border-blue-500">
                        <div class="p-6">
                            <div class="text-gray-500 dark:text-gray-400 text-sm font-medium uppercase tracking-wider">Mis Solicitudes Pendientes</div>
                            <div class="mt-2 flex items-baseline">
                                <span class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ $stats['my_pending_count'] ?? 0 }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- My Approved -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg border-l-4 border-green-500">
                        <div class="p-6">
                            <div class="text-gray-500 dark:text-gray-400 text-sm font-medium uppercase tracking-wider">Mis Aprobados</div>
                            <div class="mt-2 flex items-baseline">
                                <span class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ $stats['my_approved_count'] ?? 0 }}</span>
                            </div>
                             <div class="mt-1 text-sm text-green-600 font-medium">
                                ${{ number_format($stats['my_total_reimbursed'] ?? 0, 2) }} Total
                            </div>
                        </div>
                    </div>

                @else
                    <!-- Admin / Accountant / User Stats -->
                    
                    <!-- Pending -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg border-l-4 border-yellow-500">
                        <div class="p-6">
                            <div class="text-gray-500 dark:text-gray-400 text-sm font-medium uppercase tracking-wider">Pendientes</div>
                            <div class="mt-2 flex items-baseline">
                                <span class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ $stats['pending_count'] ?? 0 }}</span>
                            </div>
                            <div class="mt-1 text-sm text-yellow-600 font-medium">
                                ${{ number_format($stats['total_amount_pending'] ?? $stats['total_pending_amount'] ?? 0, 2) }}
                            </div>
                        </div>
                    </div>

                    <!-- Approved -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg border-l-4 border-green-500">
                        <div class="p-6">
                            <div class="text-gray-500 dark:text-gray-400 text-sm font-medium uppercase tracking-wider">Aprobados</div>
                            <div class="mt-2 flex items-baseline">
                                <span class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ $stats['approved_count'] ?? 0 }}</span>
                            </div>
                            <div class="mt-1 text-sm text-green-600 font-medium">
                                ${{ number_format($stats['total_amount_approved'] ?? $stats['total_approved_amount'] ?? 0, 2) }}
                            </div>
                        </div>
                    </div>

                    <!-- Rejected -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg border-l-4 border-red-500">
                        <div class="p-6">
                            <div class="text-gray-500 dark:text-gray-400 text-sm font-medium uppercase tracking-wider">Rechazados</div>
                            <div class="mt-2 flex items-baseline">
                                <span class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ $stats['rejected_count'] ?? 0 }}</span>
                            </div>
                        </div>
                    </div>

                @endif

                <!-- Create New Action Card -->
                 <div class="bg-indigo-50 dark:bg-indigo-900 overflow-hidden shadow-sm sm:rounded-lg border border-indigo-200 dark:border-indigo-700 flex flex-col items-center justify-center p-6 hover:bg-indigo-100 dark:hover:bg-indigo-800 transition cursor-pointer" onclick="window.location='{{ route('reimbursements.create') }}'">
                    <div class="text-indigo-600 dark:text-indigo-300 mb-2">
                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    </div>
                    <span class="font-bold text-indigo-700 dark:text-indigo-200">Nueva Solicitud</span>
                </div>

            </div>

            <!-- Recent Activity Table -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-gray-100 mb-4">Actividad Reciente</h3>
                    
                    @if($recentReimbursements->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Folio / Tipo</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Solicitante</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Fecha</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Total</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Estatus</th>
                                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($recentReimbursements as $reimbursement)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                    {{ $reimbursement->folio ?? Str::limit($reimbursement->uuid, 8) ?? 'S/F' }}
                                                </div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                                    {{ ucfirst($reimbursement->type) }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900 dark:text-gray-100">{{ $reimbursement->user->name ?? 'Usuario' }}</div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $reimbursement->costCenter->code ?? '' }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                {{ $reimbursement->created_at->format('d/m/Y') }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                                ${{ number_format($reimbursement->total, 2) }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                    {{ $reimbursement->status === 'aprobado' ? 'bg-green-100 text-green-800' : '' }}
                                                    {{ $reimbursement->status === 'rechazado' ? 'bg-red-100 text-red-800' : '' }}
                                                    {{ $reimbursement->status === 'pendiente' ? 'bg-yellow-100 text-yellow-800' : '' }}">
                                                    {{ ucfirst($reimbursement->status) }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <a href="{{ route('reimbursements.show', $reimbursement) }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">Ver</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-gray-500 dark:text-gray-400 text-sm">No hay actividad reciente para mostrar.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
