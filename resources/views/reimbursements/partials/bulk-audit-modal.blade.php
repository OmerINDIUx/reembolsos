<div x-show="openModal" class="fixed z-50 inset-0 overflow-y-auto" style="display: none;" id="bulk-modal-target">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity" aria-hidden="true" @click="openModal = false">
            <div class="absolute inset-0 bg-gray-900 opacity-75"></div>
        </div>
        
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        
        <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-gray-100 dark:border-gray-700">
            <form action="{{ route('reimbursements.bulk_audit_action') }}" method="POST">
                @csrf
                
                <template x-for="id in selectedIds" :key="id">
                    <input type="hidden" name="ids[]" :value="id">
                </template>
                
                <div class="px-6 pt-6 pb-6">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 dark:bg-indigo-900/50 sm:mx-0 sm:h-12 sm:w-12 border border-indigo-200 dark:border-indigo-800">
                            <svg class="h-6 w-6 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-xl leading-6 font-black text-gray-900 dark:text-white mb-2" id="modal-title">
                                Acción Masiva (Detalle)
                            </h3>
                            <div class="mt-2 text-sm text-gray-500 dark:text-gray-400 space-y-4">
                                <p class="font-medium text-lg leading-tight">
                                    Estás a punto de procesar <span class="font-black text-indigo-600 dark:text-indigo-400" x-text="selectedIds.length"></span> trámites por un total de <span class="font-black text-indigo-600 dark:text-indigo-400" x-text="'$' + formatMoney(totalAmount)"></span>.
                                </p>
                                
                                <!-- Alerts Block -->
                                <div x-show="totalAlerts > 0" class="bg-amber-50 dark:bg-amber-900/20 border-l-4 border-amber-500 p-4 rounded-r-xl">
                                    <div class="flex">
                                        <div class="flex-shrink-0">
                                            <svg class="h-5 w-5 text-amber-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                        <div class="ml-3">
                                            <h3 class="text-[10px] font-black uppercase tracking-widest text-amber-800 dark:text-amber-500">Atención: Contiene <span x-text="totalAlerts"></span> alertas</h3>
                                            <ul class="mt-1 text-xs font-medium text-amber-700 dark:text-amber-400 list-disc ml-4 space-y-1">
                                                <li x-show="missingUuidCount > 0"><span x-text="missingUuidCount"></span> ticket(s) sin factura.</li>
                                                <li x-show="mismatchCount > 0"><span x-text="mismatchCount"></span> trámite(s) con UUID o Monto discordante.</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Action Selection -->
                                <div class="mt-6 pt-4 border-t border-gray-100 dark:border-gray-700">
                                    <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-3">Qué acción deseas realizar?</label>
                                    <div class="grid grid-cols-2 gap-3">
                                        <label class="cursor-pointer">
                                            <input type="radio" name="action" value="aprobado" x-model="selectedAction" class="sr-only" required>
                                            <div :class="selectedAction === 'aprobado' ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/40 ring-1 ring-emerald-500' : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-transparent'" class="rounded-xl p-4 hover:bg-gray-50 dark:hover:bg-gray-700 transition-all text-center border">
                                                <span class="block text-sm font-black" :class="selectedAction === 'aprobado' ? 'text-emerald-700 dark:text-emerald-400' : 'text-gray-700 dark:text-gray-300'">Aprobar Masivamente</span>
                                            </div>
                                        </label>
                                        <label class="cursor-pointer">
                                            <input type="radio" name="action" value="rechazado" x-model="selectedAction" class="sr-only" required>
                                            <div :class="selectedAction === 'rechazado' ? 'border-red-500 bg-red-50 dark:bg-red-900/40 ring-1 ring-red-500' : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-transparent'" class="rounded-xl p-4 hover:bg-gray-50 dark:hover:bg-gray-700 transition-all text-center border">
                                                <span class="block text-sm font-black" :class="selectedAction === 'rechazado' ? 'text-red-700 dark:text-red-400' : 'text-gray-700 dark:text-gray-300'">Rechazar Masivamente</span>
                                            </div>
                                        </label>
                                    </div>
                                </div>

                                <!-- Rejection Reason (Conditional) -->
                                <div x-show="selectedAction === 'rechazado'" x-transition class="mt-4">
                                    <label class="block text-[10px] font-black text-red-500 uppercase tracking-widest mb-2">Motivo de Rechazo (Obligatorio)</label>
                                    <textarea name="rejection_reason" x-bind:required="selectedAction === 'rechazado'" rows="2" class="shadow-sm focus:ring-red-500 focus:border-red-500 block w-full sm:text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl" placeholder="Describe brevemente el motivo para los seleccionados..."></textarea>
                                </div>
                                
                                <!-- Confirmation & Security -->
                                <div class="mt-6 bg-gray-50 dark:bg-gray-900/50 p-5 rounded-2xl border border-gray-100 dark:border-gray-700 space-y-4">
                                    <label class="flex items-start cursor-pointer">
                                        <div class="flex items-center h-5">
                                            <input type="checkbox" required x-model="confirmed" class="w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                                        </div>
                                        <div class="ml-3 text-sm">
                                            <span class="font-bold text-gray-700 dark:text-gray-300">Confirmo que revisé y validé los reembolsos seleccionados</span>
                                        </div>
                                    </label>
                                    
                                    <div x-show="confirmed" x-transition.opacity>
                                        <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2">Para proceseder ingrese tu contraseña</label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                            </div>
                                            <input type="password" name="password" required class="focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-10 sm:text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl h-11" placeholder="Ingresa tu contraseña para autorizar">
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-900 px-4 py-4 sm:px-6 sm:flex sm:flex-row-reverse rounded-b-3xl">
                    <button type="submit" :disabled="!confirmed || !selectedAction" class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-sm px-6 py-2.5 bg-indigo-600 text-base font-black text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed transition-all">
                        Procesar Masivamente
                    </button>
                    <button type="button" @click="openModal = false" class="mt-3 w-full inline-flex justify-center rounded-xl border border-gray-300 dark:border-gray-600 shadow-sm px-6 py-2.5 bg-white dark:bg-gray-800 text-base font-black text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm transition-all">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
