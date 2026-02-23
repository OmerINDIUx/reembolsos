<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Detalle de la Solicitud') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- User Correction Panel -->
            @if(Auth::user()->id === $reimbursement->user_id && $reimbursement->status === 'requiere_correccion')
                <div class="bg-yellow-50 dark:bg-yellow-900/20 shadow sm:rounded-lg border-2 border-yellow-300 dark:border-yellow-600">
                    <div class="p-6">
                        <h4 class="text-lg font-bold text-yellow-800 dark:text-yellow-300 mb-2">Requiere Corrección</h4>
                        <p class="text-sm text-yellow-700 dark:text-yellow-400 mb-4">
                            Esta solicitud requiere que corrijas los archivos o proporciones más información.
                        </p>
                        <form action="{{ route('reimbursements.update', $reimbursement->id) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="is_resubmission" value="1">
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Actualizar archivo PDF (Opcional)</label>
                                    <input type="file" id="pdf_file_input" name="pdf_file" accept=".pdf" class="mt-1 block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-indigo-900 dark:file:text-indigo-300">
                                    <div id="pdf-validation-result" class="mt-2 text-sm hidden"></div>
                                </div>

                                @if($reimbursement->type === 'comida')
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300" for="attendees_count">Número de Asistentes</label>
                                        <input type="number" name="attendees_count" id="attendees_count" value="{{ old('attendees_count', $reimbursement->attendees_count) }}" min="1" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300" for="location">Lugar</label>
                                        <input type="text" name="location" id="location" value="{{ old('location', $reimbursement->location) }}" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300" for="attendees_names">Nombre de los Asistentes</label>
                                        <textarea name="attendees_names" id="attendees_names" rows="2" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">{{ old('attendees_names', $reimbursement->attendees_names) }}</textarea>
                                    </div>
                                @endif

                                @if($reimbursement->type === 'viaje')
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300" for="title">Título del Viaje / Gasto</label>
                                        <input type="text" name="title" id="title" value="{{ old('title', $reimbursement->title) }}" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300" for="trip_destination">Lugar de Destino</label>
                                        <input type="text" name="trip_destination" id="trip_destination" value="{{ old('trip_destination', $reimbursement->trip_destination) }}" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300" for="trip_nights">Noches</label>
                                        <input type="number" name="trip_nights" id="trip_nights" value="{{ old('trip_nights', $reimbursement->trip_nights) }}" min="0" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300" for="trip_start_date">Fecha de Inicio</label>
                                        <input type="date" name="trip_start_date" id="trip_start_date" value="{{ old('trip_start_date', $reimbursement->trip_start_date) }}" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300" for="trip_end_date">Fecha Final</label>
                                        <input type="date" name="trip_end_date" id="trip_end_date" value="{{ old('trip_end_date', $reimbursement->trip_end_date) }}" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    </div>
                                @endif

                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300" for="observaciones_update">Justificación / Información Adicional</label>
                                    <textarea name="user_correction_comment" id="observaciones_update" rows="2" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="Añade nueva información o describe las correcciones..." required></textarea>
                                </div>
                            </div>

                            <div class="flex justify-end mt-6">
                                <button type="submit" class="inline-flex items-center px-6 py-3 bg-indigo-600 border border-transparent rounded-md font-bold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition ease-in-out duration-150 shadow-md">
                                    Reenviar para Aprobación
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
                <div class="px-4 py-5 sm:px-6">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100">
                            {{ $reimbursement->title ?? 'Información General' }}
                        </h3>
                        <button disabled title="Próximamente" class="inline-flex items-center px-4 py-2 bg-gray-600 dark:bg-gray-700 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest cursor-not-allowed opacity-75">
                            <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            Próximamente
                        </button>
                    </div>
                    <p class="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
                        UUID: {{ $reimbursement->uuid ?? 'N/A (Viaje/Solicitud)' }}
                    </p>
                </div>
                <div class="border-t border-gray-200 dark:border-gray-700">
                    <dl>
                        <div class="bg-gray-50 dark:bg-gray-700 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-300">Folio</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 sm:mt-0 sm:col-span-2">{{ $reimbursement->folio ?? 'N/A' }}</dd>
                        </div>
                        <div class="bg-white dark:bg-gray-800 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-300">Emisor</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 sm:mt-0 sm:col-span-2">{{ $reimbursement->nombre_emisor }} ({{ $reimbursement->rfc_emisor }})</dd>
                        </div>
                        <div class="bg-white dark:bg-gray-800 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-300">Tipo de Solicitud</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 sm:mt-0 sm:col-span-2">{{ ucfirst(str_replace('_', ' ', $reimbursement->type ?? 'Reembolso')) }}</dd>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-300">Centro de Costos</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 sm:mt-0 sm:col-span-2">{{ $reimbursement->costCenter->name ?? 'N/A' }}</dd>
                        </div>
                        <div class="bg-white dark:bg-gray-800 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-300">Categoría</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 sm:mt-0 sm:col-span-2">{{ ucfirst($reimbursement->category ?? 'N/A') }}</dd>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-300">Semana</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 sm:mt-0 sm:col-span-2">{{ $reimbursement->week ?? 'N/A' }}</dd>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-300">Receptor</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 sm:mt-0 sm:col-span-2">{{ $reimbursement->nombre_receptor }} ({{ $reimbursement->rfc_receptor }})</dd>
                        </div>
                        <div class="bg-white dark:bg-gray-800 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-300">Fecha</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 sm:mt-0 sm:col-span-2">{{ \Carbon\Carbon::parse($reimbursement->fecha)->format('d/m/Y H:i') }}</dd>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-300">Total</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 sm:mt-0 sm:col-span-2 font-bold text-lg">${{ number_format($reimbursement->total, 2) }} {{ $reimbursement->moneda }}</dd>
                        </div>
                        <div class="bg-white dark:bg-gray-800 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-300">Estatus</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 sm:mt-0 sm:col-span-2">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    {{ $reimbursement->status === 'aprobado' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : '' }}
                                    {{ $reimbursement->status === 'rechazado' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300' : '' }}
                                    {{ $reimbursement->status === 'requiere_correccion' ? 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300' : '' }}
                                    {{ $reimbursement->status === 'aprobado_director' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300' : '' }}
                                    {{ $reimbursement->status === 'aprobado_control' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300' : '' }}
                                    {{ $reimbursement->status === 'aprobado_ejecutivo' ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-300' : '' }}
                                    {{ $reimbursement->status === 'aprobado_cxp' ? 'bg-teal-100 text-teal-800 dark:bg-teal-900 dark:text-teal-300' : '' }}
                                    {{ $reimbursement->status === 'pendiente' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300' : '' }}
                                ">
                                    @if($reimbursement->status === 'aprobado') Pagado @else {{ ucfirst(str_replace('_', ' ', $reimbursement->status)) }} @endif
                                </span>
                            </dd>
                        </div>
                        <!-- Approval Details -->
                        <div class="bg-white dark:bg-gray-800 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-300">Aprobaciones</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 sm:mt-0 sm:col-span-2">
                                <ul class="list-disc list-inside">
                                    @php $steps = [
                                        ['label' => 'Director', 'id' => $reimbursement->approved_by_director_id, 'name' => $reimbursement->directorApprover->name ?? 'Director', 'at' => $reimbursement->approved_by_director_at],
                                        ['label' => 'Control de Obra', 'id' => $reimbursement->approved_by_control_id, 'name' => $reimbursement->controlApprover->name ?? 'Control de Obra', 'at' => $reimbursement->approved_by_control_at],
                                        ['label' => 'Dir. Ejecutivo', 'id' => $reimbursement->approved_by_executive_id, 'name' => $reimbursement->executiveApprover->name ?? 'Dir. Ejecutivo', 'at' => $reimbursement->approved_by_executive_at],
                                        ['label' => 'CXP', 'id' => $reimbursement->approved_by_cxp_id, 'name' => $reimbursement->cxpApprover->name ?? 'CXP', 'at' => $reimbursement->approved_by_cxp_at],
                                        ['label' => 'Tesorería', 'id' => $reimbursement->approved_by_treasury_id, 'name' => $reimbursement->treasuryApprover->name ?? 'Tesorería', 'at' => $reimbursement->approved_by_treasury_at],
                                    ]; @endphp

                                    @foreach($steps as $step)
                                        @if($step['id'])
                                            <li class="text-green-600 {{ !$loop->first ? 'mt-1' : '' }}">
                                                {{ $step['label'] }}: {{ $step['name'] }}
                                                <span class="text-gray-500 text-xs text-nowrap">({{ $step['at'] ? $step['at']->format('d/m/Y H:i') : '' }})</span>
                                            </li>
                                        @else
                                            <li class="text-gray-500 {{ !$loop->first ? 'mt-1' : '' }}">{{ $step['label'] }}: Pendiente</li>
                                        @break
                                        @endif
                                    @endforeach
                                </ul>
                            </dd>
                        </div>

                        <div class="bg-gray-50 dark:bg-gray-700 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-300">Observaciones</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 sm:mt-0 sm:col-span-2 whitespace-pre-wrap">{{ $reimbursement->observaciones ?? 'Ninguna' }}</dd>
                        </div>

                         <!-- Validation Status Row -->
                        <div class="bg-white dark:bg-gray-800 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-300">Validación XML vs PDF</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 sm:mt-0 sm:col-span-2">
                                @if($reimbursement->validation_data)
                                    <div class="flex items-center space-x-4">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ ($reimbursement->validation_data['uuid_match'] ?? false) ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                            UUID: {{ ($reimbursement->validation_data['uuid_match'] ?? false) ? 'Coincide' : 'No Coincide' }}
                                        </span>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ ($reimbursement->validation_data['total_match'] ?? false) ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                            Monto: {{ ($reimbursement->validation_data['total_match'] ?? false) ? 'Coincide' : 'Revisar' }}
                                        </span>
                                    </div>
                                    @if(!($reimbursement->validation_data['uuid_match'] ?? false))
                                        <p class="mt-1 text-xs text-red-500">El UUID del XML no fue encontrado en el PDF.</p>
                                    @endif
                                @else
                                    <span class="text-gray-500 italic">No validado automáticamente.</span>
                                @endif
                            </dd>
                        </div>
                        
                        <!-- Standard Files (XML/PDF) -->
                        <div class="bg-white dark:bg-gray-800 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-300">Archivos Fiscales</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 sm:mt-0 sm:col-span-2">
                                <ul class="border border-gray-200 rounded-md divide-y divide-gray-200 dark:border-gray-600 dark:divide-gray-600">
                                    @if($reimbursement->xml_path)
                                    <li class="pl-3 pr-4 py-3 flex items-center justify-between text-sm">
                                        <div class="w-0 flex-1 flex items-center">
                                            <span class="ml-2 flex-1 w-0 truncate">XML: {{ basename($reimbursement->xml_path) }}</span>
                                        </div>
                                        <div class="ml-4 flex-shrink-0 flex space-x-4">
                                            <a href="{{ route('reimbursements.view_file', ['reimbursement' => $reimbursement, 'type' => 'xml']) }}" target="_blank" class="font-medium text-indigo-600 hover:text-indigo-500">Ver</a>
                                            <a href="{{ Storage::url($reimbursement->xml_path) }}" download class="font-medium text-gray-500 hover:text-gray-700">Descargar</a>
                                        </div>
                                    </li>
                                    @endif
                                    @if($reimbursement->pdf_path)
                                    <li class="pl-3 pr-4 py-3 flex items-center justify-between text-sm">
                                        <div class="w-0 flex-1 flex items-center">
                                            <span class="ml-2 flex-1 w-0 truncate">PDF: {{ basename($reimbursement->pdf_path) }}</span>
                                        </div>
                                        <div class="ml-4 flex-shrink-0 flex space-x-4">
                                            <a href="{{ route('reimbursements.view_file', ['reimbursement' => $reimbursement, 'type' => 'pdf']) }}" target="_blank" class="font-medium text-indigo-600 hover:text-indigo-500">Ver</a>
                                            <a href="{{ Storage::url($reimbursement->pdf_path) }}" download class="font-medium text-gray-500 hover:text-gray-700">Descargar</a>
                                        </div>
                                    </li>
                                    @endif
                                    @if(!$reimbursement->xml_path && !$reimbursement->pdf_path)
                                        <li class="pl-3 pr-4 py-3 text-sm text-gray-500">Sin archivos fiscales adjuntos.</li>
                                    @endif
                                </ul>
                                
                                <!-- Validation Data Display -->
                                @if($reimbursement->validation_data)
                                    <div class="mt-4 p-4 rounded-md {{ ($reimbursement->validation_data['uuid_match'] ?? false) ? 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800' : 'bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800' }}">
                                        <h4 class="text-sm font-medium {{ ($reimbursement->validation_data['uuid_match'] ?? false) ? 'text-green-800 dark:text-green-300' : 'text-yellow-800 dark:text-yellow-300' }} mb-2">
                                            Validación Automática de Archivos
                                        </h4>
                                        <ul class="list-disc list-inside text-sm {{ ($reimbursement->validation_data['uuid_match'] ?? false) ? 'text-green-700 dark:text-green-400' : 'text-yellow-700 dark:text-yellow-400' }}">
                                            <li>
                                                <strong>UUID en PDF:</strong> 
                                                @if($reimbursement->validation_data['uuid_match'] ?? false)
                                                    ✅ Coincide con el XML
                                                @else
                                                    ⚠️ NO encontrado o no coincide
                                                @endif
                                            </li>
                                            <li>
                                                <strong>Monto Total en PDF:</strong> 
                                                @if($reimbursement->validation_data['total_match'] ?? false)
                                                    ✅ Coincide con el XML
                                                @else
                                                    ⚠️ No detectado automáticamente (verificar manualmente)
                                                @endif
                                            </li>
                                        </ul>
                                        @if(isset($reimbursement->validation_data['message']))
                                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400 italic">
                                                Sistema: {{ $reimbursement->validation_data['message'] }}
                                            </p>
                                        @endif
                                    </div>
                                @endif
                            </dd>
                        </div>

                        <!-- Trip Specifics -->
                        @if($reimbursement->type === 'viaje')
                            <div class="bg-white dark:bg-gray-800 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-300">Detalles del Viaje</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 sm:mt-0 sm:col-span-2">
                                    <ul class="list-disc pl-5">
                                        <li><strong>Tipo:</strong> {{ ucfirst($reimbursement->trip_type) }}</li>
                                        <li><strong>Destino:</strong> {{ $reimbursement->trip_destination }}</li>
                                        <li><strong>Duración:</strong> {{ $reimbursement->trip_nights }} noches</li>
                                        <li><strong>Fechas:</strong> {{ \Carbon\Carbon::parse($reimbursement->trip_start_date)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($reimbursement->trip_end_date)->format('d/m/Y') }}</li>
                                    </ul>
                                </dd>
                            </div>

                            @if($reimbursement->trip_type === 'internacional')
                                <div class="bg-gray-50 dark:bg-gray-700 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-300">Archivos Adjuntos</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 sm:mt-0 sm:col-span-2">
                                        @if($reimbursement->files->count() > 0)
                                            <ul class="border border-gray-200 rounded-md divide-y divide-gray-200 dark:border-gray-600 dark:divide-gray-600">
                                                @foreach($reimbursement->files as $file)
                                                    <li class="pl-3 pr-4 py-3 flex items-center justify-between text-sm">
                                                        <div class="w-0 flex-1 flex items-center">
                                                            <svg class="flex-shrink-0 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                                <path fill-rule="evenodd" d="M8 4a3 3 0 00-3 3v4a5 5 0 0010 0V7a1 1 0 112 0v4a7 7 0 11-14 0V7a5 5 0 0110 0v4a3 3 0 11-6 0V7a1 1 0 012 0v4a1 1 0 102 0V7a3 3 0 00-3-3z" clip-rule="evenodd" />
                                                            </svg>
                                                            <span class="ml-2 flex-1 w-0 truncate">
                                                                {{ $file->original_name }}
                                                            </span>
                                                        </div>
                                                        <div class="ml-4 flex-shrink-0">
                                                            <a href="{{ Storage::url($file->file_path) }}" target="_blank" class="font-medium text-indigo-600 hover:text-indigo-500">
                                                                Descargar
                                                            </a>
                                                        </div>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @else
                                            <span class="text-gray-500">No hay archivos adjuntos.</span>
                                        @endif
                                    </dd>
                                </div>
                            @endif
                        @endif
                        
                        <!-- Child Reimbursements (For Nacional Trips) -->
                        @if($reimbursement->children->count() > 0)
                            <div class="bg-white dark:bg-gray-800 px-4 py-5 sm:px-6">
                                <h4 class="text-md font-medium text-gray-900 dark:text-gray-100 mb-4">Gastos Vinculados</h4>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                        <thead class="bg-gray-50 dark:bg-gray-700">
                                            <tr>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tipo</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Fecha</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Total</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Emisor</th>
                                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                            @foreach($reimbursement->children as $child)
                                                <tr>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">{{ ucfirst($child->type) }}</td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ \Carbon\Carbon::parse($child->fecha)->format('d/m/Y') }}</td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">${{ number_format($child->total, 2) }}</td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $child->nombre_emisor }}</td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                        <a href="{{ route('reimbursements.show', $child) }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">Ver</a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif

                    </dl>
                </div>
                
                <!-- Footer with Action Buttons -->
                <div class="bg-gray-50 dark:bg-gray-800 px-4 py-4 sm:px-6 flex justify-between items-center border-t border-gray-200 dark:border-gray-700">
                     <div class="flex space-x-3">
                        @if($reimbursement->type === 'viaje' && $reimbursement->trip_type === 'nacional')
                            <a href="{{ route('reimbursements.create', ['type' => 'reembolso', 'trip_id' => $reimbursement->id]) }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring ring-indigo-300 disabled:opacity-25 transition ease-in-out duration-150">
                                + Agregar Reembolso
                            </a>
                            <a href="{{ route('reimbursements.create', ['type' => 'comida', 'trip_id' => $reimbursement->id]) }}" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 active:bg-green-900 focus:outline-none focus:border-green-900 focus:ring ring-green-300 disabled:opacity-25 transition ease-in-out duration-150">
                                + Agregar Comida
                            </a>
                        @endif
                        
                        @if($reimbursement->parent)
                             <a href="{{ route('reimbursements.show', $reimbursement->parent) }}" class="text-sm text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">
                                &larr; Volver al Viaje: {{ $reimbursement->parent->title }}
                            </a>
                        @endif
                    </div>

                    <a href="{{ route('reimbursements.index') }}" class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-25 transition ease-in-out duration-150">
                        Regresar al Listado
                    </a>
                </div>

                <!-- Approval Actions -->
                @php
                    $user = Auth::user();
                    $cc = $reimbursement->costCenter;
                    $canApproveDirector = ($user->isAdmin() || ($user->isDirector() && $user->id === $cc->director_id)) && $reimbursement->status === 'pendiente';
                    $canApproveControl = ($user->isAdmin() || ($user->isControlObra() && $user->id === $cc->control_obra_id)) && $reimbursement->status === 'aprobado_director';
                    $canApproveExecutive = ($user->isAdmin() || ($user->isExecutiveDirector() && $user->id === $cc->director_ejecutivo_id)) && $reimbursement->status === 'aprobado_control';
                    $canApproveCXP = ($user->isAdmin() || $user->isCxp()) && $reimbursement->status === 'aprobado_ejecutivo';
                    $canApproveTreasury = ($user->isAdmin() || $user->isTreasury()) && $reimbursement->status === 'aprobado_cxp';
                    
                    $canApproveAny = $canApproveDirector || $canApproveControl || $canApproveExecutive || $canApproveCXP || $canApproveTreasury;
                @endphp

                @if($canApproveAny)
                    <div class="bg-gray-100 dark:bg-gray-900 p-4 border-t border-gray-200 dark:border-gray-700 flex justify-end space-x-4">
                        <form action="{{ route('reimbursements.update', $reimbursement->id) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="status" value="aprobado">
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition ease-in-out duration-150">
                                @if($canApproveTreasury) Aprobar Final (Pago)
                                @elseif($canApproveCXP) Validar CXP
                                @elseif($canApproveExecutive) Aprobar N3 (Dir. Ejecutivo)
                                @elseif($canApproveControl) Aprobar N2 (Control Obra)
                                @else Aprobar N1 (Director)
                                @endif
                            </button>
                        </form>

                        <button type="button" x-data @click="$dispatch('open-rejection-modal')" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition ease-in-out duration-150">
                            Rechazar
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const pdfInput = document.getElementById('pdf_file_input');
            const validationResult = document.getElementById('pdf-validation-result');

            if (pdfInput) {
                pdfInput.addEventListener('change', function() {
                    if (this.files.length === 0) {
                        validationResult.classList.add('hidden');
                        return;
                    }

                    validationResult.classList.remove('hidden');
                    validationResult.innerHTML = '<span class="text-gray-500">Validando archivo PDF en tiempo real...</span>';

                    const formData = new FormData();
                    formData.append('pdf_file', this.files[0]);
                    formData.append('_token', '{{ csrf_token() }}');

                    fetch('{{ route("reimbursements.validate_pdf_correction", $reimbursement->id) }}', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) {
                            validationResult.innerHTML = `
                                <div class="p-4 rounded-2xl bg-red-50 dark:bg-red-900/20 border border-red-100 dark:border-red-800/50 flex items-start">
                                    <svg class="w-5 h-5 text-red-500 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    <div>
                                        <p class="text-[10px] font-black text-red-600 dark:text-red-400 uppercase tracking-widest mb-1">Error de Validación</p>
                                        <p class="text-sm font-medium text-red-700 dark:text-red-300">${data.error}</p>
                                    </div>
                                </div>
                            `;
                            return;
                        }

                        if (data.uuid_match) {
                            validationResult.innerHTML = `
                                <div class="p-4 rounded-2xl bg-green-50 dark:bg-green-900/20 border border-green-100 dark:border-green-800/50 flex items-start">
                                    <svg class="w-5 h-5 text-green-500 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    <div>
                                        <p class="text-[10px] font-black text-green-600 dark:text-green-400 uppercase tracking-widest mb-1">Validación Exitosa</p>
                                        <p class="text-sm font-medium text-green-700 dark:text-green-300">Excelente: El UUID coincide con el XML. Puedes continuar.</p>
                                    </div>
                                </div>
                            `;
                        } else {
                            validationResult.innerHTML = `
                                <div class="p-4 rounded-2xl bg-orange-50 dark:bg-orange-900/20 border border-orange-100 dark:border-orange-800/50 flex items-start">
                                    <svg class="w-5 h-5 text-orange-500 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                    <div>
                                        <p class="text-[10px] font-black text-orange-600 dark:text-orange-400 uppercase tracking-widest mb-1">Advertencia</p>
                                        <p class="text-sm font-medium text-orange-700 dark:text-orange-300">${data.message}</p>
                                    </div>
                                </div>
                            `;
                        }
                    })
                    .catch(error => {
                        validationResult.innerHTML = `
                            <div class="p-4 rounded-2xl bg-red-50 dark:bg-red-900/20 border border-red-100 dark:border-red-800/50 flex items-center">
                                <svg class="w-5 h-5 text-red-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <span class="text-xs font-black text-red-700 dark:text-red-400 uppercase">Error crítico al validar el archivo.</span>
                            </div>
                        `;
                        console.error('Error:', error);
                    });
                });
            }
        });
    </script>
    @endpush
</x-app-layout>

<!-- Rejection Modal -->
<div x-data="{ open: false, reasons: [
    'Falta comprobante fiscal (XML/PDF)',
    'El monto no coincide con la factura',
    'Gasto no autorizado',
    'Fuera de política de viáticos',
    'Duplicado de solicitud',
    'Error en centro de costos',
    'Falta justificación detallada',
    'Fecha fuera del periodo permitido',
    'Excede el límite de gastos',
    'Otro'
] }" 
     @open-rejection-modal.window="open = true" 
     x-show="open" 
     class="fixed z-10 inset-0 overflow-y-auto" 
     style="display: none;">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity" aria-hidden="true">
            <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form action="{{ route('reimbursements.update', $reimbursement->id) }}" method="POST">
                @csrf
                @method('PUT')
                
                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                            <!-- Heroicon name: outline/exclamation -->
                            <svg class="h-6 w-6 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100" id="modal-title">
                                Rechazar Reembolso
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                                    Por favor, seleccione una razón para rechazar este reembolso.
                                </p>
                                <label for="rejection_reason" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Razón de Rechazo</label>
                                <select name="rejection_reason" id="rejection_reason" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>
                                    <option value="">Seleccione una opción</option>
                                    <template x-for="reason in reasons" :key="reason">
                                        <option :value="reason" x-text="reason"></option>
                                    </template>
                                </select>
                                
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mt-4">Tipo de Rechazo</label>
                                <div class="mt-2 space-y-2">
                                    <div class="flex items-center">
                                        <input id="rechazo_correccion" name="status" type="radio" value="requiere_correccion" class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 dark:bg-gray-700 dark:border-gray-600" required>
                                        <label for="rechazo_correccion" class="ml-3 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Requiere Corrección (El usuario podrá actualizar archivos y reenviar)
                                        </label>
                                    </div>
                                    <div class="flex items-center">
                                        <input id="rechazo_definitivo" name="status" type="radio" value="rechazado" class="focus:ring-red-500 h-4 w-4 text-red-600 border-gray-300 dark:bg-gray-700 dark:border-gray-600">
                                        <label for="rechazo_definitivo" class="ml-3 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Rechazo Definitivo (No se podrá modificar)
                                        </label>
                                    </div>
                                </div>
                                
                                <label for="rejection_comment" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mt-4">Comentario Adicional (Opcional)</label>
                                <textarea name="rejection_comment" id="rejection_comment" rows="3" class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 mt-1 block w-full sm:text-sm border-gray-300 dark:bg-gray-700 dark:text-gray-300 rounded-md"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Confirmar Rechazo
                    </button>
                    <button type="button" @click="open = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
