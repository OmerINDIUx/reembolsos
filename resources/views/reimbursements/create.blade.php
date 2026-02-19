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
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="mb-6 flex justify-between items-center">
                         <a href="{{ route('reimbursements.create') }}" class="text-sm text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">
                            &larr; Cambiar Tipo de Solicitud
                        </a>
                    </div>

                    <form action="{{ route('reimbursements.store') }}" method="POST" enctype="multipart/form-data" id="reimbursement-form">
                        @csrf
                        <input type="hidden" name="type" value="{{ $type }}">
                        
                        <!-- File Inputs -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" for="xml_file">
                                    Archivo XML (CFDI) *
                                </label>
                                <input class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:text-gray-400 focus:outline-none dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400" id="xml_file" type="file" name="xml_file" accept=".xml" required>
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

                        <hr class="my-6 border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Detalles del Comprobante</h3>

                        <!-- Auto-filled Fields -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" for="uuid">UUID (Folio Fiscal)</label>
                                <input type="text" name="uuid" id="uuid" class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" readonly required>
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
                                <input type="text" name="nombre_receptor" id="nombre_receptor" class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" readonly>
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
                                <input type="number" step="0.01" name="total" id="total" class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" readonly required>
                            </div>
                            
                            <input type="hidden" name="tipo_comprobante" id="tipo_comprobante">
                        </div>

                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" for="observaciones">Observaciones</label>
                            <textarea name="observaciones" id="observaciones" rows="3" class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"></textarea>
                        </div>

                        <div class="flex items-center justify-end">
                            <a href="{{ route('reimbursements.index') }}" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 mr-4">Cancelar</a>
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white focus:bg-gray-700 dark:focus:bg-white active:bg-gray-900 dark:active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                                Guardar Reembolso
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
