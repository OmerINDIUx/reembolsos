<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Nuevo ') . ucfirst(str_replace('_', ' ', $type)) }}
        </h2>
    </x-slot>

    <div class="py-12 relative">
        <!-- Loading Overlay -->
        <div id="loading-overlay" class="hidden absolute inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-50 rounded-lg">
            <div class="bg-white p-4 rounded-lg shadow-lg flex flex-col items-center">
                <svg class="animate-spin h-10 w-10 text-indigo-600 mb-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="text-gray-700 font-medium">Procesando Comprobantes...</p>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100" x-data="{ 
                        type: '{{ $type }}',
                        tripType: null,
                        uploading: false,
                        files: [],
                        init() {
                            this.$watch('files', value => {
                                const input = document.getElementById('extra_files_input');
                                const dt = new DataTransfer();
                                value.forEach(file => dt.items.add(file));
                                input.files = dt.files;
                            });
                        },
                        removeFile(index) {
                            this.files.splice(index, 1);
                        },
                        handleDrop(e) {
                            if (this.tripType !== 'internacional') return;
                            const droppedFiles = e.dataTransfer.files;
                            this.addFiles(droppedFiles);
                        },
                        addFiles(fileList) {
                            for (let i = 0; i < fileList.length; i++) {
                                // Prevent duplicates if needed, or simple push
                                this.files.push(fileList[i]);
                            }
                        }
                    }">
                    <div class="mb-6 flex justify-between items-center">
                         <a href="{{ route('reimbursements.create') }}" class="text-sm text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">
                            &larr; Cambiar Tipo de Solicitud
                        </a>
                    </div>

                    <form action="{{ route('reimbursements.store') }}" method="POST" enctype="multipart/form-data" id="reimbursement-form">
                        @csrf
                        <input type="hidden" name="type" value="{{ $type }}">
                        <input type="hidden" name="validation_data" id="validation_data_input">
                        @if(isset($parentReimbursement))
                            <input type="hidden" name="parent_id" value="{{ $parentReimbursement->id }}">
                            <div class="bg-blue-50 dark:bg-blue-900 border-l-4 border-blue-400 p-4 mb-4">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm text-blue-700 dark:text-blue-200">
                                            Agregando gasto al viaje: <strong>{{ $parentReimbursement->title ?? 'Viaje sin título' }}</strong>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endif
                        
                        <!-- Standard XML/PDF Inputs (Hidden for Viaje International/Nacional initially) -->
                        <div x-show="type !== 'viaje'">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" for="xml_file">
                                        Archivo XML (CFDI) *
                                    </label>
                                    <input class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:text-gray-400 focus:outline-none dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400" id="xml_file" type="file" name="xml_file" accept=".xml" :required="type !== 'viaje'">
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-300">Carga el XML para autocompletar los campos.</p>
                                    @error('xml_file')
                                        <p class="text-red-500 text-xs italic mt-2">{{ $message }}</p>
                                    @enderror
                                </div>
    
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" for="pdf_file">
                                        Archivo PDF
                                    </label>
                                    <div class="flex items-center mb-2">
                                        <input id="no_pdf" name="no_pdf" type="checkbox" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                        <label for="no_pdf" class="ml-2 block text-sm text-gray-900 dark:text-gray-300">
                                            No cuento con el PDF
                                        </label>
                                    </div>
                                    <input class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:text-gray-400 focus:outline-none dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400" id="pdf_file" type="file" name="pdf_file" accept=".pdf">
                                    @error('pdf_file')
                                        <p class="text-red-500 text-xs italic mt-2">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Viaje Logic -->
                        <template x-if="type === 'viaje'">
                            <div class="mb-8 border-b pb-6 border-gray-200 dark:border-gray-700">
                                <label class="block text-lg font-medium text-gray-900 dark:text-gray-100 mb-4 text-center">Tipo de Destino</label>
                                <div class="flex justify-center space-x-6">
                                    <label class="cursor-pointer border-2 rounded-lg p-4 hover:bg-gray-50 dark:hover:bg-gray-700 transition" :class="tripType === 'nacional' ? 'border-indigo-600 bg-indigo-50 dark:bg-indigo-900' : 'border-gray-300'">
                                        <input type="radio" name="trip_type" value="nacional" class="hidden" x-model="tripType">
                                        <span class="block text-center font-bold text-gray-900 dark:text-gray-100">Nacional</span>
                                        <span class="block text-xs text-gray-500 dark:text-gray-400 mt-1">Con facturas XML/PDF</span>
                                    </label>
                                    <label class="cursor-pointer border-2 rounded-lg p-4 hover:bg-gray-50 dark:hover:bg-gray-700 transition" :class="tripType === 'internacional' ? 'border-indigo-600 bg-indigo-50 dark:bg-indigo-900' : 'border-gray-300'">
                                        <input type="radio" name="trip_type" value="internacional" class="hidden" x-model="tripType">
                                        <span class="block text-center font-bold text-gray-900 dark:text-gray-100">Internacional</span>
                                        <span class="block text-xs text-gray-500 dark:text-gray-400 mt-1">Archivos varios (Drag & Drop)</span>
                                    </label>
                                </div>
                            </div>
                        </template>

                        <!-- Trip Common Fields -->
                         <div x-show="type === 'viaje' && tripType">
                             <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" for="title">Título del Viaje *</label>
                                    <input type="text" name="title" id="title" class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="Ej: Visita a Cliente X en Monterrey" :required="type === 'viaje'">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" for="trip_nights">Duración del Viaje (Noches) *</label>
                                    <input type="number" name="trip_nights" id="trip_nights" min="0" class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" :required="type === 'viaje'">
                                </div>
                                 <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" for="trip_destination">Lugar de Destino *</label>
                                    <input type="text" name="trip_destination" id="trip_destination" class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="Ciudad, Estado, País..." :required="type === 'viaje'">
                                </div>
                                 <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" for="trip_start_date">Fecha de Inicio *</label>
                                    <input type="date" name="trip_start_date" id="trip_start_date" class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" :required="type === 'viaje'">
                                </div>
                                 <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" for="trip_end_date">Fecha Final *</label>
                                    <input type="date" name="trip_end_date" id="trip_end_date" class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" :required="type === 'viaje'">
                                </div>
                             </div>
                         </div>
                        
                        <hr class="my-6 border-gray-200 dark:border-gray-700" x-show="type !== 'viaje'">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4" x-show="type !== 'viaje'">Detalles del Comprobante</h3>

                        <!-- Auto-filled Fields / Common Fields -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6" x-show="type !== 'viaje'"> <!-- Hide standard details for Trip until supported -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" for="cost_center_id">Centro de Costos</label>
                                <select name="cost_center_id" id="cost_center_id" class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required x-bind:disabled="type === 'viaje'">
                                    <option value="">Seleccione un Centro de Costos...</option>
                                    @foreach($costCenters as $center)
                                        <option value="{{ $center->id }}">{{ $center->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" for="category">Categoría</label>
                                <input list="categories_list" name="category" id="category" class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="Seleccione o escriba..." :required="type !== 'viaje'" autocomplete="off">
                                <datalist id="categories_list">
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat }}">
                                    @endforeach
                                </datalist>
                                @error('category')
                                    <p class="text-red-500 text-xs italic mt-2">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" for="week">Semana (Año-Semana)</label>
                                <input type="text" name="week" id="week" value="{{ $currentWeek }}" class="w-full bg-gray-100 dark:bg-gray-700 border-gray-300 dark:border-gray-600 dark:text-gray-300 rounded-md shadow-sm sm:text-sm cursor-not-allowed" readonly>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" for="uuid">UUID (Folio Fiscal)</label>
                                <input type="text" name="uuid" id="uuid" class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" readonly :required="type !== 'viaje'">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" for="folio">Folio Interno</label>
                                <input type="text" name="folio" id="folio" class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" for="rfc_emisor">RFC Emisor</label>
                                <input type="text" name="rfc_emisor" id="rfc_emisor" class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" readonly>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" for="nombre_emisor">Nombre Emisor</label>
                                <input type="text" name="nombre_emisor" id="nombre_emisor" class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" readonly>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" for="rfc_receptor">RFC Receptor</label>
                                <input type="text" name="rfc_receptor" id="rfc_receptor" class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" readonly>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" for="nombre_receptor">Nombre Receptor</label>
                                <input type="text" name="nombre_receptor" id="nombre_receptor" class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm mb-2" readonly>
                                <div class="flex items-center">
                                    <input id="confirm_company" name="confirm_company" type="checkbox" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded" required>
                                    <label for="confirm_company" class="ml-2 block text-sm text-gray-900 dark:text-gray-300">
                                        Confirmo que es la empresa donde estoy dado de alta *
                                    </label>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" for="fecha">Fecha de Emisión</label>
                                <input type="text" name="fecha" id="fecha" class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" readonly>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" for="moneda">Moneda</label>
                                <input type="text" name="moneda" id="moneda" class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" readonly>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" for="subtotal">Subtotal</label>
                                <input type="number" step="0.01" name="subtotal" id="subtotal" class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" readonly>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" for="total">Total</label>
                                <input type="number" step="0.01" name="total" id="total" class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" readonly :required="type !== 'viaje'">
                            </div>
                            
                            <input type="hidden" name="tipo_comprobante" id="tipo_comprobante">
                        </div>
                        
                        <!-- Cost Center Copy for Viaje (Needs to be visible always essentially, or at least for Viaje too) -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6" x-show="type === 'viaje' && tripType">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" for="cost_center_id_viaje">Centro de Costos</label>
                                <select name="cost_center_id" id="cost_center_id_viaje" class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required x-bind:disabled="type !== 'viaje'">
                                    <option value="">Seleccione un Centro de Costos...</option>
                                    @foreach($costCenters as $center)
                                        <option value="{{ $center->id }}">{{ $center->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                             <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" for="week_viaje">Semana (Año-Semana)</label>
                                <input type="text" name="week" id="week_viaje" value="{{ $currentWeek }}" class="w-full bg-gray-100 dark:bg-gray-700 border-gray-300 dark:border-gray-600 dark:text-gray-300 rounded-md shadow-sm sm:text-sm cursor-not-allowed" readonly>
                            </div>
                        </div>


                        <div class="mb-6" x-show="type === 'viaje' ? tripType : true">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" for="observaciones">
                                Justificación del Reembolso 
                                @if($type === 'comida') 
                                    (motivo del negocio) 
                                @elseif($type === 'viaje')
                                    (Describe los resultados tu agenda y el motivo del viaje)
                                @endif
                            </label>
                            <textarea name="observaciones" id="observaciones" rows="3" class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"></textarea>
                        </div>
                        
                        <!-- Drag & Drop Zone for International -->
                        <div class="mb-6" x-show="type === 'viaje' && tripType === 'internacional'">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Documentos del Viaje (Imágenes, PDF, Word)</label>
                            <div 
                                class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-8 text-center hover:bg-gray-50 dark:hover:bg-gray-700 transition relative"
                                @dragover.prevent="uploading = true"
                                @dragleave.prevent="uploading = false"
                                @drop.prevent="uploading = false; handleDrop($event)"
                            >
                                <input type="file" id="extra_files_input" name="extra_files[]" multiple class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" @change="addFiles($event.target.files)">
                                <div class="flex flex-col items-center justify-center">
                                    <svg class="w-12 h-12 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Arrivastra archivos aquí o haz click para seleccionar</p>
                                    <p class="text-xs text-gray-500 mt-1">Soporta múltiples archivos</p>
                                </div>
                            </div>
                            
                            <!-- File List -->
                            <div class="mt-4 space-y-2" x-show="files.length > 0">
                                <template x-for="(file, index) in files" :key="index">
                                    <div class="flex items-center justify-between bg-gray-50 dark:bg-gray-700 p-2 rounded-md">
                                        <span class="text-sm text-gray-700 dark:text-gray-300" x-text="file.name"></span>
                                        <button type="button" @click="removeFile(index)" class="text-red-500 hover:text-red-700 text-sm">Eliminar</button>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Buttons for Nacional -->
                        <div class="mb-6" x-show="type === 'viaje' && tripType === 'nacional'">
                            <div class="bg-yellow-50 dark:bg-yellow-900 p-4 rounded-md border border-yellow-200 dark:border-yellow-700 mb-4">
                                <p class="text-sm text-yellow-800 dark:text-yellow-200">
                                    <strong>Nota:</strong> Para agregar gastos (Reembolsos y Comidas) vinculados a este viaje:
                                </p>
                                <ol class="list-decimal list-inside text-sm text-yellow-800 dark:text-yellow-200 mt-1">
                                    <li>Complete la información del viaje y haga clic en "Guardar y Continuar".</li>
                                    <li>Será redirigido al panel del viaje donde podrá usar los botones de "Agregar Reembolso" o "Agregar Comida".</li>
                                </ol>
                            </div>
                             <div class="flex space-x-4 opacity-50 cursor-not-allowed"> 
                                <!-- Mock buttons to show the user what's coming, as requested "aparecen abajo" -->
                                <button type="button" disabled class="flex items-center px-4 py-2 bg-indigo-100 text-indigo-700 rounded-md">
                                    + Agregar Reembolso (XML/PDF)
                                </button>
                                <button type="button" disabled class="flex items-center px-4 py-2 bg-indigo-100 text-indigo-700 rounded-md">
                                    + Agregar Comida (XML/PDF)
                                </button>
                            </div>
                        </div>

                        <!-- Comida Fields -->
                        @if($type === 'comida')
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" for="attendees_count">Número de Asistentes *</label>
                                <input type="number" name="attendees_count" id="attendees_count" min="1" class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" for="location">Lugar *</label>
                                <input type="text" name="location" id="location" class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="Restaurante, Ciudad, etc." required>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" for="attendees_names">Nombre de los Asistentes (Opcional)</label>
                                <textarea name="attendees_names" id="attendees_names" rows="2" class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="Nombres separados por comas..."></textarea>
                            </div>
                        </div>
                        @endif

                        <div class="flex items-center justify-end" x-show="type !== 'viaje' || (type === 'viaje' && tripType)">
                            <a href="{{ route('reimbursements.index') }}" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 mr-4">Cancelar</a>
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white focus:bg-gray-700 dark:focus:bg-white active:bg-gray-900 dark:active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                                <span x-text="type === 'viaje' && tripType === 'nacional' ? 'Guardar y Continuar' : 'Guardar Reembolso'"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        const xmlInput = document.getElementById('xml_file');
        const pdfInput = document.getElementById('pdf_file');
        
        const noPdfCheckbox = document.getElementById('no_pdf');
        const loadingOverlay = document.getElementById('loading-overlay');
        
        noPdfCheckbox.addEventListener('change', function() {
            if (this.checked) {
                pdfInput.disabled = true;
                pdfInput.value = ''; // Clear file
                pdfInput.classList.add('opacity-50', 'cursor-not-allowed');
                // Re-trigger parse if XML exists to clear validation messages about PDF
                if (xmlInput.files.length > 0) parseFiles();
            } else {
                pdfInput.disabled = false;
                pdfInput.classList.remove('opacity-50', 'cursor-not-allowed');
            }
        });

        function parseFiles() {
            const xmlFile = xmlInput.files[0];
            const pdfFile = !noPdfCheckbox.checked ? pdfInput.files[0] : null;
            
            if (!xmlFile) return;

            // Show Loading
            loadingOverlay.classList.remove('hidden');

            const formData = new FormData();
            formData.append('xml_file', xmlFile);
            if (pdfFile) {
                formData.append('pdf_file', pdfFile);
            }
            formData.append('_token', '{{ csrf_token() }}');

            // Clear previous validation messages
            const existingMsg = document.getElementById('pdf-validation-msg');
            if(existingMsg) existingMsg.remove();

            fetch('{{ route("reimbursements.parse") }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                loadingOverlay.classList.add('hidden'); // Hide Loading

                if (data.error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error de Validación',
                        text: data.error,
                        confirmButtonText: 'Entendido'
                    });
                    // Clear the invalid file input to allow re-upload
                    xmlInput.value = ''; 
                } else {
                    // Populate fields
                    document.getElementById('uuid').value = data.uuid || '';
                    document.getElementById('folio').value = data.folio || '';
                    document.getElementById('rfc_emisor').value = data.rfc_emisor || '';
                    document.getElementById('nombre_emisor').value = data.nombre_emisor || '';
                    document.getElementById('rfc_receptor').value = data.rfc_receptor || '';
                    document.getElementById('nombre_receptor').value = data.nombre_receptor || '';
                    document.getElementById('fecha').value = data.fecha || '';
                    document.getElementById('moneda').value = data.moneda || '';
                    document.getElementById('subtotal').value = data.subtotal || '';
                    document.getElementById('total').value = data.total || '';
                    document.getElementById('tipo_comprobante').value = data.tipo_comprobante || '';
                    
                    // Handle PDF Validation Feedback
                    if (data.pdf_validation) {
                        // Store validation data in hidden input
                        document.getElementById('validation_data_input').value = JSON.stringify(data.pdf_validation);

                        const msgDiv = document.createElement('div');
                        msgDiv.id = 'pdf-validation-msg';
                        msgDiv.className = 'mt-4 p-4 rounded-md ' + (data.pdf_validation.uuid_match ? 'bg-green-50 text-green-700' : 'bg-yellow-50 text-yellow-700');
                        
                        let html = '';
                        if (data.pdf_validation.error) {
                             html = `<p class="font-bold">Error PDF:</p><p>${data.pdf_validation.error}</p>`;
                        } else {
                            html = `<p class="font-bold">Validación PDF:</p>
                                    <ul class="list-disc list-inside">
                                        <li>UUID en PDF: ${data.pdf_validation.uuid_match ? '✅ Encontrado' : '❌ NO Encontrado'}</li>
                                        <li>Total en PDF: ${data.pdf_validation.total_match ? '✅ Encontrado' : '⚠️ NO Encontrado (puede ser formato)'}</li>
                                    </ul>`;
                        }
                        msgDiv.innerHTML = html;
                        
                        // Insert after the file inputs container
                        document.querySelector('.grid.grid-cols-1.md\\:grid-cols-2.gap-6.mb-6').insertAdjacentElement('afterend', msgDiv);
                    }

                    // Success Toast
                    Swal.fire({
                        icon: 'success',
                        title: 'Información Extraída',
                        text: 'Los datos del XML han sido cargados correctamente.',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                loadingOverlay.classList.add('hidden');
                Swal.fire({
                    icon: 'error',
                    title: 'Error del Sistema',
                    text: 'Hubo un problema al comunicarse con el servidor.',
                });
            });
        }

        xmlInput.addEventListener('change', parseFiles);
        pdfInput.addEventListener('change', parseFiles);
    </script>
    @endpush
</x-app-layout>
