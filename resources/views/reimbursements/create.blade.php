<x-app-layout>
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

        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-8 flex justify-between items-center">
                <a href="{{ route('reimbursements.create') }}" class="inline-flex items-center text-sm font-bold text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    CAMBIAR TIPO DE SOLICITUD
                </a>
            </div>

            <!-- Global Form -->
            <form :action="type === 'viaje' ? '{{ route('reimbursements.store') }}' : '{{ route('reimbursements.bulk_store') }}'" method="POST" enctype="multipart/form-data" x-on:submit="handleSubmit">
                @csrf
                <input type="hidden" name="type" value="{{ $type }}">
                <input type="hidden" name="week" value="{{ $currentWeek }}">

                <!-- Header Info Card -->
                <div class="bg-white dark:bg-gray-800 shadow-xl rounded-3xl mb-10 overflow-hidden border border-gray-100 dark:border-gray-700">
                    <div class="p-8 md:p-10">
                        <div class="flex items-center mb-8">
                            <div class="w-12 h-12 bg-indigo-600 rounded-2xl flex items-center justify-center text-white shadow-lg shadow-indigo-500/30 mr-5">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                            </div>
                            <div>
                                <h3 class="text-2xl font-black text-gray-900 dark:text-white uppercase tracking-tight">Configuración General</h3>
                                <p class="text-sm text-gray-500 font-medium">Define el centro de costos para todos los comprobantes de esta sesión.</p>
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
                                    <h3 class="text-xl font-bold text-gray-800 dark:text-white" x-text="item.fileName || 'Pendiente de Carga'"></h3>
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
                                        <div class="space-y-6">
                                            <h4 class="text-[10px] font-black uppercase text-gray-400 tracking-[0.2em] border-b pb-3">Archivos Fuente</h4>
                                            
                                            <div>
                                                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-3">Archivo XML (CFDI) *</label>
                                                <input type="file" :name="'items['+index+'][xml_file]'" accept=".xml" class="block w-full text-xs text-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl cursor-pointer bg-gray-50 dark:bg-gray-900 dark:text-gray-400 focus:outline-none p-3" required x-on:change="handleXmlChange($event, index)">
                                            </div>

                                            <div :class="item.xmlParsed ? 'opacity-100' : 'opacity-40 pointer-events-none transition-opacity'">
                                                <div class="flex items-center justify-between mb-3">
                                                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300">Archivo PDF</label>
                                                    <label class="flex items-center cursor-pointer">
                                                        <input type="checkbox" :name="'items['+index+'][no_pdf]'" class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500" x-on:change="item.noPdf = $event.target.checked">
                                                        <span class="ml-2 text-[10px] font-bold text-gray-500 uppercase">Sin PDF</span>
                                                    </label>
                                                </div>
                                                <input type="file" :name="'items['+index+'][pdf_file]'" accept=".pdf" class="block w-full text-xs text-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl cursor-pointer bg-gray-50 dark:bg-gray-900 dark:text-gray-400 focus:outline-none p-3" :disabled="item.noPdf" :required="!item.noPdf && item.xmlParsed" x-on:change="handlePdfChange($event, index)">
                                                
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
                                        </div>

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
                                        <h4 class="text-[10px] font-black uppercase text-gray-400 tracking-[0.2em] border-b pb-3">Información del Sistema (CFDI)</h4>
                                        
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-10 gap-y-6">
                                            <div class="col-span-1 md:col-span-2">
                                                <label class="block text-[10px] font-bold text-gray-400 uppercase mb-2">Folio Fiscal (UUID)</label>
                                                <input type="text" :value="item.data.uuid" class="w-full bg-gray-50 dark:bg-gray-900/50 border-gray-200 dark:border-gray-700 rounded-xl text-xs font-mono text-gray-600 dark:text-gray-400" readonly placeholder="Esperando XML...">
                                            </div>
                                            
                                            <div>
                                                <label class="block text-[10px] font-bold text-gray-400 uppercase mb-2">RFC Emisor</label>
                                                <input type="text" :value="item.data.rfc_emisor" class="w-full bg-gray-50 dark:bg-gray-900/50 border-gray-200 dark:border-gray-700 rounded-xl text-xs" readonly>
                                            </div>
                                            <div>
                                                <label class="block text-[10px] font-bold text-gray-400 uppercase mb-2">Folio Interno (XML)</label>
                                                <input type="text" :value="item.data.folio" class="w-full bg-gray-50 dark:bg-gray-900/50 border-gray-200 dark:border-gray-700 rounded-xl text-xs" readonly>
                                            </div>

                                            <div>
                                                <label class="block text-[10px] font-bold text-gray-400 uppercase mb-2">Nombre Emisor</label>
                                                <input type="text" :value="item.data.nombre_emisor" class="w-full bg-gray-50 dark:bg-gray-900/50 border-gray-200 dark:border-gray-700 rounded-xl text-xs" readonly>
                                            </div>
                                            <div>
                                                <label class="block text-[10px] font-bold text-gray-400 uppercase mb-2">Fecha Emisión</label>
                                                <input type="text" :value="item.data.fecha" class="w-full bg-gray-50 dark:bg-gray-900/50 border-gray-200 dark:border-gray-700 rounded-xl text-xs" readonly>
                                            </div>

                                            <div class="border-t border-gray-50 dark:border-gray-700/50 pt-4 col-span-1 md:col-span-2"></div>

                                            <div>
                                                <label class="block text-[10px] font-bold text-gray-400 uppercase mb-2">RFC Receptor</label>
                                                <input type="text" :value="item.data.rfc_receptor" class="w-full bg-gray-50 dark:bg-gray-900/50 border-gray-200 dark:border-gray-700 rounded-xl text-xs" readonly>
                                            </div>
                                            <div>
                                                <label class="block text-[10px] font-bold text-gray-400 uppercase mb-2">Nombre Receptor</label>
                                                <input type="text" :value="item.data.nombre_receptor" class="w-full bg-gray-50 dark:bg-gray-900/50 border-gray-200 dark:border-gray-700 rounded-xl text-xs mb-3" readonly>
                                                <!-- RESTORED CONFIRMATION -->
                                                <div class="flex items-center">
                                                    <input type="checkbox" :name="'items['+index+'][confirm_company]'" class="w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500" required>
                                                    <label class="ml-2 text-[10px] font-black text-indigo-600 dark:text-indigo-400 uppercase italic">
                                                        Confirmo mi empresa *
                                                    </label>
                                                </div>
                                            </div>

                                            <div class="border-t border-gray-50 dark:border-gray-700/50 pt-4 col-span-1 md:col-span-2"></div>

                                            <div>
                                                <label class="block text-[10px] font-bold text-gray-400 uppercase mb-2 text-indigo-600">Subtotal</label>
                                                <input type="text" :value="item.data.subtotal ? ('$ ' + parseFloat(item.data.subtotal).toLocaleString('es-MX', {minimumFractionDigits: 2})) : '$ 0.00'" class="w-full bg-gray-50 dark:bg-gray-900/50 border-gray-200 dark:border-gray-700 rounded-xl text-sm font-bold text-indigo-600" readonly>
                                            </div>
                                            <div>
                                                <label class="block text-[10px] font-black text-white bg-indigo-600 rounded-t-lg px-2 py-1 uppercase mb-0 tracking-widest inline-block">Total del Gasto</label>
                                                <input type="text" :value="item.data.total ? ('$ ' + parseFloat(item.data.total).toLocaleString('es-MX', {minimumFractionDigits: 2}) + ' ' + item.data.moneda) : '$ 0.00 MXN'" class="w-full bg-indigo-50 dark:bg-indigo-900/50 border-indigo-200 dark:border-indigo-800 rounded-xl rounded-tl-none text-xl font-black text-indigo-700 dark:text-indigo-300 py-3" readonly>
                                            </div>
                                        </div>

                                        @if($type === 'comida')
                                            <div class="bg-orange-50 dark:bg-orange-900/20 p-8 rounded-3xl border border-orange-100 dark:border-orange-800 grid grid-cols-1 md:grid-cols-2 gap-8 mt-10 animate-slideDown">
                                                <h4 class="col-span-2 text-xs font-black text-orange-600 dark:text-orange-400 uppercase tracking-widest border-b border-orange-200 pb-2">Detalles Comida</h4>
                                                <div>
                                                    <label class="block text-sm font-bold text-orange-800 dark:text-orange-300 mb-2">Asistentes *</label>
                                                    <input type="number" :name="'items['+index+'][attendees_count]'" min="1" class="w-full border-orange-200 dark:border-orange-800 dark:bg-gray-900 rounded-xl" :required="type === 'comida'">
                                                </div>
                                                <div>
                                                    <label class="block text-sm font-bold text-orange-800 dark:text-orange-300 mb-2">Lugar *</label>
                                                    <input type="text" :name="'items['+index+'][location]'" class="w-full border-orange-200 dark:border-orange-800 dark:bg-gray-900 rounded-xl" :required="type === 'comida'" placeholder="Restaurante...">
                                                </div>
                                                <div class="col-span-2">
                                                    <label class="block text-sm font-bold text-orange-800 dark:text-orange-300 mb-2">Invitados (Nombres)</label>
                                                    <textarea :name="'items['+index+'][attendees_names]'" rows="2" class="w-full border-orange-200 dark:border-orange-800 dark:bg-gray-900 rounded-xl"></textarea>
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
                                <span class="text-xl font-black text-gray-900 dark:text-white uppercase tracking-tighter">AGREGAR FACTURA</span>
                            </div>
                        </button>
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
                        
                        <button type="submit" class="group inline-flex items-center px-16 py-8 bg-indigo-600 text-white rounded-[2rem] font-black text-2xl uppercase italic hover:bg-indigo-700 shadow-2xl transition-all transform hover:scale-105">
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
                items: [],
                init() { if (this.type !== 'viaje') this.addItem(); },
                addItem() {
                    this.items.push({
                        id: Date.now() + Math.random(),
                        fileName: '',
                        xmlParsed: false,
                        noPdf: false,
                        data: { uuid: '', folio: '', rfc_emisor: '', nombre_emisor: '', rfc_receptor: '', nombre_receptor: '', fecha: '', moneda: 'MXN', subtotal: 0, total: 0 }
                    });
                },
                removeItem(index) { if (this.items.length > 1) this.items.splice(index, 1); else Swal.fire('Atención', 'Mínimo un comprobante.', 'info'); },
                handleXmlChange(e, index) {
                    const file = e.target.files[0];
                    if (!file) return;
                    this.items[index].fileName = 'Leyendo...';
                    this.validateFiles(index);
                },
                handlePdfChange(e, index) {
                    const file = e.target.files[0];
                    if (!file) return;
                    this.validateFiles(index);
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
                    .then(r => r.json())
                    .then(d => {
                        if (d.error) { 
                            Swal.fire('Error', d.error, 'error'); 
                            item.xmlParsed = false; 
                            if (!d.error.includes('CFDI')) xmlInput.value = ''; 
                            item.fileName = ''; 
                        }
                        else { 
                            item.xmlParsed = true; 
                            item.data = d; 
                            item.fileName = 'Factura: ' + (d.folio || d.uuid.substring(0, 8));
                            
                            if (d.pdf_validation && !d.pdf_validation.uuid_match && !item.noPdf) {
                                Swal.fire({
                                    icon: 'warning',
                                    title: 'PDF no coincide',
                                    text: 'El UUID del XML no se encuentra en el archivo PDF seleccionado.',
                                    confirmButtonColor: '#4f46e5'
                                });
                            }
                        }
                    });
                },
                calculateTotal() { return this.items.reduce((acc, i) => acc + (parseFloat(i.data.total) || 0), 0); },
                handleSubmit() { document.getElementById('loading-overlay').classList.remove('hidden'); }
            }
        }
    </script>

    <style>
        .animate-fadeIn { animation: fadeIn 0.4s ease-out forwards; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        .animate-slideDown { animation: slideDown 0.4s ease forwards; }
        @keyframes slideDown { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
    </style>
    @endpush
</x-app-layout>
