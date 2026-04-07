<x-app-layout>
    <style>
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        .animate-shake { animation: shake 0.4s ease-in-out; }
        .animate-fadeIn { animation: fadeIn 0.4s ease-out forwards; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .animate-slideDown { animation: slideDown 0.4s ease forwards; }
        @keyframes slideDown { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
    </style>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Registrar ') . ucfirst(str_replace('_', ' ', $type)) }}
        </h2>
    </x-slot>

    <div class="py-12 relative" x-data="reimbursementForm()">
        <!-- Loading Overlay -->
        <div id="loading-overlay" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-60 backdrop-blur-sm">
            <div class="bg-white dark:bg-gray-800 p-8 rounded-2xl shadow-2xl flex flex-col items-center max-w-sm w-full mx-4 border border-gray-200 dark:border-gray-700">
                <div class="relative mb-6">
                    <div class="w-16 h-16 border-4 border-indigo-200 dark:border-indigo-900 rounded-full"></div>
                    <div class="w-16 h-16 border-4 border-indigo-600 rounded-full animate-spin absolute top-0 left-0 border-t-transparent"></div>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Procesando Solicitudes</h3>
                <p class="text-gray-500 dark:text-gray-400 text-center text-sm">Por favor, espera un momento mientras guardamos tus registros.</p>
            </div>
        </div>



        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-8 flex justify-between items-center">
                <a href="{{ route('reimbursements.create') }}" class="inline-flex items-center text-sm font-bold text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    CAMBIAR TIPO DE SOLICITUD
                </a>
            </div>

            <!-- Static alerts replaced with SweetAlert2 below -->

            <!-- Global Form -->
            <form id="reimbursement-form" action="{{ route('reimbursements.bulk_store') }}" method="POST" enctype="multipart/form-data" x-on:submit="handleSubmit" novalidate>
                @csrf
                <input type="hidden" name="type" value="{{ $type }}">
                <input type="hidden" name="week" value="{{ $currentWeek }}">
                <input type="hidden" name="has_invoice" value="{{ $hasInvoice ? 1 : 0 }}">
                <input type="hidden" name="draft_id" :value="draftId">

                <!-- Paso 1: Clasificación de Gasto -->
                <div class="bg-white dark:bg-gray-800 shadow-xl rounded-3xl mb-10 overflow-hidden border border-gray-100 dark:border-gray-700">
                    <div class="p-8 md:p-10">
                        <div class="flex items-center mb-8">
                            <div class="w-12 h-12 bg-indigo-600 rounded-2xl flex items-center justify-center text-white shadow-lg shadow-indigo-500/30 mr-5">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                            </div>
                            <div>
                                <h3 class="text-2xl font-black text-gray-900 dark:text-white uppercase tracking-tight">Paso 1</h3>
                                <p class="text-sm text-gray-500 font-medium">Define a qué entidad se cargará el gasto de esta sesión.</p>
                            </div>
                        </div>

                        <!-- Charge Type Toggle -->
                        <div x-show="type === 'viaje'" class="flex p-1 bg-gray-100 dark:bg-gray-900 rounded-2xl mb-8 max-w-md">
                            <button type="button" 
                                @click="chargeType = 'cost_center'" 
                                :class="chargeType === 'cost_center' ? 'bg-white dark:bg-gray-800 shadow-md text-indigo-600' : 'text-gray-500 hover:text-gray-700'"
                                class="flex-1 py-3 px-4 rounded-xl text-xs font-black uppercase tracking-widest transition-all">
                                Centro de Costos
                            </button>
                            <button type="button" 
                                @click="chargeType = 'travel_event'" 
                                :class="chargeType === 'travel_event' ? 'bg-white dark:bg-gray-800 shadow-md text-indigo-600' : 'text-gray-500 hover:text-gray-700'"
                                class="flex-1 py-3 px-4 rounded-xl text-xs font-black uppercase tracking-widest transition-all">
                                Viaje o Evento
                            </button>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <!-- Cost Center Select -->
                            <div x-show="type !== 'viaje' || chargeType === 'cost_center'" class="animate-slideDown">
                                <label class="block text-xs font-black text-gray-400 uppercase tracking-[0.2em] mb-3">Centro de Costos *</label>
                                <select name="cost_center_id" class="w-full border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-2xl shadow-sm focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all py-4 px-5" :required="type !== 'viaje' || chargeType === 'cost_center'">
                                    <option value="">Selecciona el Centro de Costos...</option>
                                    @foreach($costCenters as $center)
                                        <option value="{{ $center->id }}">{{ $center->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Travel/Event Select -->
                            <div x-show="type === 'viaje' && chargeType === 'travel_event'" class="animate-slideDown">
                                <label class="block text-xs font-black text-gray-400 uppercase tracking-[0.2em] mb-3">Viaje o Evento *</label>
                                <select name="travel_event_id" class="w-full border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-2xl shadow-sm focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all py-4 px-5" :required="type === 'viaje' && chargeType === 'travel_event'">
                                    <option value="">Selecciona el Viaje o Evento...</option>
                                    @foreach($travelEvents as $event)
                                        <option value="{{ $event->id }}">{{ $event->name }} ({{ $event->code }})</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-black text-gray-400 uppercase tracking-[0.2em] mb-3">Semana de Proceso</label>
                                <div class="w-full bg-gray-50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 rounded-2xl py-4 px-5 text-gray-500 font-bold">
                                    {{ $currentWeek }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- REPEATER (FOR REEMBOLSO, FONDO FIJO, COMIDA, VIAJE) -->
                <div class="space-y-16">
                    <template x-for="(item, index) in items" :key="item.id">
                        <div class="bg-white dark:bg-gray-800 shadow-2xl rounded-[2.5rem] border border-gray-100 dark:border-gray-700 overflow-hidden animate-fadeIn relative">
                            <input type="hidden" :name="'items['+index+'][draft_id]'" :value="item.draftId">
                            
                            <!-- Card Header -->
                            <div class="bg-gray-50 dark:bg-gray-900/40 px-8 md:px-10 py-6 flex justify-between items-center border-b border-gray-100 dark:border-gray-700">
                                <div class="flex items-center space-x-4">
                                    <div class="w-10 h-10 bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400 rounded-xl flex items-center justify-center font-black text-lg" x-text="index + 1"></div>
                                    <h3 class="text-xl font-bold text-gray-800 dark:text-white" x-text="item.fileName || 'Paso 2: Sube tu factura y completa la información requerida.'"></h3>
                                </div>
                                <button type="button" x-on:click="removeItem(index)" class="flex items-center space-x-2 text-red-500 hover:text-white hover:bg-red-500 px-4 py-2 rounded-xl transition-all font-bold text-sm">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    <span>ELIMINAR FACTURA</span>
                                </button>
                            </div>

                            <div class="p-8 md:p-10">
                                <div class="grid grid-cols-1 lg:grid-cols-12 gap-12">
                                    <!-- Left Column: XML/PDF Carga -->
                                    <div class="lg:col-span-4 space-y-8 border-r border-gray-50 dark:border-gray-700/50 pr-6">
                                        <div class="space-y-6" x-show="hasInvoice">
                                            <h4 class="text-[10px] font-black uppercase text-gray-400 tracking-[0.2em] border-b pb-3">Archivos Fuente</h4>
                                            
                                            <div>
                                                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-3 flex items-center justify-between">
                                                    <span>Archivo XML (CFDI) *</span>
                                                    <span class="text-[9px] font-black text-gray-400 uppercase italic">Máx 10MB</span>
                                                </label>
                                                <input type="file" :name="'items['+index+'][xml_file]'" accept=".xml" class="block w-full text-xs text-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl cursor-pointer bg-gray-50 dark:bg-gray-900 dark:text-gray-400 focus:outline-none p-3" :required="hasInvoice && !item.fileName" x-on:change="handleXmlChange($event, index)">
                                                <template x-if="item.fileName">
                                                    <div class="mt-1 flex items-center justify-between">
                                                        <div class="flex items-center text-[10px] text-green-600 font-bold uppercase italic">
                                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"></path></svg>
                                                            <span x-text="item.fileName"></span>
                                                        </div>
                                                        <template x-if="item.draftId">
                                                            <a :href="'/reimbursements/' + item.draftId + '/view-file/xml'" target="_blank" class="text-[10px] font-black text-indigo-600 hover:text-indigo-800 uppercase tracking-widest flex items-center bg-indigo-50 px-2 py-0.5 rounded-lg transition-colors border border-indigo-100">
                                                                VER
                                                                <svg class="w-2 h-2 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                                                            </a>
                                                        </template>
                                                    </div>
                                                </template>
                                            </div>

                                             <template x-if="hasInvoice">
                                                <div :class="item.xmlParsed ? 'opacity-100' : 'opacity-40 pointer-events-none transition-opacity'">
                                                    <div class="flex items-center justify-between mb-3">
                                                        <div class="flex flex-col">
                                                            <label class="block text-sm font-bold text-gray-700 dark:text-gray-300">Archivo PDF</label>
                                                            <span class="text-[9px] font-black text-gray-400 uppercase italic leading-none mt-1">Máx 10MB</span>
                                                        </div>
                                                        <label class="flex items-center cursor-pointer">
                                                            <input type="checkbox" :name="'items['+index+'][no_pdf]'" class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500" x-on:change="item.noPdf = $event.target.checked">
                                                            <span class="ml-2 text-[10px] font-bold text-gray-500 uppercase">Sin PDF</span>
                                                        </label>
                                                    </div>
                                                    <input type="file" :name="'items['+index+'][pdf_file]'" accept=".pdf" class="block w-full text-xs text-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl cursor-pointer bg-gray-50 dark:bg-gray-900 dark:text-gray-400 focus:outline-none p-3" :disabled="item.noPdf" :required="!item.noPdf && item.xmlParsed && hasInvoice && !item.pdfName" x-on:change="handlePdfChange($event, index)">
                                                    <template x-if="item.pdfName">
                                                        <div class="mt-1 flex items-center justify-between">
                                                            <div class="flex items-center text-[10px] text-green-600 font-bold uppercase italic">
                                                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"></path></svg>
                                                                <span>PDF Guardado</span>
                                                            </div>
                                                            <template x-if="item.draftId">
                                                                <a :href="'/reimbursements/' + item.draftId + '/view-file/pdf'" target="_blank" class="text-[10px] font-black text-indigo-600 hover:text-indigo-800 uppercase tracking-widest flex items-center bg-indigo-50 px-2 py-0.5 rounded-lg transition-colors border border-indigo-100">
                                                                    VER
                                                                    <svg class="w-2 h-2 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                                                                </a>
                                                            </template>
                                                        </div>
                                                    </template>
                                                    
                                                    <div class="mt-4">
                                                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2 flex items-center justify-between">
                                                            <span>Ticket / Pruebas Adicionales</span>
                                                            <span class="text-[9px] font-black text-gray-400 uppercase italic">Máx 10MB</span>
                                                        </label>
                                                        <input type="file" :name="'items['+index+'][ticket_file]'" accept=".pdf,.jpg,.jpeg,.png,.txt" class="block w-full text-xs text-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl cursor-pointer bg-gray-50 dark:bg-gray-900 dark:text-gray-400 focus:outline-none p-2" :required="!item.ticketName" x-on:change="handleTicketChange($event, index)">
                                                        <template x-if="item.ticketName">
                                                            <div class="mt-1 flex items-center justify-between">
                                                                <div class="flex items-center text-[10px] text-green-600 font-bold uppercase italic">
                                                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"></path></svg>
                                                                    <span>Ticket Guardado</span>
                                                                </div>
                                                                <template x-if="item.draftId">
                                                                    <a :href="'/reimbursements/' + item.draftId + '/view-file/ticket'" target="_blank" class="text-[10px] font-black text-indigo-600 hover:text-indigo-800 uppercase tracking-widest flex items-center bg-indigo-50 px-2 py-0.5 rounded-lg transition-colors border border-indigo-100">
                                                                        VER
                                                                        <svg class="w-2 h-2 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                                                                    </a>
                                                                </template>
                                                            </div>
                                                        </template>
                                                    </div>

                                                    <!-- PDF Validation Indicator -->
                                                    <div x-show="item.data.pdf_validation" class="mt-2 space-y-2 animate-fadeIn text-[10px] font-black uppercase tracking-widest">
                                                        <!-- UUID Badge -->
                                                        <template x-if="item.data.pdf_validation?.uuid_match">
                                                            <div class="flex items-center text-green-600 bg-green-50 dark:bg-green-900/20 px-3 py-1.5 rounded-lg border border-green-100">
                                                                <svg class="w-3 h-3 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                                                                UUID VALIDADO
                                                            </div>
                                                        </template>
                                                        <template x-if="item.data.pdf_validation && !item.data.pdf_validation.uuid_match && !item.data.pdf_validation.error">
                                                            <div class="flex items-center text-red-600 bg-red-50 dark:bg-red-900/20 px-3 py-1.5 rounded-lg border border-red-100">
                                                                <svg class="w-3 h-3 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                                                UUID NO COINCIDE
                                                            </div>
                                                        </template>

                                                        <!-- Total Badge -->
                                                        <template x-if="item.data.pdf_validation?.total_match">
                                                            <div class="flex items-center text-green-600 bg-green-50 dark:bg-green-900/20 px-3 py-1.5 rounded-lg border border-green-100">
                                                                <svg class="w-3 h-3 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                                                                TOTAL VALIDADO
                                                            </div>
                                                        </template>
                                                        <template x-if="item.data.pdf_validation && !item.data.pdf_validation.total_match && !item.data.pdf_validation.error">
                                                            <div class="flex items-center text-red-600 bg-red-50 dark:bg-red-900/20 px-3 py-1.5 rounded-lg border border-red-100">
                                                                <svg class="w-3 h-3 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                                                IMPORTE NO COINCIDE
                                                            </div>
                                                        </template>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>

                                         <template x-if="!hasInvoice">
                                            <div class="space-y-6">
                                                <h4 class="text-[10px] font-black uppercase text-gray-400 tracking-[0.2em] border-b pb-3">Comprobante</h4>
                                                <div>
                                                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Ticket / Pruebas Adicionales</label>
                                                    <input type="file" :name="'items['+index+'][ticket_file]'" accept=".pdf,.jpg,.jpeg,.png,.txt" class="block w-full text-xs text-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl cursor-pointer bg-gray-50 dark:bg-gray-900 dark:text-gray-400 focus:outline-none p-2 mb-4" :required="!item.ticketName" x-on:change="handleTicketChange($event, index)">

                                                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-3">Archivo de Respaldado (PDF/Imagen) *</label>
                                                    <input type="file" :name="'items['+index+'][pdf_file]'" accept=".pdf,image/*,.txt" class="block w-full text-xs text-gray-900 border border-gray-100 dark:border-gray-700 rounded-xl cursor-pointer bg-gray-50 dark:bg-gray-900 dark:text-gray-400 focus:outline-none p-3" :required="!hasInvoice && !item.pdfName" x-on:change="handlePdfChange($event, index)">
                                                    <template x-if="item.pdfName">
                                                        <div class="mt-1 flex items-center text-[10px] text-green-600 font-bold uppercase italic">
                                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"></path></svg>
                                                            <span x-text="item.pdfName"></span>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>
                                        </template>

                                        <!-- Clasificación -->
                                        <div class="space-y-6">
                                            <h4 class="text-[10px] font-black uppercase text-gray-400 tracking-[0.2em] border-b pb-3">Clasificación</h4>
                                            <div>
                                                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-3">Categoría *</label>
                                                <select :name="'items['+index+'][category]'" x-model="item.data.category" class="w-full border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-xl shadow-sm focus:ring-indigo-500 text-sm py-3" required>
                                                    <option value="">Selecciona...</option>
                                                    @foreach($categories as $cat)
                                                        <option value="{{ $cat }}">{{ $cat }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-3">Justificación *</label>
                                                <textarea :name="'items['+index+'][observaciones]'" x-model="item.data.observaciones" rows="4" class="w-full border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-xl shadow-sm text-sm" required placeholder="Motivo del gasto..."></textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Right Column: ALL DATA FIELDS -->
                                    <div class="lg:col-span-8 space-y-8">
                                        <h4 class="text-[10px] font-black uppercase text-gray-400 tracking-[0.2em] border-b pb-3" x-text="hasInvoice ? 'Información del Sistema (CFDI)' : 'Información Manual'"></h4>
                                        
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-10 gap-y-6">
                                            <div class="col-span-1 md:col-span-2" x-show="hasInvoice">
                                                <label class="block text-[10px] font-bold text-gray-400 uppercase mb-2">Folio Fiscal (UUID)</label>
                                                <input type="text" :name="'items['+index+'][uuid]'" :value="item.data.uuid" class="w-full bg-gray-50 dark:bg-gray-900/50 border-gray-200 dark:border-gray-700 rounded-xl text-xs font-mono text-gray-600 dark:text-gray-400" readonly placeholder="Esperando XML...">
                                            </div>
                                            
                                            <div x-show="hasInvoice">
                                                <label class="block text-[10px] font-bold text-gray-400 uppercase mb-2">RFC Emisor</label>
                                                <input type="text" :name="'items['+index+'][rfc_emisor]'" :value="item.data.rfc_emisor" class="w-full bg-gray-50 dark:bg-gray-900/50 border-gray-200 dark:border-gray-700 rounded-xl text-xs" readonly>
                                            </div>
                                            <div x-show="hasInvoice">
                                                <label class="block text-[10px] font-bold text-gray-400 uppercase mb-2">Folio Interno (XML)</label>
                                                <input type="text" :name="'items['+index+'][folio]'" :value="item.data.folio" class="w-full bg-gray-50 dark:bg-gray-900/50 border-gray-200 dark:border-gray-700 rounded-xl text-xs" readonly>
                                            </div>
                                            
                                            <div x-show="hasInvoice">
                                                <label class="block text-[10px] font-bold text-gray-400 uppercase mb-2">Método de Pago</label>
                                                <input type="text" :name="'items['+index+'][metodo_pago]'" :value="item.data.metodo_pago" class="w-full bg-gray-50 dark:bg-gray-900/50 border-gray-200 dark:border-gray-700 rounded-xl text-xs" readonly>
                                            </div>
                                            <div x-show="hasInvoice">
                                                <label class="block text-[10px] font-bold text-gray-400 uppercase mb-2">Forma de Pago</label>
                                                <input type="text" :name="'items['+index+'][forma_pago]'" :value="item.data.forma_pago" class="w-full bg-gray-50 dark:bg-gray-900/50 border-gray-200 dark:border-gray-700 rounded-xl text-xs" readonly>
                                            </div>
                                            <div x-show="hasInvoice">
                                                <label class="block text-[10px] font-bold text-gray-400 uppercase mb-2">Uso CFDI</label>
                                                <input type="text" :name="'items['+index+'][uso_cfdi]'" :value="item.data.uso_cfdi" class="w-full bg-gray-50 dark:bg-gray-900/50 border-gray-200 dark:border-gray-700 rounded-xl text-xs" readonly>
                                            </div>
                                            <div x-show="hasInvoice">
                                                <label class="block text-[10px] font-bold text-gray-400 uppercase mb-2">Lugar Expedición (CP)</label>
                                                <input type="text" :name="'items['+index+'][lugar_expedicion]'" :value="item.data.lugar_expedicion" class="w-full bg-gray-50 dark:bg-gray-900/50 border-gray-200 dark:border-gray-700 rounded-xl text-xs" readonly>
                                            </div>
                                            <div x-show="hasInvoice" class="col-span-2">
                                                <label class="block text-[10px] font-bold text-gray-400 uppercase mb-2">Regimen Fiscal Emisor</label>
                                                <input type="text" :name="'items['+index+'][regimen_fiscal_emisor]'" :value="item.data.regimen_fiscal_emisor" class="w-full bg-gray-50 dark:bg-gray-900/50 border-gray-200 dark:border-gray-700 rounded-xl text-xs" readonly>
                                            </div>

                                            <div :class="!hasInvoice ? 'col-span-2' : ''">
                                                <label class="block text-[10px] font-bold text-gray-400 uppercase mb-2">Nombre Emisor *</label>
                                                <input type="text" :name="'items['+index+'][nombre_emisor]'" x-model="item.data.nombre_emisor" class="w-full bg-gray-50 dark:bg-gray-900/50 border-gray-200 dark:border-gray-700 rounded-xl text-xs" :readonly="hasInvoice" :required="!hasInvoice" placeholder="Nombre de la empresa o negocio">
                                            </div>
                                            <div>
                                                <label class="block text-[10px] font-bold text-gray-400 uppercase mb-2">Fecha Emisión *</label>
                                                <input type="date" :name="'items['+index+'][fecha]'" x-model="item.data.fecha" :max="new Date().toISOString().split('T')[0]" class="w-full bg-gray-50 dark:bg-gray-900/50 border-gray-200 dark:border-gray-700 rounded-xl text-xs" :readonly="hasInvoice" :required="!hasInvoice">
                                            </div>

                                            <template x-if="hasInvoice">
                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-10 gap-y-6 col-span-1 md:col-span-2 contents">
                                                    <div class="border-t border-gray-50 dark:border-gray-700/50 pt-4 col-span-1 md:col-span-2"></div>

                                                    <div>
                                                        <label class="block text-[10px] font-bold text-gray-400 uppercase mb-2">RFC Receptor</label>
                                                        <input type="text" :name="'items['+index+'][rfc_receptor]'" :value="item.data.rfc_receptor" class="w-full bg-gray-50 dark:bg-gray-900/50 border-gray-200 dark:border-gray-700 rounded-xl text-xs" readonly>
                                                    </div>
                                                    <div>
                                                        <label class="block text-[10px] font-bold text-gray-400 uppercase mb-2">Nombre Receptor</label>
                                                        <input type="text" :name="'items['+index+'][nombre_receptor]'" :value="item.data.nombre_receptor" class="w-full bg-gray-50 dark:bg-gray-900/50 border-gray-200 dark:border-gray-700 rounded-xl text-xs mb-3" readonly>
                                                        <!-- RESTORED CONFIRMATION -->
                                                        <div class="flex items-center">
                                                            <input type="checkbox" :name="'items['+index+'][confirm_company]'" class="w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500" required>
                                                            <label class="ml-2 text-[10px] font-black text-indigo-600 dark:text-indigo-400 uppercase italic">
                                                                Confirmo que esta es la empresa en la que estoy dado de alta como colaborador(a).
                                                            </label>
                                                        </div>
                                                    </div>

                                                    <div class="border-t border-gray-50 dark:border-gray-700/50 pt-4 col-span-2"></div>
                                                </div>
                                            </template>

                                            <div>
                                                <label class="block text-[10px] font-bold text-gray-400 uppercase mb-2 text-indigo-600">Subtotal *</label>
                                                <div class="relative">
                                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-xs font-bold text-gray-400">$</span>
                                                    <input type="number" step="0.01" :name="'items['+index+'][subtotal]'" x-model="item.data.subtotal" class="w-full bg-gray-50 dark:bg-gray-900/50 border-gray-200 dark:border-gray-700 rounded-xl text-sm font-bold text-indigo-600 pl-8" :readonly="hasInvoice" :required="!hasInvoice">
                                                </div>
                                            </div>

                                            <div>
                                                <label class="block text-[10px] font-bold text-gray-400 uppercase mb-2 text-amber-600">Impuestos (IVA)</label>
                                                <div class="relative">
                                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-xs font-bold text-gray-400">$</span>
                                                    <input type="number" step="0.01" :name="'items['+index+'][impuestos]'" x-model="item.data.impuestos" class="w-full bg-gray-50 dark:bg-gray-900/50 border-gray-200 dark:border-gray-700 rounded-xl text-sm font-bold text-amber-600 pl-8" :readonly="hasInvoice">
                                                </div>
                                            </div>
                                            <div>
                                                <label class="block text-[10px] font-black text-white bg-indigo-600 rounded-t-lg px-2 py-1 uppercase mb-0 tracking-widest inline-block">Total del Gasto *</label>
                                                <div class="relative">
                                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-lg font-black text-indigo-300">$</span>
                                                    <input type="number" step="0.01" :name="'items['+index+'][total]'" x-model="item.data.total" class="w-full bg-indigo-50 dark:bg-indigo-900/50 border-indigo-200 dark:border-indigo-800 rounded-xl rounded-tl-none text-xl font-black text-indigo-700 dark:text-indigo-300 py-3 pl-10" :readonly="hasInvoice" :required="!hasInvoice" 
                                                        @input="if(!hasInvoice) item.data.impuestos = (parseFloat(item.data.total || 0) - parseFloat(item.data.subtotal || 0)).toFixed(2)">
                                                </div>
                                                
                                                <template x-if="!hasInvoice && parseFloat(item.data.subtotal) > parseFloat(item.data.total)">
                                                    <p class="mt-1 text-[9px] font-bold text-red-600 uppercase">El subtotal no puede ser mayor al total</p>
                                                </template>
                                                
                                                <!-- Limit Warning -->
                                                <template x-if="!hasInvoice && item.data.total > 2000">
                                                    <div class="mt-4 p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-2xl animate-shake">
                                                        <div class="flex items-start">
                                                            <div class="flex-shrink-0">
                                                                <svg class="h-5 w-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                                </svg>
                                                            </div>
                                                            <div class="ml-3">
                                                                <h3 class="text-xs font-black text-red-800 dark:text-red-400 uppercase tracking-tight">Límite Excedido</h3>
                                                                <p class="mt-1 text-[10px] font-bold text-red-700 dark:text-red-300 leading-relaxed uppercase">
                                                                    No se pueden hacer reembolsos mayores a $2,000 MXN sin factura. 
                                                                    Por favor comunícate con tu director para revisar la situación.
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                        </div>

                                        @if($type === 'comida')
                                            <div class="lg:col-span-12 mt-12 group/comida">
                                                <div class="bg-gradient-to-br from-orange-50 to-white dark:from-orange-900/10 dark:to-gray-800 p-8 rounded-[2.5rem] border-2 border-orange-100 dark:border-orange-900/30 shadow-xl shadow-orange-500/5 relative overflow-hidden transition-all hover:shadow-orange-500/10">
                                                    <!-- Decorative Icon Background -->
                                                    <div class="absolute -right-10 -top-10 opacity-5 group-hover/comida:scale-110 transition-transform duration-700">
                                                        <svg class="w-48 h-48 text-orange-600" fill="currentColor" viewBox="0 0 24 24"><path d="M11 9H9V2H7V9H5V2H3V9C3 11.12 4.66 12.84 6.75 12.97V22H9.25V12.97C11.34 12.84 13 11.12 13 9V2H11V9ZM16 6V14H18.5V22H21V2C18.24 2 16 4.24 16 6Z"/></svg>
                                                    </div>

                                                    <div class="relative flex items-center gap-4 mb-8 border-b border-orange-100 dark:border-orange-900/30 pb-6">
                                                        <div class="w-12 h-12 bg-orange-600 rounded-2xl flex items-center justify-center text-white shadow-lg shadow-orange-500/30">
                                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                                                        </div>
                                                        <div>
                                                            <h4 class="text-xl font-black text-gray-900 dark:text-white uppercase tracking-tight">Detalles de la Comida</h4>
                                                            <p class="text-[10px] font-bold text-orange-500 uppercase tracking-widest">Información de representación requerida</p>
                                                        </div>
                                                    </div>

                                                    <div class="grid grid-cols-1 md:grid-cols-12 gap-8 relative">
                                                        <div class="md:col-span-4">
                                                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Nº Asistentes *</label>
                                                            <div class="relative">
                                                                <input type="number" :name="'items['+index+'][attendees_count]'" min="1" class="w-full bg-white dark:bg-gray-900 border-2 border-orange-50 dark:border-orange-900/20 rounded-2xl py-4 px-6 text-sm font-bold focus:ring-4 focus:ring-orange-500/10 focus:border-orange-500 transition-all placeholder-gray-300" :required="type === 'comida'" placeholder="0">
                                                                <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none">
                                                                    <svg class="w-5 h-5 text-orange-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="md:col-span-8">
                                                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Establecimiento / Lugar *</label>
                                                            <div class="relative">
                                                                <input type="text" :name="'items['+index+'][location]'" class="w-full bg-white dark:bg-gray-900 border-2 border-orange-50 dark:border-orange-900/20 rounded-2xl py-4 px-6 text-sm font-bold focus:ring-4 focus:ring-orange-500/10 focus:border-orange-500 transition-all placeholder-gray-300" :required="type === 'comida'" placeholder="Nombre del restaurante o local">
                                                                <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none">
                                                                    <svg class="w-5 h-5 text-orange-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.828a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="md:col-span-12">
                                                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Relación de Invitados (Nombres)</label>
                                                            <textarea :name="'items['+index+'][attendees_names]'" rows="3" class="w-full bg-white dark:bg-gray-900 border-2 border-orange-50 dark:border-orange-900/20 rounded-2xl p-6 text-sm font-medium focus:ring-4 focus:ring-orange-500/10 focus:border-orange-500 transition-all placeholder-gray-300" :required="type === 'comida'" placeholder="Escribe los nombres de las personas que asistieron..."></textarea>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>

                    <div class="flex flex-col items-center space-y-4">
                        <!-- Limits Indicator -->
                        <div class="flex flex-wrap justify-center gap-6 mb-2">
                            <div class="flex items-center space-x-2">
                                <span class="text-[10px] font-black uppercase tracking-widest text-gray-400">Archivos:</span>
                                <span :class="items.length >= maxItems ? 'text-red-600' : 'text-indigo-600'" class="text-xs font-bold" x-text="items.length + ' / ' + maxItems"></span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="text-[10px] font-black uppercase tracking-widest text-gray-400">Peso Total:</span>
                                <span :class="currentTotalSize >= maxTotalSize * 0.9 ? 'text-red-600' : 'text-indigo-600'" class="text-xs font-bold" x-text="(currentTotalSize / (1024*1024)).toFixed(2) + 'MB / ' + (maxTotalSize / (1024*1024)) + 'MB'"></span>
                            </div>
                        </div>

                        <button type="button" 
                            x-on:click="addItem()" 
                            :disabled="items.length >= maxItems || currentTotalSize >= maxTotalSize"
                            :class="(items.length >= maxItems || currentTotalSize >= maxTotalSize) ? 'opacity-50 grayscale cursor-not-allowed' : 'hover:shadow-2xl transform hover:scale-105'"
                            class="group flex items-center justify-center p-1 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-[2rem] transition-all">
                            <div class="bg-white dark:bg-gray-800 px-12 py-6 rounded-[1.9rem] flex items-center">
                                <div class="w-10 h-10 bg-indigo-600 text-white rounded-full flex items-center justify-center mr-4 group-hover:rotate-180 transition-transform duration-500">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                </div>
                                <span class="text-xl font-black text-gray-900 dark:text-white uppercase tracking-tighter" x-text="hasInvoice ? 'AGREGAR FACTURA' : 'AGREGAR COMPROBANTE'"></span>
                            </div>
                        </button>
                        
                        <template x-if="items.length >= maxItems">
                            <p class="text-[10px] font-bold text-red-600 uppercase tracking-tight animate-shake">Límite de 20 facturas alcanzado por carga.</p>
                        </template>
                        <template x-if="currentTotalSize >= maxTotalSize">
                            <p class="text-[10px] font-bold text-red-600 uppercase tracking-tight animate-shake">Límite de peso total (64MB) excedido. Por favor registra estas facturas y crea una nueva sesión.</p>
                        </template>
                    </div>
                </div>

                <!-- VIAJE FORM -->
                <div x-show="false" class="animate-fadeIn"> <!-- DEPRECATED SINGLE TRIP FORM -->
                    <div class="bg-white dark:bg-gray-800 shadow-2xl rounded-[2.5rem] border border-gray-100 dark:border-gray-700 overflow-hidden relative p-8 md:p-10">
                        <div class="flex items-center mb-8">
                            <div class="w-12 h-12 bg-purple-600 rounded-2xl flex items-center justify-center text-white shadow-lg shadow-purple-500/30 mr-5">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                            <div>
                                <h3 class="text-2xl font-black text-gray-900 dark:text-white uppercase tracking-tight">Detalles del Viaje</h3>
                                <p class="text-sm text-gray-500 font-medium">Información general sobre el viaje o comisión.</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div class="md:col-span-2">
                                <label class="block text-xs font-black text-gray-400 uppercase tracking-[0.2em] mb-3">Título del Viaje (Ej: Visita Obra Querétaro) *</label>
                                <input type="text" name="title" x-model="title" class="w-full border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-2xl shadow-sm focus:ring-4 focus:ring-purple-500/10 focus:border-purple-500 transition-all py-4 px-5">
                            </div>

                            <div>
                                <label class="block text-xs font-black text-gray-400 uppercase tracking-[0.2em] mb-3">Tipo de Viaje *</label>
                                <select name="trip_type" x-model="tripType" class="w-full border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-2xl shadow-sm focus:ring-4 focus:ring-purple-500/10 focus:border-purple-500 transition-all py-4 px-5">
                                    <option value="nacional">Nacional</option>
                                    <option value="internacional">Internacional</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-black text-gray-400 uppercase tracking-[0.2em] mb-3">Destino *</label>
                                <input type="text" name="trip_destination" x-model="tripDestination" class="w-full border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-2xl shadow-sm focus:ring-4 focus:ring-purple-500/10 focus:border-purple-500 transition-all py-4 px-5" placeholder="Ciudad, Estado/Pais">
                            </div>

                            <div>
                                <label class="block text-xs font-black text-gray-400 uppercase tracking-[0.2em] mb-3">Noches *</label>
                                <input type="number" name="trip_nights" x-model="tripNights" min="0" value="0" class="w-full border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-2xl shadow-sm focus:ring-4 focus:ring-purple-500/10 focus:border-purple-500 transition-all py-4 px-5">
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-black text-gray-400 uppercase tracking-[0.2em] mb-3">Fecha Inicio *</label>
                                    <input type="date" name="trip_start_date" x-model="tripStartDate" class="w-full border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-2xl shadow-sm focus:ring-4 focus:ring-purple-500/10 focus:border-purple-500 transition-all py-4 px-5">
                                </div>
                                <div>
                                    <label class="block text-xs font-black text-gray-400 uppercase tracking-[0.2em] mb-3">Fecha Fin *</label>
                                    <input type="date" name="trip_end_date" x-model="tripEndDate" class="w-full border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-2xl shadow-sm focus:ring-4 focus:ring-purple-500/10 focus:border-purple-500 transition-all py-4 px-5">
                                </div>
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-xs font-black text-gray-400 uppercase tracking-[0.2em] mb-3">Observaciones / Justificación</label>
                                <textarea name="observaciones" x-model="observacionesGeneral" rows="3" class="w-full border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-2xl shadow-sm focus:ring-4 focus:ring-purple-500/10 focus:border-purple-500 transition-all py-4 px-5" placeholder="Describe el motivo del viaje..."></textarea>
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-xs font-black text-gray-400 uppercase tracking-[0.2em] mb-3">Ticket / Pruebas Adicionales (Comprobante único para el viaje)</label>
                                <input type="file" name="ticket_file" accept=".pdf,.jpg,.jpeg,.png,.txt" class="block w-full text-sm text-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl cursor-pointer bg-gray-50 dark:bg-gray-900 focus:outline-none p-2.5 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" x-on:change="handleTicketChange($event)">
                            </div>

                            <div class="md:col-span-2" x-show="tripType === 'internacional'">
                                <label class="block text-xs font-black text-gray-400 uppercase tracking-[0.2em] mb-3">Documentación Adicional (PDF, Imágenes) - Viajes Internacionales</label>
                                <input type="file" name="extra_files[]" multiple class="w-full border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-2xl shadow-sm focus:ring-4 focus:ring-purple-500/10 focus:border-purple-500 transition-all py-4 px-5">
                                <p class="text-[10px] text-gray-500 mt-2 italic font-bold">Puedes seleccionar múltiples archivos para comprobar gastos en el extranjero que no cuentan con XML.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="mt-24 border-t border-gray-100 dark:border-gray-700 pt-12 pb-32 flex flex-col md:flex-row items-center justify-between gap-10">
                    <a href="{{ route('reimbursements.index') }}" class="text-[10px] font-black text-gray-400 hover:text-gray-900 dark:hover:text-white uppercase tracking-[0.3em] transition-all">CANCELAR TODO</a>
                    
                    <div class="flex items-center space-x-12">
                    <div x-show="items.length > 0" class="text-right hidden sm:block">
                            <span class="block text-[10px] font-black text-indigo-400 uppercase mb-1">Total Acumulado</span>
                            <span class="block text-4xl font-black text-gray-900 dark:text-white" x-text="'$ ' + calculateTotal().toLocaleString('es-MX', {minimumFractionDigits: 2})"></span>
                        </div>
                        
                        <div class="flex flex-col items-center space-y-4">
                            <div x-show="lastAutoSave" x-cloak class="text-[10px] font-black text-gray-400 uppercase tracking-widest animate-pulse">
                                <span class="text-green-500">●</span> Guardado autom. <span x-text="lastAutoSave"></span>
                            </div>
                            <div class="flex items-center space-x-4">
                                <button type="button" @click="saveDraft(false)" 
                                    class="inline-flex items-center px-8 py-4 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-gray-200 dark:hover:bg-gray-600 transition-all border-b-4 border-gray-200 dark:border-gray-900 active:border-b-0 active:translate-y-1">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
                                    GUARDAR BORRADOR
                                </button>
                                
                                <button type="submit" 
                                    :disabled="!hasInvoice && (items.some(i => i.data.total > 2000) || items.some(i => parseFloat(i.data.subtotal) > parseFloat(i.data.total)))"
                                    :class="!hasInvoice && (items.some(i => i.data.total > 2000) || items.some(i => parseFloat(i.data.subtotal) > parseFloat(i.data.total))) ? 'opacity-50 cursor-not-allowed grayscale' : ''"
                                    class="group inline-flex items-center px-12 py-6 bg-indigo-600 text-white rounded-[1.5rem] font-black text-xl uppercase italic hover:bg-indigo-700 shadow-xl transition-all transform hover:scale-105">
                                    <span>REGISTRAR</span>
                                    <svg class="w-6 h-6 ml-3 group-hover:translate-x-2 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7-7 7M5 5l7 7-7 7"></path></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
        function reimbursementForm() {
            return {
                type: '{{ $type }}',
                chargeType: 'cost_center',
                tripType: 'nacional',
                title: '',
                tripDestination: '',
                tripNights: 0,
                tripStartDate: '',
                tripEndDate: '',
                observacionesGeneral: '',
                items: [],
                maxItems: 20,
                @php
                    $val = ini_get('upload_max_filesize');
                    $val = trim($val);
                    $last = strtolower($val[strlen($val)-1]);
                    $val = (int)$val;
                    switch($last) {
                        case 'g': $val *= 1024;
                        case 'm': $val *= 1024;
                        case 'k': $val *= 1024;
                    }
                    $phpMax = $val ?: (2 * 1024 * 1024);
                    // Use the minimum between our 10MB app limit and PHP's hard limit
                    $appMax = 10 * 1024 * 1024;
                    $finalMax = min($phpMax, $appMax);
                @endphp
                maxFileSize: {{ $finalMax }}, // PHP upload_max_filesize or 10MB
                maxTotalSize: 64 * 1024 * 1024, // 64 MB Total
                currentTotalSize: 0,
                hasInvoice: {{ $hasInvoice ? 'true' : 'false' }},
                draftId: {{ isset($reimbursement) ? $reimbursement->id : 'null' }},
                lastAutoSave: null,
                isSaving: false,
                init() { 
                    @if(isset($reimbursement))
                        this.chargeType = @json($reimbursement->travel_event_id ? "travel_event" : "cost_center");
                        this.tripType = @json($reimbursement->trip_type ?? "nacional");
                        this.title = @json($reimbursement->title);
                        this.tripDestination = @json($reimbursement->trip_destination);
                        this.tripNights = {{ $reimbursement->trip_nights ?? 0 }};
                        this.tripStartDate = @json($reimbursement->trip_start_date ? $reimbursement->trip_start_date->format("Y-m-d") : "");
                        this.tripEndDate = @json($reimbursement->trip_end_date ? $reimbursement->trip_end_date->format("Y-m-d") : "");
                        this.observacionesGeneral = @json($reimbursement->observaciones);
                        
                        @if($reimbursement->type === 'viaje')
                            @foreach($reimbursement->children as $child)
                                this.addItem({
                                    draftId: @json($child->id),
                                    fileName: @json($child->xml_path ? "Factura: " . ($child->folio ?: (substr($child->uuid, 0, 8) ?: 'Cargada')) : ""),
                                    pdfName: @json($child->pdf_path ? "PDF Guardado" : ""),
                                    ticketName: @json($child->ticket_path ? "Ticket/Prueba Guardado" : ""),
                                    xmlParsed: {{ $child->xml_path ? 'true' : 'false' }},
                                    data: {
                                        uuid: @json($child->uuid),
                                        folio: @json($child->folio),
                                        rfc_emisor: @json($child->rfc_emisor),
                                        nombre_emisor: @json($child->nombre_emisor),
                                        rfc_receptor: @json($child->rfc_receptor),
                                        nombre_receptor: @json($child->nombre_receptor),
                                        fecha: @json($child->fecha ? $child->fecha->format("Y-m-d") : ""),
                                        total: {{ $child->total ?: 0 }},
                                        subtotal: {{ $child->subtotal ?: 0 }},
                                        impuestos: {{ $child->impuestos ?: 0 }},
                                        moneda: @json($child->moneda ?: "MXN"),
                                        metodo_pago: @json($child->metodo_pago),
                                        forma_pago: @json($child->forma_pago),
                                        uso_cfdi: @json($child->uso_cfdi),
                                        lugar_expedicion: @json($child->lugar_expedicion),
                                        regimen_fiscal_emisor: @json($child->regimen_fiscal_emisor),
                                        category: @json($child->category),
                                        pdf_validation: @json($child->validation_data),
                                        observaciones: @json($child->observaciones)
                                    }
                                });
                            @endforeach
                            if (this.items.length === 0) this.addItem();
                        @else
                            this.addItem({
                                draftId: @json($reimbursement->id),
                                fileName: @json($reimbursement->xml_path ? "Factura: " . ($reimbursement->folio ?: (substr($reimbursement->uuid, 0, 8) ?: 'Cargada')) : ""),
                                pdfName: @json($reimbursement->pdf_path ? "PDF Guardado" : ""),
                                ticketName: @json($reimbursement->ticket_path ? "Ticket/Prueba Guardado" : ""),
                                xmlParsed: {{ $reimbursement->xml_path ? 'true' : 'false' }},
                                data: {
                                    uuid: @json($reimbursement->uuid),
                                    folio: @json($reimbursement->folio),
                                    rfc_emisor: @json($reimbursement->rfc_emisor),
                                    nombre_emisor: @json($reimbursement->nombre_emisor),
                                    rfc_receptor: @json($reimbursement->rfc_receptor),
                                    nombre_receptor: @json($reimbursement->nombre_receptor),
                                    fecha: @json($reimbursement->fecha ? $reimbursement->fecha->format("Y-m-d") : ""),
                                    total: {{ $reimbursement->total ?: 0 }},
                                    subtotal: {{ $reimbursement->subtotal ?: 0 }},
                                    impuestos: {{ $reimbursement->impuestos ?: 0 }},
                                    moneda: @json($reimbursement->moneda ?: "MXN"),
                                    metodo_pago: @json($reimbursement->metodo_pago),
                                    forma_pago: @json($reimbursement->forma_pago),
                                    uso_cfdi: @json($reimbursement->uso_cfdi),
                                    lugar_expedicion: @json($reimbursement->lugar_expedicion),
                                    regimen_fiscal_emisor: @json($reimbursement->regimen_fiscal_emisor),
                                    category: @json($reimbursement->category),
                                    pdf_validation: @json($reimbursement->validation_data),
                                    observaciones: @json($reimbursement->observaciones)
                                }
                            });
                        @endif
                    @else
                        this.addItem(); 
                    @endif

                    // Trigger validation for items that have both files but no validation record yet
                    this.items.forEach((item, index) => {
                        if (item.draftId && item.xmlParsed && item.pdfName && (!item.data || !item.data.pdf_validation)) {
                            this.validateFiles(index);
                        }
                    });

                    // Auto-save every 30 seconds
                    setInterval(() => this.saveDraft(true), 30000);
                },
                addItem(initialData = null) {
                    if (this.items.length >= this.maxItems) return;
                    
                    const newItem = {
                        id: Date.now() + Math.random(),
                        draftId: initialData ? initialData.draftId : null,
                        fileName: initialData ? initialData.fileName : '',
                        pdfName: initialData ? initialData.pdfName : '',
                        ticketName: initialData ? initialData.ticketName : '',
                        xmlParsed: initialData ? initialData.xmlParsed : false,
                        noPdf: false,
                        manualData: { nombre_emisor: '', fecha: '', subtotal: 0, total: 0, impuestos: 0 },
                        data: initialData ? initialData.data : { 
                            uuid: '', folio: '', rfc_emisor: '', nombre_emisor: '', rfc_receptor: '', 
                            nombre_receptor: '', fecha: '', moneda: 'MXN', subtotal: 0, total: 0, 
                            impuestos: 0, metodo_pago: '', forma_pago: '', uso_cfdi: '', 
                            lugar_expedicion: '', regimen_fiscal_emisor: '', category: '', observaciones: '' 
                        }
                    };
                    
                    this.items.push(newItem);
                },
                async saveDraft(isAuto = false) {
                    if (this.isSaving) return;

                    // NEW: Prevent autosave if Step 2 is empty
                    if (isAuto) {
                        let hasStep2Data = this.items.some(item => {
                            return item.draftId || // Already saved
                                   item.xmlParsed || // XML exists
                                   item.pdfName || // PDF preview exists
                                   item.ticketName || // Ticket preview exists
                                   parseFloat(item.data.total) > 0 || // Amount exists
                                   (item.data.nombre_emisor && item.data.nombre_emisor.trim() !== '') ||
                                   (item.data.observaciones && item.data.observaciones.trim() !== '');
                        });

                        if (!hasStep2Data) return; // Ignore if no data in Step 2
                    }

                    this.isSaving = true;
                    
                    try {
                        const formElem = document.getElementById('reimbursement-form');
                        const formData = new FormData(formElem);
                        
                        // IF isAuto, we could strip files, but user wants everything.
                        // However, to avoid timeouts, for AUTO-SAVE we'll strip them IF they haven't changed.
                        // For simplicity now, let's just send everything but handle the response.

                        if (this.draftId) {
                            formData.append('draft_id', this.draftId);
                        }
                        
                        // Also append individual item draft IDs if they exist
                        this.items.forEach((item, index) => {
                            if (item.draftId) {
                                formData.append(`items[${index}][draft_id]`, item.draftId);
                            }
                        });

                        formData.append('_token', '{{ csrf_token() }}');

                        const response = await fetch('{{ route("reimbursements.auto_save") }}', {
                            method: 'POST',
                            body: formData,
                            headers: { 'X-Requested-With': 'XMLHttpRequest' }
                        });

                        const result = await response.json();
                        if (result.success) {
                            // Update IDs
                            if (result.main_id) this.draftId = result.main_id;
                            if (result.ids) {
                                Object.keys(result.ids).forEach(index => {
                                    if (this.items[index]) {
                                        const r = result.ids[index];
                                        this.items[index].draftId = r.id;
                                        if (r.has_xml) {
                                            this.items[index].xmlParsed = true;
                                            if (!this.items[index].fileName) {
                                                this.items[index].fileName = 'Factura: ' + (r.folio || 'Guardada');
                                            }
                                        }
                                        if (r.has_pdf) this.items[index].pdfName = 'PDF Guardado';
                                        if (r.has_ticket) this.items[index].ticketName = 'Ticket Guardado';
                                        if (r.folio) this.items[index].folio = r.folio;
                                    }
                                });
                            }

                            this.lastAutoSave = new Date().toLocaleTimeString();
                            if (!isAuto) {
                                Swal.fire({
                                    icon: 'success',
                                    title: '<span class="text-lg font-black uppercase tracking-tight">Borrador Guardado</span>',
                                    text: 'Se han guardado todos los archivos y datos.',
                                    timer: 2000,
                                    showConfirmButton: false,
                                    toast: true,
                                    position: 'top-end',
                                    customClass: {
                                        popup: 'rounded-2xl border-none shadow-2xl dark:bg-gray-800'
                                    }
                                });
                            }
                        } else {
                            throw new Error(result.error || 'Server error');
                        }
                    } catch (e) {
                        console.error('Draft Save Error:', e);
                        if (!isAuto) {
                            Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo guardar: ' + e.message });
                        }
                    } finally {
                        this.isSaving = false;
                    }
                },
                removeItem(index) { 
                    if (this.items.length > 1) {
                        this.items.splice(index, 1); 
                        this.$nextTick(() => this.calculateTotalFileSize());
                    } else {
                        Swal.fire({
                            title: '<span class="text-xl font-black uppercase tracking-tight">Atención</span>',
                            html: '<p class="text-sm font-medium text-gray-500">Debes registrar al menos un comprobante para continuar.</p>',
                            icon: 'info',
                            confirmButtonText: 'ENTENDIDO',
                            confirmButtonColor: '#4f46e5',
                            customClass: {
                                popup: 'rounded-[1.5rem] border-none shadow-2xl dark:bg-gray-800',
                                confirmButton: 'rounded-xl px-10 py-3 font-black text-xs uppercase tracking-widest'
                            }
                        });
                    }
                },
                handleXmlChange(e, index) {
                    const file = e.target.files[0];
                    if (!file) return;

                    if (file.size > this.maxFileSize) {
                        Swal.fire({
                            title: '<span class="text-xl font-black uppercase tracking-tight text-red-600">Archivo Muy Grande</span>',
                            html: `<p class="text-sm font-medium text-gray-600 dark:text-gray-400 mt-2">El archivo <b>${file.name}</b> supera el límite permitido de <b>${(this.maxFileSize / (1024*1024)).toFixed(1)} MB</b> (Límite del servidor).</p>`,
                            icon: 'warning',
                            confirmButtonColor: '#ef4444',
                        });
                        e.target.value = '';
                        return;
                    }

                    const extension = file.name.split('.').pop().toLowerCase();
                    if (extension !== 'xml') {
                        Swal.fire({
                            title: '<span class="text-xl font-black uppercase tracking-tight text-red-600">Archivo Inválido</span>',
                            html: '<p class="text-sm font-medium text-gray-600 dark:text-gray-400 mt-2">Este campo solo acepta archivos <b>XML (CFDI)</b>.</p>',
                            icon: 'error',
                            confirmButtonText: 'ENTENDIDO',
                            confirmButtonColor: '#ef4444',
                            customClass: {
                                popup: 'rounded-[1.5rem] border-none shadow-2xl dark:bg-gray-800',
                                confirmButton: 'rounded-xl px-8 py-3 font-black text-xs uppercase tracking-widest'
                            }
                        });
                        e.target.value = '';
                        return;
                    }

                    this.items[index].fileName = 'Leyendo...';
                    this.validateFiles(index);
                    this.calculateTotalFileSize();
                },
                handlePdfChange(e, index) {
                    const file = e.target.files[0];
                    if (!file) return;

                    if (file.size > this.maxFileSize) {
                        Swal.fire({
                             title: '<span class="text-xl font-black uppercase tracking-tight text-red-600">Archivo Muy Grande</span>',
                             html: `<p class="text-sm font-medium text-gray-600 dark:text-gray-400 mt-2">El archivo <b>${file.name}</b> supera el límite permitido de <b>10MB</b>.</p>`,
                             icon: 'warning',
                             confirmButtonColor: '#ef4444',
                        });
                        e.target.value = '';
                        return;
                    }

                    const extension = file.name.split('.').pop().toLowerCase();
                    const allowedExtensions = this.hasInvoice ? ['pdf'] : ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'jfif', 'txt'];
                    
                    if (!allowedExtensions.includes(extension)) {
                        Swal.fire({
                            title: '<span class="text-xl font-black uppercase tracking-tight text-red-600">Archivo Inválido</span>',
                            html: `<p class="text-sm font-medium text-gray-600 dark:text-gray-400 mt-2">Este campo solo acepta archivos <b>${allowedExtensions.map(e => e.toUpperCase()).join(', ')}</b>.</p>`,
                            icon: 'error',
                            confirmButtonText: 'ENTENDIDO',
                            confirmButtonColor: '#ef4444',
                            customClass: {
                                popup: 'rounded-[1.5rem] border-none shadow-2xl dark:bg-gray-800',
                                confirmButton: 'rounded-xl px-8 py-3 font-black text-xs uppercase tracking-widest'
                            }
                        });
                        e.target.value = '';
                        return;
                    }

                    if (this.hasInvoice) {
                        this.validateFiles(index);
                    }

                    this.calculateTotalFileSize();
                },
                handleTicketChange(e, index) {
                    const file = e.target.files[0];
                    if (!file) return;

                    if (file.size > this.maxFileSize) {
                        Swal.fire({
                             title: '<span class="text-xl font-black uppercase tracking-tight text-red-600">Archivo Muy Grande</span>',
                             html: `<p class="text-sm font-medium text-gray-600 dark:text-gray-400 mt-2">El archivo <b>${file.name}</b> supera el límite permitido de <b>10MB</b>.</p>`,
                             icon: 'warning',
                             confirmButtonColor: '#ef4444',
                        });
                        e.target.value = '';
                        return;
                    }

                    const extension = file.name.split('.').pop().toLowerCase();
                    const allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'jfif', 'txt'];
                    
                    if (!allowedExtensions.includes(extension)) {
                        Swal.fire({
                            title: '<span class="text-xl font-black uppercase tracking-tight text-red-600">Archivo Inválido</span>',
                            html: `<p class="text-sm font-medium text-gray-600 dark:text-gray-400 mt-2">Este campo solo acepta archivos <b>${allowedExtensions.map(e => e.toUpperCase()).join(', ')}</b>.</p>`,
                            icon: 'error',
                            confirmButtonText: 'ENTENDIDO',
                            confirmButtonColor: '#ef4444',
                            customClass: {
                                popup: 'rounded-[1.5rem] border-none shadow-2xl dark:bg-gray-800',
                                confirmButton: 'rounded-xl px-8 py-3 font-black text-xs uppercase tracking-widest'
                            }
                        });
                        e.target.value = '';
                        return;
                    }

                    if (typeof index !== 'undefined') {
                        this.items[index].ticketName = file.name;
                    } else {
                        // For flat viaje type ticket
                        const dummyItem = { ticketName: file.name }; 
                        // It updates the visual if there was one for viaje, actually viaje uses global or just relies on the input showing the name. 
                        // We will rely on native input for viaje, but still trigger auto-save.
                    }

                    this.calculateTotalFileSize();
                    this.saveDraft(true);
                },
                calculateTotalFileSize() {
                    let total = 0;
                    const fileInputs = document.querySelectorAll('input[type="file"]');
                    fileInputs.forEach(input => {
                        if (input.files) {
                            for (let j = 0; j < input.files.length; j++) {
                                total += input.files[j].size;
                            }
                        }
                    });
                    this.currentTotalSize = total;
                },
                validateFiles(index) {
                    const item = this.items[index];
                    const xmlInput = document.querySelector(`input[name="items[${index}][xml_file]"]`);
                    const pdfInput = document.querySelector(`input[name="items[${index}][pdf_file]"]`);
                    
                    // Allow validation if we have EITHER a new file OR a draft file for XML
                    const hasXml = (xmlInput && xmlInput.files[0]) || (item.draftId && item.xmlParsed);
                    if (!hasXml) return;

                    // PDF is optional for parsing but data will be cleaner with it.
                    const hasPdf = (pdfInput && pdfInput.files[0]) || (item.draftId && item.pdfName);

                    const formData = new FormData();
                    if (xmlInput && xmlInput.files[0]) {
                        formData.append('xml_file', xmlInput.files[0]);
                    }
                    if (pdfInput && pdfInput.files[0]) {
                        formData.append('pdf_file', pdfInput.files[0]);
                    }
                    if (item.draftId) {
                        formData.append('draft_id', item.draftId);
                    }
                    formData.append('_token', '{{ csrf_token() }}');

                    fetch('{{ route("reimbursements.parse") }}', { 
                        method: 'POST', 
                        body: formData, 
                        headers: { 'X-Requested-With': 'XMLHttpRequest' } 
                    })
                    .then(r => r.json().then(data => ({ status: r.status, ok: r.ok, data })))
                    .then(({ status, ok, data: d }) => {
                        if (!ok || d.error) { 
                            if (d.error === 'duplicate_cfdi') {
                                let statusClasses = 'bg-gray-100 text-gray-800';
                                let statusLabel = d.status.toUpperCase().replace('_', ' ');
                                
                                if (d.status === 'requiere_correccion') {
                                    statusClasses = 'bg-orange-100 text-orange-800';
                                    statusLabel = 'REQUIERE CORRECCIÓN';
                                } else if (d.status === 'rechazado') {
                                    statusClasses = 'bg-red-100 text-red-800';
                                    statusLabel = 'RECHAZADO';
                                } else if (d.status === 'aprobado') {
                                    statusClasses = 'bg-green-100 text-green-800';
                                    statusLabel = 'PAGADO';
                                } else if (d.status === 'pendiente') {
                                    statusClasses = 'bg-yellow-100 text-yellow-800';
                                    statusLabel = 'PENDIENTE';
                                }

                                Swal.fire({
                                    title: '<span class="text-xl font-black uppercase tracking-tight text-red-600">Comprobante Duplicado</span>',
                                    html: `
                                        <div class="mt-4 p-6 bg-gray-50 dark:bg-gray-700/50 rounded-2xl border border-gray-100 dark:border-gray-600 text-left">
                                            <div class="mb-4">
                                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Este CFDI : UUID</p>
                                                <p class="text-xs font-mono font-bold text-gray-700 dark:text-gray-200 break-all leading-relaxed">${d.uuid}</p>
                                            </div>
                                            <div class="mb-4">
                                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Folio Interno</p>
                                                <p class="text-sm font-bold text-gray-900 dark:text-white">${d.folio || 'Sin Folio'}</p>
                                            </div>
                                            <div>
                                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Estatus Actual</p>
                                                <span class="inline-flex px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-tight ${statusClasses}">
                                                    ${statusLabel}
                                                </span>
                                            </div>
                                        </div>
                                        <p class="text-xs text-gray-500 mt-4 px-2">Este comprobante ya existe en el sistema y no puede ser registrado nuevamente.</p>
                                    `,
                                    icon: 'error',
                                    confirmButtonText: 'ENTENDIDO',
                                    confirmButtonColor: '#ef4444',
                                    customClass: {
                                        popup: 'rounded-[2rem] border-none shadow-2xl dark:bg-gray-800',
                                        confirmButton: 'rounded-xl px-12 py-3 font-black text-xs uppercase tracking-widest'
                                    }
                                });
                            } else {
                                const errorMsg = d.error || d.message || (d.errors ? Object.values(d.errors).flat().join(' ') : 'Error de procesamiento');
                                Swal.fire({
                                    title: '<span class="text-xl font-black uppercase tracking-tight text-red-600">Error de Procesamiento</span>',
                                    html: `<p class="text-sm font-medium text-gray-600 dark:text-gray-400 mt-2">${errorMsg}</p>`,
                                    icon: 'error',
                                    confirmButtonText: 'CORREGIR ARCHIVO',
                                    confirmButtonColor: '#ef4444',
                                    customClass: {
                                        popup: 'rounded-[1.5rem] border-none shadow-2xl dark:bg-gray-800',
                                        confirmButton: 'rounded-xl px-8 py-3 font-black text-xs uppercase tracking-widest'
                                    }
                                });
                            }
                            item.xmlParsed = false; 
                            xmlInput.value = ''; 
                            item.fileName = ''; 
                            item.data = { uuid: '', folio: '', rfc_emisor: '', nombre_emisor: '', rfc_receptor: '', nombre_receptor: '', fecha: '', moneda: 'MXN', subtotal: 0, total: 0, metodo_pago: '', forma_pago: '', uso_cfdi: '', lugar_expedicion: '', regimen_fiscal_emisor: '' };
                        }
                        else { 
                            // Check for duplicates in current session list
                            const isDuplicateSession = this.items.some((it, idx) => idx !== index && it.data.uuid === d.uuid);
                            if (isDuplicateSession) {
                                Swal.fire({
                                    title: '<span class="text-xl font-black uppercase tracking-tight text-orange-600">Factura Duplicada</span>',
                                    html: '<p class="text-sm font-medium text-gray-600 dark:text-gray-400 mt-2">Este CFDI ya ha sido agregado en otro renglón de este mismo registro.</p>',
                                    icon: 'warning',
                                    confirmButtonText: 'ENTENDIDO',
                                    confirmButtonColor: '#f59e0b',
                                    customClass: {
                                        popup: 'rounded-[1.5rem] border-none shadow-2xl dark:bg-gray-800',
                                        confirmButton: 'rounded-xl px-8 py-3 font-black text-xs uppercase tracking-widest'
                                    }
                                });
                                xmlInput.value = '';
                                item.xmlParsed = false;
                                item.fileName = '';
                                item.data = { uuid: '', folio: '', rfc_emisor: '', nombre_emisor: '', rfc_receptor: '', nombre_receptor: '', fecha: '', moneda: 'MXN', subtotal: 0, total: 0, metodo_pago: '', forma_pago: '', uso_cfdi: '', lugar_expedicion: '', regimen_fiscal_emisor: '' };
                                return;
                            }

                            item.xmlParsed = true; 
                            item.data = d; 
                            item.fileName = 'Factura: ' + (d.folio || (d.uuid ? d.uuid.substring(0, 8) : '???'));
                            
                            if (d.pdf_validation && !d.pdf_validation.uuid_match && !item.noPdf) {
                                Swal.fire({
                                    icon: 'warning',
                                    title: '<span class="text-xl font-black uppercase tracking-tight text-orange-600">PDF no coincide</span>',
                                    html: '<p class="text-sm font-medium text-gray-600 dark:text-gray-400 mt-2">El <b>UUID</b> del XML no se encuentra dentro del contenido de texto del archivo <b>PDF</b> especificado. Por favor verifica que ambos archivos correspondan a la misma factura.</p>',
                                    confirmButtonText: 'VERIFICAR ARCHIVOS',
                                    confirmButtonColor: '#f59e0b',
                                    customClass: {
                                        popup: 'rounded-[1.5rem] border-none shadow-2xl dark:bg-gray-800',
                                        confirmButton: 'rounded-xl px-8 py-3 font-black text-xs uppercase tracking-widest'
                                    }
                                });
                            }
                        }
                    })
                    .catch(e => {
                        console.error(e);
                        item.fileName = '';
                        Swal.fire({
                            icon: 'error',
                            title: 'Error de Red',
                            text: 'No se pudo comunicar con el servidor.'
                        });
                    });
                },
                calculateTotal() { 
                    return this.items.reduce((acc, i) => {
                        const val = parseFloat(i.data.total) || 0;
                        return acc + val;
                    }, 0);
                },
                handleSubmit(e) { 
                    const form = e.target;

                    // New: Validate total size before anything else
                    if (this.currentTotalSize > this.maxTotalSize) {
                        e.preventDefault();
                        Swal.fire({
                            title: '<span class="text-xl font-black uppercase tracking-tight text-red-600">Límite de Peso Excedido</span>',
                            html: `<p class="text-sm font-medium text-gray-600 dark:text-gray-400 mt-2">El peso total de los archivos subidos es de <b>${(this.currentTotalSize / (1024*1024)).toFixed(2)} MB</b>, lo que supera el límite de <b>64 MB</b>. Por favor, realiza la carga en varias sesiones.</p>`,
                            icon: 'error',
                            confirmButtonText: 'ENTENDIDO',
                            confirmButtonColor: '#ef4444',
                            customClass: {
                                popup: 'rounded-[1.5rem] border-none shadow-2xl dark:bg-gray-800',
                                confirmButton: 'rounded-xl px-12 py-3 font-black text-xs uppercase tracking-widest'
                            }
                        });
                        return;
                    }

                    // Manual Validation Check
                    if (!form.checkValidity()) {
                        e.preventDefault();
                        
                        // Find the first invalid field to display its label in the error message
                        const firstInvalid = form.querySelector(':invalid');
                        let labelText = "Campos Obligatorios";
                        let customError = "";
                        
                        // New: Check specifically for XML extension if it's an invoice upload
                        if (this.hasInvoice) {
                            const xmlInputs = form.querySelectorAll('input[type="file"][accept=".xml"]');
                            xmlInputs.forEach(input => {
                                if (input.files.length > 0) {
                                    const ext = input.files[0].name.split('.').pop().toLowerCase();
                                    if (ext !== 'xml') {
                                        customError = "Los archivos de factura deben ser de tipo .XML";
                                    }
                                }
                            });
                        }

                        if (firstInvalid && !customError) {
                            // Try to find the label associated with the invalid field
                            const container = firstInvalid.closest('div');
                            const label = container ? container.querySelector('label') : null;
                            if (label) {
                                labelText = label.innerText.replace('*', '').trim();
                            }
                        }

                        Swal.fire({
                            title: '<span class="text-xl font-black uppercase tracking-tight text-red-600">Formulario Incompleto</span>',
                            html: `<p class="text-sm font-medium text-gray-600 dark:text-gray-400 mt-2">Por favor completa todos los campos marcados con (*) antes de continuar.<br><br><span class="text-xs text-red-500 font-bold uppercase italic">FALTA: ${customError || labelText}</span></p>`,
                            icon: 'warning',
                            confirmButtonText: 'REVISAR',
                            confirmButtonColor: '#4f46e5',
                            customClass: {
                                popup: 'rounded-[1.5rem] border-none shadow-2xl dark:bg-gray-800',
                                confirmButton: 'rounded-xl px-12 py-3 font-black text-xs uppercase tracking-widest'
                            }
                        });
                        return;
                    }

                    if (!this.hasInvoice) {
                        const hasFutureDate = this.items.some(i => i.data.fecha && new Date(i.data.fecha) > new Date());
                        const hasInvalidSubtotal = this.items.some(i => parseFloat(i.data.subtotal) > parseFloat(i.data.total));
                        
                        if (hasFutureDate || hasInvalidSubtotal) {
                            e.preventDefault();
                            Swal.fire({
                                title: '<span class="text-xl font-black uppercase tracking-tight text-red-600">Errores de Validación</span>',
                                html: `<p class="text-sm font-medium text-gray-600 dark:text-gray-400 mt-2">${hasFutureDate ? 'No se permiten fechas futuras.' : ''} ${hasInvalidSubtotal ? 'El subtotal no puede ser mayor al total.' : ''}</p>`,
                                icon: 'error',
                                confirmButtonText: 'CORREGIR',
                                confirmButtonColor: '#ef4444',
                                customClass: {
                                    popup: 'rounded-[1.5rem] border-none shadow-2xl dark:bg-gray-800',
                                    confirmButton: 'rounded-xl px-8 py-3 font-black text-xs uppercase tracking-widest'
                                }
                            });
                            return;
                        }
                    }
                    document.getElementById('loading-overlay').classList.remove('hidden'); 
                }
            }
        }
    </script>



        <script>
            document.addEventListener('DOMContentLoaded', function () {
                @if (session('success'))
                    Swal.fire({
                        title: '<span class="text-xl font-black uppercase tracking-tight text-green-600">Registro Exitoso</span>',
                        html: '<p class="text-sm font-medium text-gray-600 dark:text-gray-400 mt-2">{{ session('success') }}</p>',
                        icon: 'success',
                        confirmButtonText: 'ENTENDIDO',
                        confirmButtonColor: '#10b981',
                        customClass: {
                            popup: 'rounded-[1.5rem] border-none shadow-2xl dark:bg-gray-800',
                            confirmButton: 'rounded-xl px-12 py-3 font-black text-xs uppercase tracking-widest'
                        }
                    });
                @endif

                @if (session('error'))
                    Swal.fire({
                        title: '<span class="text-xl font-black uppercase tracking-tight text-red-600">Error en el Proceso</span>',
                        html: '<p class="text-sm font-medium text-gray-600 dark:text-gray-400 mt-2">{{ session('error') }}</p>',
                        icon: 'error',
                        confirmButtonText: 'REVISAR',
                        confirmButtonColor: '#ef4444',
                        customClass: {
                            popup: 'rounded-[1.5rem] border-none shadow-2xl dark:bg-gray-800',
                            confirmButton: 'rounded-xl px-12 py-3 font-black text-xs uppercase tracking-widest'
                        }
                    });
                @endif

                @if ($errors->any())
                    Swal.fire({
                        title: '<span class="text-xl font-black uppercase tracking-tight text-red-600">Errores de Validación</span>',
                        html: `<div class="mt-4 text-left">
                                <ul class="space-y-2">
                                    @foreach ($errors->all() as $error)
                                        <li class="flex items-center text-xs font-bold text-red-700 dark:text-red-400 uppercase italic">
                                            <span class="w-1.5 h-1.5 bg-red-400 rounded-full mr-2"></span>
                                            {{ $error }}
                                        </li>
                                    @endforeach
                                </ul>
                               </div>`,
                        icon: 'warning',
                        confirmButtonText: 'CORREGIR',
                        confirmButtonColor: '#ef4444',
                        customClass: {
                            popup: 'rounded-[1.5rem] border-none shadow-2xl dark:bg-gray-800',
                            confirmButton: 'rounded-xl px-12 py-3 font-black text-xs uppercase tracking-widest'
                        }
                    });
                @endif
            });
        </script>
    @endpush
</x-app-layout>
