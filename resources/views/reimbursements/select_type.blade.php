<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Nuevo - Selecciona el Tipo') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="text-lg font-medium text-center mb-8">¿Qué tipo de solicitud deseas registrar?</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <!-- Reembolso -->
                        <a href="{{ route('reimbursements.create', ['type' => 'reembolso']) }}" class="group block">
                            <div class="p-6 bg-white dark:bg-gray-700 border-2 border-gray-200 dark:border-gray-600 rounded-xl hover:border-indigo-500 hover:shadow-lg transition-all text-center h-full flex flex-col items-center justify-center group-hover:bg-indigo-50 dark:group-hover:bg-gray-600">
                                <div class="bg-indigo-100 dark:bg-indigo-900 p-4 rounded-full mb-4 group-hover:scale-110 transition-transform">
                                    <svg class="w-10 h-10 text-indigo-600 dark:text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                </div>
                                <h4 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Reembolso</h4>
                                <p class="text-sm text-gray-500 dark:text-gray-300">Gastos generales realizados con recursos propios.</p>
                            </div>
                        </a>

                        <!-- Fondo Fijo -->
                        <a href="{{ route('reimbursements.create', ['type' => 'fondo_fijo']) }}" class="group block">
                            <div class="p-6 bg-white dark:bg-gray-700 border-2 border-gray-200 dark:border-gray-600 rounded-xl hover:border-indigo-500 hover:shadow-lg transition-all text-center h-full flex flex-col items-center justify-center group-hover:bg-indigo-50 dark:group-hover:bg-gray-600">
                                <div class="bg-green-100 dark:bg-green-900 p-4 rounded-full mb-4 group-hover:scale-110 transition-transform">
                                    <svg class="w-10 h-10 text-green-600 dark:text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                                </div>
                                <h4 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Fondo Fijo</h4>
                                <p class="text-sm text-gray-500 dark:text-gray-300">Comprobación de gastos de caja chica o fondo.</p>
                            </div>
                        </a>

                        <!-- Comida -->
                        <a href="{{ route('reimbursements.create', ['type' => 'comida']) }}" class="group block">
                            <div class="p-6 bg-white dark:bg-gray-700 border-2 border-gray-200 dark:border-gray-600 rounded-xl hover:border-indigo-500 hover:shadow-lg transition-all text-center h-full flex flex-col items-center justify-center group-hover:bg-indigo-50 dark:group-hover:bg-gray-600">
                                <div class="bg-orange-100 dark:bg-orange-900 p-4 rounded-full mb-4 group-hover:scale-110 transition-transform">
                                    <svg class="w-10 h-10 text-orange-600 dark:text-orange-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                                </div>
                                <h4 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Comida</h4>
                                <p class="text-sm text-gray-500 dark:text-gray-300">Gastos de representación o alimentación.</p>
                            </div>
                        </a>

                        <!-- Viaje (Disabled) -->
                        <div class="group block cursor-not-allowed opacity-60 relative" title="Próximamente">
                            <div class="relative p-6 bg-gray-50 dark:bg-gray-800 border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-xl text-center h-full flex flex-col items-center justify-center">
                                <span class="absolute top-[-10px] right-2 bg-gray-200 dark:bg-gray-600 text-gray-600 dark:text-gray-300 text-[10px] font-bold px-2 py-1 rounded-full uppercase tracking-wider shadow-sm">
                                    Próximamente
                                </span>
                                <div class="bg-gray-200 dark:bg-gray-700 p-4 rounded-full mb-4">
                                    <svg class="w-10 h-10 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                </div>
                                <h4 class="text-xl font-bold text-gray-400 dark:text-gray-500 mb-2">Viaje</h4>
                                <p class="text-sm text-gray-400 dark:text-gray-500">Viáticos, hospedaje y transporte.</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-8 text-center">
                        <a href="{{ route('reimbursements.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 underline">Volver al listado</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
