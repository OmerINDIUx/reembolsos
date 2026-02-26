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

            @if ($errors->any())
                <div class="mb-10 p-6 bg-red-50 dark:bg-red-900/20 border-2 border-red-100 dark:border-red-900/30 rounded-[2rem] animate-shake">
                    <div class="flex items-center space-x-4 mb-4">
                        <div class="w-10 h-10 bg-red-600 rounded-xl flex items-center justify-center text-white shadow-lg">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        </div>
                        <h3 class="text-xl font-black text-red-900 dark:text-red-400 uppercase tracking-tight">Errores en el registro</h3>
                    </div>
                    <ul class="space-y-2">
                        @foreach ($errors->all() as $error)
                            <li class="flex items-center text-sm font-bold text-red-700 dark:text-red-300 uppercase italic">
                                <span class="w-2 h-2 bg-red-400 rounded-full mr-3"></span>
                                {{ $error }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Global Form -->
            <form :action="type === 'viaje' ? '{{ route('reimbursements.store') }}' : '{{ route('reimbursements.bulk_store') }}'" method="POST" enctype="multipart/form-data" x-on:submit="handleSubmit">
                @csrf
                <input type="hidden" name="type" value="{{ $type }}">
                <input type="hidden" name="week" value="{{ $currentWeek }}">
                <input type="hidden" name="has_invoice" value="{{ $hasInvoice ? 1 : 0 }}">

                <!-- Header Info Card -->
                <div class="bg-white dark:bg-gray-800 shadow-xl rounded-3xl mb-10 overflow-hidden border border-gray-100 dark:border-gray-700">
                    <div class="p-8 md:p-10">
                        <div class="flex items-center mb-8">
                            <div class="w-12 h-12 bg-indigo-600 rounded-2xl flex items-center justify-center text-white shadow-lg shadow-indigo-500/30 mr-5">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                            </div>
                            <div>
                                <h3 class="text-2xl font-black text-gray-900 dark:text-white uppercase tracking-tight">Paso 1</h3>
                                <p class="text-sm text-gray-500 font-medium">Define el Centro de Costos al que se cargarán todos los comprobantes de esta sesión.</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div>
                                <label class="block text-xs font-black text-gray-400 uppercase tracking-[0.2em] mb-3">Centro de Costos *</label>
                                <select name="cost_center_id" class="w-full border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-2xl shadow-sm focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all py-4 px-5" required>
                                    <option value="">Selecciona el Centro de Costos...</option>
                                    @foreach($costCenters as $center)
                                        <option value="{{ $center->id }}">{{ $center->name }}</option>
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

                <!-- REPEATER (FOR REEMBOLSO, FONDO FIJO, COMIDA) -->
                <div x-show="type !== 'viaje'" class="space-y-16">
                    <template x-for="(item, index) in items" :key="item.id">
                        <div class="bg-white dark:bg-gray-800 shadow-2xl rounded-[2.5rem] border border-gray-100 dark:border-gray-700 overflow-hidden animate-fadeIn relative">
                            
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
                                                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-3">Archivo XML (CFDI) *</label>
                                                <input type="file" :name="'items['+index+'][xml_file]'" accept=".xml" class="block w-full text-xs text-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl cursor-pointer bg-gray-50 dark:bg-gray-900 dark:text-gray-400 focus:outline-none p-3" :required="hasInvoice" x-on:change="handleXmlChange($event, index)">
                                            </div>

                                             <template x-if="hasInvoice">
                                                <div :class="item.xmlParsed ? 'opacity-100' : 'opacity-40 pointer-events-none transition-opacity'">
                                                    <div class="flex items-center justify-between mb-3">
                                                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300">Archivo PDF</label>
                                                        <label class="flex items-center cursor-pointer">
                                                            <input type="checkbox" :name="'items['+index+'][no_pdf]'" class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500" x-on:change="item.noPdf = $event.target.checked">
                                                            <span class="ml-2 text-[10px] font-bold text-gray-500 uppercase">Sin PDF</span>
                                                        </label>
                                                    </div>
                                                    <input type="file" :name="'items['+index+'][pdf_file]'" accept=".pdf" class="block w-full text-xs text-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl cursor-pointer bg-gray-50 dark:bg-gray-900 dark:text-gray-400 focus:outline-none p-3" :disabled="item.noPdf" :required="!item.noPdf && item.xmlParsed && hasInvoice" x-on:change="handlePdfChange($event, index)">
                                                    
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
                                                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-3">Archivo de Respaldado (PDF/Imagen) *</label>
                                                    <input type="file" :name="'items['+index+'][pdf_file]'" accept=".pdf,image/*,.txt" class="block w-full text-xs text-gray-900 border border-gray-100 dark:border-gray-700 rounded-xl cursor-pointer bg-gray-50 dark:bg-gray-900 dark:text-gray-400 focus:outline-none p-3" :required="!hasInvoice">
                                                </div>
                                            </div>
                                        </template>

                                        <!-- Clasificación -->
                                        <div class="space-y-6">
                                            <h4 class="text-[10px] font-black uppercase text-gray-400 tracking-[0.2em] border-b pb-3">Clasificación</h4>
                                            <div>
                                                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-3">Categoría *</label>
                                                <select :name="'items['+index+'][category]'" class="w-full border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-xl shadow-sm focus:ring-indigo-500 text-sm py-3" required>
                                                    <option value="">Selecciona...</option>
                                                    @foreach($categories as $cat)
                                                        <option value="{{ $cat }}">{{ $cat }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-3">Justificación *</label>
                                                <textarea :name="'items['+index+'][observaciones]'" rows="4" class="w-full border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-xl shadow-sm text-sm" required placeholder="Motivo del gasto..."></textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Right Column: ALL DATA FIELDS -->
                                    <div class="lg:col-span-8 space-y-8">
                                        <h4 class="text-[10px] font-black uppercase text-gray-400 tracking-[0.2em] border-b pb-3" x-text="hasInvoice ? 'Información del Sistema (CFDI)' : 'Información Manual'"></h4>
                                        
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-10 gap-y-6">
                                            <div class="col-span-1 md:col-span-2" x-show="hasInvoice">
                                                <label class="block text-[10px] font-bold text-gray-400 uppercase mb-2">Folio Fiscal (UUID)</label>
                                                <input type="text" :value="item.data.uuid" class="w-full bg-gray-50 dark:bg-gray-900/50 border-gray-200 dark:border-gray-700 rounded-xl text-xs font-mono text-gray-600 dark:text-gray-400" readonly placeholder="Esperando XML...">
                                            </div>
                                            
                                            <div x-show="hasInvoice">
                                                <label class="block text-[10px] font-bold text-gray-400 uppercase mb-2">RFC Emisor</label>
                                                <input type="text" :value="item.data.rfc_emisor" class="w-full bg-gray-50 dark:bg-gray-900/50 border-gray-200 dark:border-gray-700 rounded-xl text-xs" readonly>
                                            </div>
                                            <div x-show="hasInvoice">
                                                <label class="block text-[10px] font-bold text-gray-400 uppercase mb-2">Folio Interno (XML)</label>
                                                <input type="text" :value="item.data.folio" class="w-full bg-gray-50 dark:bg-gray-900/50 border-gray-200 dark:border-gray-700 rounded-xl text-xs" readonly>
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
                                                                Confirmo que esta es la empresa en la que estoy dado de alta como colaborador(a). *
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
                                                <template x-if="!hasInvoice && parseFloat(item.data.subtotal) > parseFloat(item.data.total)">
                                                    <p class="mt-1 text-[9px] font-bold text-red-600 uppercase">El subtotal no puede ser mayor al total</p>
                                                </template>
                                            </div>
                                            <div>
                                                <label class="block text-[10px] font-black text-white bg-indigo-600 rounded-t-lg px-2 py-1 uppercase mb-0 tracking-widest inline-block">Total del Gasto *</label>
                                                <div class="relative">
                                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-lg font-black text-indigo-300">$</span>
                                                    <input type="number" step="0.01" :name="'items['+index+'][total]'" x-model="item.data.total" class="w-full bg-indigo-50 dark:bg-indigo-900/50 border-indigo-200 dark:border-indigo-800 rounded-xl rounded-tl-none text-xl font-black text-indigo-700 dark:text-indigo-300 py-3 pl-10" :readonly="hasInvoice" :required="!hasInvoice">
                                                </div>
                                                
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
                                                            <textarea :name="'items['+index+'][attendees_names]'" rows="3" class="w-full bg-white dark:bg-gray-900 border-2 border-orange-50 dark:border-orange-900/20 rounded-2xl p-6 text-sm font-medium focus:ring-4 focus:ring-orange-500/10 focus:border-orange-500 transition-all placeholder-gray-300" placeholder="Escribe los nombres de las personas que asistieron..."></textarea>
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

                    <div class="flex flex-col items-center">
                        <button type="button" x-on:click="addItem()" class="group flex items-center justify-center p-1 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-[2rem] hover:shadow-2xl transition-all transform hover:scale-105">
                            <div class="bg-white dark:bg-gray-800 px-12 py-6 rounded-[1.9rem] flex items-center">
                                <div class="w-10 h-10 bg-indigo-600 text-white rounded-full flex items-center justify-center mr-4 group-hover:rotate-180 transition-transform duration-500">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                </div>
                                <span class="text-xl font-black text-gray-900 dark:text-white uppercase tracking-tighter" x-text="hasInvoice ? 'AGREGAR FACTURA' : 'AGREGAR COMPROBANTE'"></span>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- VIAJE FORM -->
                <div x-show="type === 'viaje'" class="animate-fadeIn">
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
                                <input type="text" name="title" class="w-full border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-2xl shadow-sm focus:ring-4 focus:ring-purple-500/10 focus:border-purple-500 transition-all py-4 px-5" :required="type === 'viaje'">
                            </div>

                            <div>
                                <label class="block text-xs font-black text-gray-400 uppercase tracking-[0.2em] mb-3">Tipo de Viaje *</label>
                                <select name="trip_type" x-model="tripType" class="w-full border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-2xl shadow-sm focus:ring-4 focus:ring-purple-500/10 focus:border-purple-500 transition-all py-4 px-5" :required="type === 'viaje'">
                                    <option value="nacional">Nacional</option>
                                    <option value="internacional">Internacional</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-black text-gray-400 uppercase tracking-[0.2em] mb-3">Destino *</label>
                                <input type="text" name="trip_destination" class="w-full border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-2xl shadow-sm focus:ring-4 focus:ring-purple-500/10 focus:border-purple-500 transition-all py-4 px-5" :required="type === 'viaje'" placeholder="Ciudad, Estado/Pais">
                            </div>

                            <div>
                                <label class="block text-xs font-black text-gray-400 uppercase tracking-[0.2em] mb-3">Noches *</label>
                                <input type="number" name="trip_nights" min="0" value="0" class="w-full border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-2xl shadow-sm focus:ring-4 focus:ring-purple-500/10 focus:border-purple-500 transition-all py-4 px-5" :required="type === 'viaje'">
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-black text-gray-400 uppercase tracking-[0.2em] mb-3">Fecha Inicio *</label>
                                    <input type="date" name="trip_start_date" class="w-full border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-2xl shadow-sm focus:ring-4 focus:ring-purple-500/10 focus:border-purple-500 transition-all py-4 px-5" :required="type === 'viaje'">
                                </div>
                                <div>
                                    <label class="block text-xs font-black text-gray-400 uppercase tracking-[0.2em] mb-3">Fecha Fin *</label>
                                    <input type="date" name="trip_end_date" class="w-full border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-2xl shadow-sm focus:ring-4 focus:ring-purple-500/10 focus:border-purple-500 transition-all py-4 px-5" :required="type === 'viaje'">
                                </div>
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-xs font-black text-gray-400 uppercase tracking-[0.2em] mb-3">Observaciones / Justificación</label>
                                <textarea name="observaciones" rows="3" class="w-full border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-2xl shadow-sm focus:ring-4 focus:ring-purple-500/10 focus:border-purple-500 transition-all py-4 px-5" placeholder="Describe el motivo del viaje..."></textarea>
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
                        <div x-show="type !== 'viaje' && items.length > 0" class="text-right hidden sm:block">
                            <span class="block text-[10px] font-black text-indigo-400 uppercase mb-1">Total Acumulado</span>
                            <span class="block text-4xl font-black text-gray-900 dark:text-white" x-text="'$ ' + calculateTotal().toLocaleString('es-MX', {minimumFractionDigits: 2})"></span>
                        </div>
                        
                        <button type="submit" 
                            :disabled="!hasInvoice && (items.some(i => i.data.total > 2000) || items.some(i => parseFloat(i.data.subtotal) > parseFloat(i.data.total)))"
                            :class="!hasInvoice && (items.some(i => i.data.total > 2000) || items.some(i => parseFloat(i.data.subtotal) > parseFloat(i.data.total))) ? 'opacity-50 cursor-not-allowed grayscale' : ''"
                            class="group inline-flex items-center px-16 py-8 bg-indigo-600 text-white rounded-[2rem] font-black text-2xl uppercase italic hover:bg-indigo-700 shadow-2xl transition-all transform hover:scale-105">
                            <span>REGISTRAR TODO</span>
                            <svg class="w-8 h-8 ml-4 group-hover:translate-x-2 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7-7 7M5 5l7 7-7 7"></path></svg>
                        </button>
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
                tripType: 'nacional',
                items: [],
                hasInvoice: {{ $hasInvoice ? 'true' : 'false' }},
                init() { if (this.type !== 'viaje') this.addItem(); },
                addItem() {
                    this.items.push({
                        id: Date.now() + Math.random(),
                        fileName: '',
                        xmlParsed: false,
                        noPdf: false,
                        manualData: { nombre_emisor: '', fecha: '', subtotal: 0, total: 0 },
                        data: { uuid: '', folio: '', rfc_emisor: '', nombre_emisor: '', rfc_receptor: '', nombre_receptor: '', fecha: '', moneda: 'MXN', subtotal: 0, total: 0 }
                    });
                },
                removeItem(index) { 
                    if (this.items.length > 1) {
                        this.items.splice(index, 1); 
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
                },
                handlePdfChange(e, index) {
                    const file = e.target.files[0];
                    if (!file) return;

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
                },
                validateFiles(index) {
                    const item = this.items[index];
                    const xmlInput = document.querySelector(`input[name="items[${index}][xml_file]"]`);
                    const pdfInput = document.querySelector(`input[name="items[${index}][pdf_file]"]`);
                    
                    if (!xmlInput || !xmlInput.files[0]) return;

                    const formData = new FormData();
                    formData.append('xml_file', xmlInput.files[0]);
                    if (pdfInput && pdfInput.files[0]) {
                        formData.append('pdf_file', pdfInput.files[0]);
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
                            item.data = { uuid: '', folio: '', rfc_emisor: '', nombre_emisor: '', rfc_receptor: '', nombre_receptor: '', fecha: '', moneda: 'MXN', subtotal: 0, total: 0 };
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
                                item.data = { uuid: '', folio: '', rfc_emisor: '', nombre_emisor: '', rfc_receptor: '', nombre_receptor: '', fecha: '', moneda: 'MXN', subtotal: 0, total: 0 };
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


    @endpush
</x-app-layout>
