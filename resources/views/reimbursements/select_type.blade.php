<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Nuevo - Selecciona el Tipo') }}
        </h2>
    </x-slot>

    <div class="py-12" x-data="{
        askForInvoice(type) {
            Swal.fire({
                title: '<span class=&quot;text-2xl font-black uppercase tracking-tight&quot;>¿Cuentas con factura?</span>',
                html: '<p class=&quot;text-sm font-medium text-gray-500&quot;>Selecciona el tipo de comprobación que vas a realizar.</p>',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'SÍ, TENGO FACTURA (XML)',
                cancelButtonText: 'NO TENGO FACTURA',
                confirmButtonColor: '#4f46e5',
                cancelButtonColor: '#f59e0b',
                reverseButtons: true,
                customClass: {
                    popup: 'rounded-[2rem] border-none shadow-2xl dark:bg-gray-800',
                    confirmButton: 'rounded-xl px-10 py-4 font-black text-xs uppercase tracking-widest mb-2',
                    cancelButton: 'rounded-xl px-10 py-4 font-black text-xs uppercase tracking-widest mb-2'
                }
            }).then((result) => {
                let hasInvoice = result.isConfirmed ? 1 : 0;
                window.location.href = `{{ route('reimbursements.create') }}?type=${type}&has_invoice=${hasInvoice}`;
            });
        }
    }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg border border-gray-100 dark:border-gray-700">
                <div class="p-8 md:p-12 text-gray-900 dark:text-gray-100">
                    <h3 class="text-3xl font-black text-center mb-12 uppercase tracking-tight">¿Qué tipo de solicitud deseas registrar?</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                        <!-- Reembolso -->
                        <button type="button" @click="askForInvoice('reembolso')" class="group block text-left">
                            <div class="p-8 bg-white dark:bg-gray-700 border-4 border-gray-100 dark:border-gray-600 rounded-[2rem] hover:border-indigo-500 hover:shadow-2xl transition-all text-center h-full flex flex-col items-center justify-center group-hover:bg-indigo-50 dark:group-hover:bg-gray-600">
                                <div class="bg-indigo-100 dark:bg-indigo-900 p-6 rounded-3xl mb-6 group-hover:scale-110 transition-transform shadow-inner">
                                    <svg class="w-12 h-12 text-indigo-600 dark:text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                </div>
                                <h4 class="text-2xl font-black text-gray-900 dark:text-white mb-3 uppercase tracking-tighter">Reembolso</h4>
                                <p class="text-xs font-bold text-gray-400 dark:text-gray-400 uppercase tracking-widest leading-loose">Gastos generales realizados con recursos propios.</p>
                            </div>
                        </button>

                        <!-- Fondo Fijo -->
                        <button type="button" @click="askForInvoice('fondo_fijo')" class="group block text-left">
                            <div class="p-8 bg-white dark:bg-gray-700 border-4 border-gray-100 dark:border-gray-600 rounded-[2rem] hover:border-green-500 hover:shadow-2xl transition-all text-center h-full flex flex-col items-center justify-center group-hover:bg-green-50 dark:group-hover:bg-gray-600">
                                <div class="bg-green-100 dark:bg-green-900 p-6 rounded-3xl mb-6 group-hover:scale-110 transition-transform shadow-inner">
                                    <svg class="w-12 h-12 text-green-600 dark:text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                                </div>
                                <h4 class="text-2xl font-black text-gray-900 dark:text-white mb-3 uppercase tracking-tighter">Fondo Fijo</h4>
                                <p class="text-xs font-bold text-gray-400 dark:text-gray-400 uppercase tracking-widest leading-loose">Comprobación de gastos de caja chica o fondo.</p>
                            </div>
                        </button>

                        <!-- Comida -->
                        <button type="button" @click="askForInvoice('comida')" class="group block text-left">
                            <div class="p-8 bg-white dark:bg-gray-700 border-4 border-gray-100 dark:border-gray-600 rounded-[2rem] hover:border-orange-500 hover:shadow-2xl transition-all text-center h-full flex flex-col items-center justify-center group-hover:bg-orange-50 dark:group-hover:bg-gray-600">
                                <div class="bg-orange-100 dark:bg-orange-900 p-6 rounded-3xl mb-6 group-hover:scale-110 transition-transform shadow-inner">
                                    <svg class="w-12 h-12 text-orange-600 dark:text-orange-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                                </div>
                                <h4 class="text-2xl font-black text-gray-900 dark:text-white mb-3 uppercase tracking-tighter">Comida</h4>
                                <p class="text-xs font-bold text-gray-400 dark:text-gray-400 uppercase tracking-widest leading-loose">Gastos de representación o alimentación.</p>
                            </div>
                        </button>
                    </div>

                    <!-- Viaje (Optional - Still show as upcoming or handle?) -->
                    <div class="mt-12 p-8 bg-gray-50 dark:bg-gray-900/50 rounded-[2.5rem] border-2 border-dashed border-gray-200 dark:border-gray-700 flex flex-col md:flex-row items-center justify-between gap-8">
                        <div class="flex items-center gap-6">
                            <div class="bg-gray-200 dark:bg-gray-800 p-5 rounded-2xl">
                                <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                            <div>
                                <h4 class="text-xl font-black text-gray-400 uppercase tracking-tight">Viajes y Comisiones</h4>
                                <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Próximamente disponible para registro detallado.</p>
                            </div>
                        </div>
                        <div class="bg-white dark:bg-gray-800 px-6 py-3 rounded-full border border-gray-100 dark:border-gray-700 text-[10px] font-black text-gray-400 uppercase tracking-widest shadow-sm">
                            En Desarrollo
                        </div>
                    </div>

                    <div class="mt-16 text-center">
                        <a href="{{ route('reimbursements.index') }}" class="inline-flex items-center text-[10px] font-black text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 uppercase tracking-[0.3em] transition-all group">
                            <svg class="w-4 h-4 mr-2 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                            Volver al listado
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
