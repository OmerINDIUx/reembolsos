<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Detalle de la Solicitud') }}
        </h2>
    </x-slot>

    <div class="py-12 bg-gray-50/50 dark:bg-gray-950/50 min-h-screen font-sans text-gray-800 dark:text-gray-200">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <!-- User Correction Panel (High Priority) -->
            @if(!Auth::user()->isAdminView() && Auth::user()->id === $reimbursement->user_id && $reimbursement->status === 'requiere_correccion')
                <div class="mb-8 rounded-xl border border-yellow-300 bg-yellow-50 dark:bg-yellow-900/20 dark:border-yellow-700/50 shadow-sm">
                    <div class="p-6 md:p-8">
                        <div class="flex items-start gap-4">
                            <div class="p-3 bg-yellow-100 text-yellow-600 rounded-full dark:bg-yellow-800 dark:text-yellow-300">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            </div>
                            <div class="flex-1">
                                <h4 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Acción Requerida: Ajustes Necesarios</h4>
                                <p class="text-gray-700 dark:text-gray-300 text-base mb-6">Esta solicitud ha sido devuelta para corrección. Revisa las observaciones del aprobador y actualiza la información o archivos.</p>
                                
                                <form action="{{ route('reimbursements.update', $reimbursement->id) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="is_resubmission" value="1">
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div class="space-y-2">
                                            <label class="text-sm font-semibold text-gray-600 dark:text-gray-400">
                                                {{ $reimbursement->uuid ? 'Actualizar PDF (Opcional)' : 'Nuevo Comprobante (PDF/IMG)' }}
                                            </label>
                                            <div class="relative group">
                                                <input type="file" id="pdf_file_input" name="pdf_file" accept="{{ $reimbursement->uuid ? '.pdf' : '.pdf,image/*,.txt' }}" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                                                <div class="p-4 border-2 border-dashed border-gray-300 rounded-lg group-hover:border-indigo-500 bg-white dark:bg-gray-800 transition-colors text-center">
                                                    <svg class="w-6 h-6 text-gray-400 group-hover:text-indigo-500 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                                                    <span class="text-sm text-gray-500 dark:text-gray-400">Seleccionar Archivo...</span>
                                                </div>
                                            </div>
                                            <div id="pdf-validation-result" class="mt-2 text-sm hidden"></div>
                                        </div>
                                        
                                        <div class="space-y-2">
                                            <label class="text-sm font-semibold text-gray-600 dark:text-gray-400">Actualizar Ticket (Opcional)</label>
                                            <div class="relative group">
                                                <input type="file" name="ticket_file" accept=".pdf,.jpg,.jpeg,.png,.txt" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                                                <div class="p-4 border-2 border-dashed border-gray-300 rounded-lg group-hover:border-emerald-500 bg-white dark:bg-gray-800 transition-colors text-center">
                                                    <svg class="w-6 h-6 text-gray-400 group-hover:text-emerald-500 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path></svg>
                                                    <span class="text-sm text-gray-500 dark:text-gray-400">Seleccionar Ticket...</span>
                                                </div>
                                            </div>
                                        </div>

                                        @if($reimbursement->type === 'comida')
                                            <div class="space-y-2">
                                                <label class="text-sm font-semibold text-gray-600 dark:text-gray-400">Asistentes</label>
                                                <input type="number" name="attendees_count" value="{{ old('attendees_count', $reimbursement->attendees_count) }}" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-800 dark:border-gray-600">
                                            </div>
                                            <div class="space-y-2">
                                                <label class="text-sm font-semibold text-gray-600 dark:text-gray-400">Lugar</label>
                                                <input type="text" name="location" value="{{ old('location', $reimbursement->location) }}" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-800 dark:border-gray-600">
                                            </div>
                                        @endif
                                        
                                        <div class="md:col-span-2 space-y-2">
                                            <label class="text-sm font-semibold text-indigo-700 dark:text-indigo-400">Justificación de los Cambios</label>
                                            <textarea name="user_correction_comment" rows="3" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-800 dark:border-gray-600" placeholder="Explica qué corregiste..." required></textarea>
                                        </div>
                                    </div>

                                    <div class="flex justify-end pt-2">
                                        <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg shadow-sm transition-colors">
                                            Reenviar para Aprobación
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            @if(!$reimbursement->uuid || $reimbursement->folio === 'SIN-FACTURA')
                <div class="bg-orange-50 border border-orange-200 rounded-xl mb-6 p-4 flex items-center shadow-sm dark:bg-orange-900/20 dark:border-orange-800">
                    <svg class="w-6 h-6 text-orange-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    <div>
                        <h4 class="text-sm font-bold text-orange-800 dark:text-orange-300">Registro Manual Sin Factura (Sin XML)</h4>
                        <p class="text-orange-600 dark:text-orange-400 text-xs">Este gasto requiere validación manual, no tiene datos XML fiscales asociados.</p>
                    </div>
                </div>
            @endif

            <!-- Main Status Header -->
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-8">
                <div>
                    <div class="flex items-center space-x-3 mb-2">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200">
                            {{ ucfirst(str_replace('_', ' ', $reimbursement->type ?? 'Reembolso')) }}
                        </span>

                        <span class="text-xs text-gray-500 bg-white border border-gray-200 px-2.5 py-0.5 rounded-md dark:bg-gray-800 dark:border-gray-700">
                            Folio: {{ $reimbursement->folio ?? 'PENDIENTE' }}
                        </span>
                    </div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white leading-tight">
                        {{ $reimbursement->title ?? 'Gasto de ' . ucfirst($reimbursement->category) }}
                    </h1>
                </div>
                
                <div class="flex items-center gap-4">
                    <div class="text-right">
                        @php
                            $statusColors = [
                                'aprobado' => 'text-green-700 bg-green-50 ring-green-600/20',
                                'rechazado' => 'text-red-700 bg-red-50 ring-red-600/10',
                                'requiere_correccion' => 'text-yellow-800 bg-yellow-50 ring-yellow-600/20',
                                'pendiente' => 'text-gray-600 bg-gray-50 ring-gray-500/10',
                            ];
                            $defaultColor = 'text-indigo-700 bg-indigo-50 ring-indigo-600/20';
                            $statusClasses = $statusColors[$reimbursement->status] ?? $defaultColor;
                            
                            $statusLabel = match($reimbursement->status) {
                                'aprobado' => 'PAGADO',
                                'aprobado_cxp' => 'APROBADO SUBDIRECCIÓN',
                                'aprobado_direccion' => 'APROBADO DIRECCIÓN',
                                default => str_replace('_', ' ', strtoupper($reimbursement->status))
                            };
                        @endphp
                        <span class="inline-flex items-center rounded-md px-3 py-1 text-sm font-semibold {{ $statusClasses }} ring-1 ring-inset">
                            {{ $statusLabel }}
                        </span>
                    </div>
                    <a href="{{ route('reimbursements.index') }}" class="p-2 text-gray-400 hover:text-gray-600 bg-white border border-gray-200 rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-700 dark:hover:text-gray-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7 7-7m8 14l-7-7 7-7"></path></svg>
                    </a>
                </div>
            </div>

            <!-- Page Layout: Grid System -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <!-- Left Column: Details -->
                <div class="lg:col-span-2 space-y-6">
                    
                    <!-- Hero Section: Total & Subtotal -->
                    <div class="bg-indigo-600 rounded-xl overflow-hidden shadow-sm">
                        <div class="p-6 md:p-8 text-white flex flex-col md:flex-row md:justify-between md:items-center gap-6">
                            <div>
                                <p class="text-indigo-200 text-sm font-semibold mb-1">Monto Total</p>
                                <div class="text-4xl md:text-5xl font-bold tracking-tight">
                                    <span class="text-indigo-300 font-normal mr-1">$</span>{{ number_format($reimbursement->total, 2) }}
                                    <span class="text-xl ml-1 text-indigo-300">{{ $reimbursement->moneda }}</span>
                                </div>
                            </div>
                            <div class="flex gap-8 border-l border-indigo-500 pl-8">
                                <div>
                                    <p class="text-indigo-200 text-xs uppercase tracking-wider mb-1">Subtotal</p>
                                    <p class="text-lg font-semibold">${{ number_format($reimbursement->subtotal, 2) }}</p>
                                </div>
                                <div>
                                    <p class="text-indigo-200 text-xs uppercase tracking-wider mb-1">Impuestos</p>
                                    <p class="text-lg font-semibold">${{ number_format($reimbursement->total - $reimbursement->subtotal, 2) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Especificaciones Principales -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                        <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-base font-semibold leading-6 text-gray-900 dark:text-white">Detalles del Reembolso</h3>
                        </div>
                        <div class="border-t border-gray-100 dark:border-gray-800">
                            <dl class="divide-y divide-gray-100 dark:divide-gray-800">
                                @if($reimbursement->uuid)
                                <div class="px-6 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Folio Fiscal (UUID)</dt>
                                    <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0 dark:text-gray-200 font-mono">
                                        {{ $reimbursement->uuid }}
                                    </dd>
                                </div>
                                @endif
                                <div class="px-6 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Fecha del Gasto</dt>
                                    <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0 dark:text-gray-200">
                                        {{ \Carbon\Carbon::parse($reimbursement->fecha)->format('d \d\e M, Y') }} (Semana Fiscal #{{ $reimbursement->week }})
                                    </dd>
                                </div>
                                <div class="px-6 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Centro de Costos</dt>
                                    <dd class="mt-1 text-sm font-semibold text-gray-900 sm:col-span-2 sm:mt-0 dark:text-gray-200">{{ $reimbursement->costCenter->name ?? 'N/A' }}</dd>
                                </div>
                                <div class="px-6 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 bg-indigo-50/30 dark:bg-indigo-900/10">
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Destinatario del Pago</dt>
                                    <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0 dark:text-gray-200">
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold border-b-2 border-indigo-200 dark:border-indigo-800">{{ $reimbursement->payee->name ?? ($reimbursement->user->name ?? 'N/A') }}</span>
                                            @if($reimbursement->payee_id && $reimbursement->payee_id !== $reimbursement->user_id)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[9px] font-black bg-indigo-600 text-white uppercase tracking-widest">BENEFICIARIO CC</span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[9px] font-black bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300 uppercase tracking-widest">SOLICITANTE</span>
                                            @endif
                                        </div>
                                    </dd>
                                </div>
                                <div class="px-6 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Categoría</dt>
                                    <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0 dark:text-gray-200 capitalize">{{ $reimbursement->category ?? 'N/A' }}</dd>
                                </div>
                                <div class="px-6 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 bg-gray-50 dark:bg-gray-900/50">
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Emisor Comercial</dt>
                                    <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0 dark:text-gray-200">
                                        {{ $reimbursement->nombre_emisor }} <br>
                                        @if($reimbursement->rfc_emisor && $reimbursement->rfc_emisor !== 'N/A')
                                        <span class="text-xs text-gray-400 mt-1 font-mono">{{ $reimbursement->rfc_emisor }}</span>
                                        @endif
                                    </dd>
                                </div>
                                <div class="px-6 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 bg-gray-50 dark:bg-gray-900/50">
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Receptor (Entidad)</dt>
                                    <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0 dark:text-gray-200">
                                        {{ $reimbursement->nombre_receptor ?? 'Grupo INDI S.A.' }} 
                                        @if($reimbursement->rfc_receptor)
                                        <span class="text-xs text-gray-400 ml-2 font-mono">{{ $reimbursement->rfc_receptor }}</span>
                                        @endif
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <!-- Atributos CFDI -->
                    @if($reimbursement->uuid)
                    @php
                        $satMetodoPago = [
                            'PUE' => 'Pago en una sola exhibición',
                            'PPD' => 'Pago en parcialidades o diferido'
                        ];
                        
                        $satFormaPago = [
                            '01' => 'Efectivo', '02' => 'Cheque nominativo', '03' => 'Transferencia electrónica', 
                            '04' => 'Tarjeta de crédito', '05' => 'Monedero electrónico', '06' => 'Dinero electrónico', 
                            '08' => 'Vales de despensa', '12' => 'Dación en pago', '13' => 'Pago por subrogación', 
                            '14' => 'Pago por consignación', '15' => 'Condonación', '17' => 'Compensación', 
                            '23' => 'Novación', '24' => 'Confusión', '25' => 'Remisión de deuda', 
                            '26' => 'Prescripción o caducidad', '27' => 'A satisfacción del acreedor', 
                            '28' => 'Tarjeta de débito', '29' => 'Tarjeta de servicios', '30' => 'Aplicación de anticipos', 
                            '31' => 'Intermediario pagos', '99' => 'Por definir'
                        ];

                        $satUsoCfdi = [
                            'G01' => 'Adquisición de mercancias', 'G02' => 'Devoluciones, descuentos o bonificaciones', 
                            'G03' => 'Gastos en general', 'I01' => 'Construcciones', 'I02' => 'Mobiliario y equipo', 
                            'I03' => 'Equipo de transporte', 'I04' => 'Equipo de computo', 'I05' => 'Troqueles, moldes', 
                            'I06' => 'Comunicaciones telefónicas', 'I07' => 'Comunicaciones satelitales', 
                            'I08' => 'Otra maquinaria y equipo', 'D01' => 'Honorarios médicos', 'D02' => 'Gastos médicos por incapacidad', 
                            'D03' => 'Gastos funerales', 'D04' => 'Donativos', 'D05' => 'Intereses por créditos hipotecarios', 
                            'D06' => 'Aportaciones voluntarias SAR', 'D07' => 'Primas por seguros médicos', 
                            'D08' => 'Transportación escolar', 'D09' => 'Cuentas para el ahorro', 'D10' => 'Servicios educativos', 
                            'S01' => 'Sin efectos fiscales', 'CP01' => 'Pagos', 'CN01' => 'Nómina'
                        ];

                        $satRegimenFiscal = [
                            '601' => 'General de Ley Personas Morales', '603' => 'Personas Morales con Fines no Lucrativos', 
                            '605' => 'Sueldos y Salarios', '606' => 'Arrendamiento', '607' => 'Enajenación o Adquisición de Bienes', 
                            '608' => 'Demás ingresos', '609' => 'Consolidación', '610' => 'Residentes Extranjero', 
                            '611' => 'Ingresos por Dividendos', '612' => 'Personas Físicas Actividades Empresariales', 
                            '614' => 'Ingresos por intereses', '615' => 'Ingresos obtención de premios', 
                            '616' => 'Sin obligaciones fiscales', '620' => 'Sociedades Cooperativas', 
                            '621' => 'Incorporación Fiscal', '622' => 'Actividades Agrícolas/Ganaderas', 
                            '623' => 'Opcional para Grupos', '624' => 'Coordinados', '625' => 'Plataformas Tecnológicas', 
                            '626' => 'Régimen Simplificado de Confianza'
                        ];

                        $mp = $reimbursement->metodo_pago;
                        $mpDesc = $satMetodoPago[$mp] ?? '';

                        $fp = $reimbursement->forma_pago;
                        $fpDesc = $satFormaPago[$fp] ?? '';

                        $uso = $reimbursement->uso_cfdi;
                        $usoDesc = $satUsoCfdi[$uso] ?? '';

                        $reg = $reimbursement->regimen_fiscal_emisor;
                        $regDesc = $satRegimenFiscal[$reg] ?? '';
                    @endphp
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <!-- Prominent Validation Header (Semaphore) -->
                        @if($reimbursement->validation_data)
                            @php
                                $uM = $reimbursement->validation_data['uuid_match'] ?? false;
                                $tM = $reimbursement->validation_data['total_match'] ?? false;
                                $statusColor = ($uM && $tM) ? 'emerald' : (($uM) ? 'amber' : 'rose');
                                $statusLabel = ($uM && $tM) ? 'FACTURA VALIDADA' : (($uM) ? 'ADVERTENCIA EN MONTOS' : 'REVISIÓN DE SEGURIDAD REQUERIDA');
                                $bgBanner = ($uM && $tM) ? 'bg-emerald-600' : (($uM) ? 'bg-amber-500' : 'bg-rose-600');
                                $icon = ($uM && $tM) ? 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' : (($uM) ? 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z' : 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z');
                            @endphp
                            <div class="{{ $bgBanner }} px-6 py-4 flex flex-col md:flex-row justify-between items-center gap-4 shadow-lg text-white">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-white/20 rounded-lg backdrop-blur-sm">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="{{ $icon }}"></path></svg>
                                    </div>
                                    <div>
                                        <h4 class="text-xs font-black uppercase tracking-[0.2em] opacity-80 mb-0.5">Semáforo de Validación SAT</h4>
                                        <p class="text-lg font-black tracking-tight leading-none">{{ $statusLabel }}</p>
                                    </div>
                                </div>
                                <div class="flex gap-4">
                                    <div class="bg-black/20 backdrop-blur-md px-4 py-2 rounded-xl flex items-center gap-3 border border-white/10">
                                        <div class="flex h-3 w-3">
                                            <span class="animate-ping absolute inline-flex h-3 w-3 rounded-full {{ $uM ? 'bg-emerald-300' : 'bg-rose-300' }} opacity-75"></span>
                                            <span class="relative inline-flex rounded-full h-3 w-3 {{ $uM ? 'bg-emerald-400' : 'bg-rose-400' }}"></span>
                                        </div>
                                        <span class="text-[10px] font-black uppercase tracking-widest leading-none">UUID: {{ $uM ? 'OK' : 'ERROR' }}</span>
                                    </div>
                                    <div class="bg-black/20 backdrop-blur-md px-4 py-2 rounded-xl flex items-center gap-3 border border-white/10">
                                        <div class="flex h-3 w-3">
                                            <span class="animate-ping absolute inline-flex h-3 w-3 rounded-full {{ $tM ? 'bg-emerald-300' : ($uM ? 'bg-amber-300' : 'bg-rose-300') }} opacity-75"></span>
                                            <span class="relative inline-flex rounded-full h-3 w-3 {{ $tM ? 'bg-emerald-400' : ($uM ? 'bg-amber-400' : 'bg-rose-400') }}"></span>
                                        </div>
                                        <span class="text-[10px] font-black uppercase tracking-widest leading-none">MONTO: {{ $tM ? 'OK' : 'DIFF' }}</span>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/20">
                                <h3 class="text-xs font-black text-gray-400 uppercase tracking-widest dark:text-gray-500">Atributos del CFDI</h3>
                            </div>
                        @endif
                        <div class="p-6 grid grid-cols-2 md:grid-cols-4 gap-6">
                            <div>
                                <p class="text-[10px] text-gray-400 uppercase tracking-wider mb-1">Método de Pago</p>
                                <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $mp ?? 'N/A' }}</p>
                                @if($mpDesc)<p class="text-[11px] font-medium text-indigo-600 dark:text-indigo-400 mt-0.5 leading-tight">{{ $mpDesc }}</p>@endif
                            </div>
                            <div>
                                <p class="text-[10px] text-gray-400 uppercase tracking-wider mb-1">Forma de Pago</p>
                                <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $fp ?? 'N/A' }}</p>
                                @if($fpDesc)<p class="text-[11px] font-medium text-indigo-600 dark:text-indigo-400 mt-0.5 leading-tight">{{ $fpDesc }}</p>@endif
                            </div>
                            <div>
                                <p class="text-[10px] text-gray-400 uppercase tracking-wider mb-1">Uso de CFDI</p>
                                <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $uso ?? 'N/A' }}</p>
                                @if($usoDesc)<p class="text-[11px] font-medium text-indigo-600 dark:text-indigo-400 mt-0.5 leading-tight">{{ $usoDesc }}</p>@endif
                            </div>
                            <div>
                                <p class="text-[10px] text-gray-400 uppercase tracking-wider mb-1">CP Expedición</p>
                                <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $reimbursement->lugar_expedicion ?? 'S/N' }}</p>
                            </div>
                            
                            <div class="col-span-2 md:col-span-4 border-t border-gray-100 pt-4 dark:border-gray-700">
                                <p class="text-[10px] text-gray-400 uppercase tracking-wider mb-1">Régimen Fiscal Emisor</p>
                                <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $reg ?? 'S/N' }}</p>
                                @if($regDesc)<p class="text-[11px] font-medium text-emerald-600 dark:text-emerald-400 mt-0.5 leading-tight">{{ $regDesc }}</p>@endif
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Expediente Digital -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Documentación</h3>
                        </div>
                        <div class="p-6 flex flex-wrap gap-4">
                            @if($reimbursement->xml_path)
                            <div class="flex items-center p-3 border border-gray-200 rounded-lg flex-1 min-w-[200px] dark:border-gray-700">
                                <div class="p-2 bg-indigo-50 text-indigo-600 rounded-lg mr-3 dark:bg-indigo-900 dark:text-indigo-300">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">Factura XML</p>
                                    <a href="{{ route('reimbursements.view_file', ['reimbursement' => $reimbursement, 'type' => 'xml']) }}" target="_blank" class="text-xs text-indigo-600 hover:text-indigo-800 dark:text-indigo-400">Ver archivo</a>
                                </div>
                                <a href="{{ route('reimbursements.download_file', ['reimbursement' => $reimbursement, 'type' => 'xml']) }}" class="p-2 text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg></a>
                            </div>
                            @endif

                            @if($reimbursement->pdf_path)
                            <div class="flex items-center p-3 border border-gray-200 rounded-lg flex-1 min-w-[200px] dark:border-gray-700">
                                <div class="p-2 bg-orange-50 text-orange-600 rounded-lg mr-3 dark:bg-orange-900 dark:text-orange-300">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $reimbursement->uuid ? 'PDF (Representación)' : 'Comprobante' }}</p>
                                    <a href="{{ route('reimbursements.view_file', ['reimbursement' => $reimbursement, 'type' => 'pdf']) }}" target="_blank" class="text-xs text-indigo-600 hover:text-indigo-800 dark:text-indigo-400">Ver archivo</a>
                                </div>
                                <a href="{{ route('reimbursements.download_file', ['reimbursement' => $reimbursement, 'type' => 'pdf']) }}" class="p-2 text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg></a>
                            </div>
                            @endif

                            @if($reimbursement->ticket_path)
                            <div class="flex items-center p-3 border border-gray-200 rounded-lg flex-1 min-w-[200px] dark:border-gray-700">
                                <div class="p-2 bg-emerald-50 text-emerald-600 rounded-lg mr-3 dark:bg-emerald-900 dark:text-emerald-300">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path></svg>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">Evidencia Adicional</p>
                                    <a href="{{ route('reimbursements.view_file', ['reimbursement' => $reimbursement, 'type' => 'ticket']) }}" target="_blank" class="text-xs text-indigo-600 hover:text-indigo-800 dark:text-indigo-400">Ver archivo</a>
                                </div>
                                <a href="{{ route('reimbursements.download_file', ['reimbursement' => $reimbursement, 'type' => 'ticket']) }}" class="p-2 text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg></a>
                            </div>
                            @endif
                        </div>


                    </div>

                    <!-- Viaje Info -->
                    @if($reimbursement->type === 'viaje')
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 p-6 dark:border-gray-700">
                            <h3 class="text-sm font-semibold text-gray-900 border-b border-gray-100 pb-3 mb-4 dark:text-white dark:border-gray-700">Datos del Viaje</h3>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                                <div><p class="text-xs text-gray-500">Destino</p><p class="text-sm font-medium">{{ $reimbursement->trip_destination }}</p></div>
                                <div><p class="text-xs text-gray-500">Duración</p><p class="text-sm font-medium">{{ $reimbursement->trip_nights }} Noches</p></div>
                                <div class="col-span-2"><p class="text-xs text-gray-500">Cronograma</p><p class="text-sm font-medium">{{ \Carbon\Carbon::parse($reimbursement->trip_start_date)->format('d M') }} — {{ \Carbon\Carbon::parse($reimbursement->trip_end_date)->format('d M, Y') }}</p></div>
                            </div>
                        </div>
                    @endif

                    <!-- Subgastos Vinculados -->
                    @if($reimbursement->children->count() > 0)
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 overflow-hidden dark:border-gray-700">
                            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Gastos Vinculados al Viaje</h3>
                            </div>
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                                <thead class="bg-gray-50 dark:bg-gray-900/50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Concepto</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Monto</th>
                                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acción</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                                    @foreach($reimbursement->children as $child)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-200 capitalize">{{ $child->type }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ \Carbon\Carbon::parse($child->fecha)->format('d/m/Y') }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-indigo-600 text-right dark:text-indigo-400">${{ number_format($child->total, 2) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <a href="{{ route('reimbursements.show', $child) }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400">Detalles</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                </div>

                <!-- Right Column: Sidebar -->
                <div class="space-y-6">
                    
                    <!-- Acción de Aprobación -->
                    @php
                        $user = auth()->user();
                        $cc = $reimbursement->costCenter;
                        
                        $canApproveDirector = ($user->isAdmin() || ($user->isDirector() && $user->id === $cc->director_id)) && $reimbursement->status === 'pendiente';
                        $canApproveControl = ($user->isAdmin() || ($user->isControlObra() && $user->id === $cc->control_obra_id)) && $reimbursement->status === 'aprobado_director';
                        $canApproveExecutive = ($user->isAdmin() || ($user->isExecutiveDirector() && $user->id === $cc->director_ejecutivo_id)) && $reimbursement->status === 'aprobado_control';
                        $canApproveCXP = ($user->isAdmin() || $user->isCxp()) && $reimbursement->status === 'aprobado_ejecutivo';
                        $canApproveDireccion = ($user->isAdmin() || $user->isDireccion()) && $reimbursement->status === 'aprobado_cxp';
                        $canApproveTreasury = ($user->isAdmin() || $user->isTreasury()) && $reimbursement->status === 'aprobado_direccion';
                        
                        $canApproveAny = !$user->isAdminView() && ($canApproveDirector || $canApproveControl || $canApproveExecutive || $canApproveCXP || $canApproveDireccion || $canApproveTreasury);
                    @endphp

                    @if($canApproveAny)
                    <div class="bg-indigo-600 rounded-xl p-6 text-white shadow-sm">
                        <h4 class="font-semibold mb-4 text-indigo-50">Acciones Disponibles</h4>
                        <div class="space-y-3">
                            <form action="{{ route('reimbursements.update', $reimbursement->id) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="status" value="aprobado">
                                <button type="submit" class="w-full flex justify-center items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-indigo-700 bg-white hover:bg-indigo-50 focus:outline-none">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    Aprobar Solicitud
                                </button>
                            </form>
                            <button type="button" x-data @click="$dispatch('open-rejection-modal')" class="w-full flex justify-center items-center px-4 py-2 border border-indigo-400 shadow-sm text-sm font-medium rounded-md text-white bg-indigo-700 hover:bg-indigo-800 hover:text-red-300 hover:border-red-400 focus:outline-none transition-colors">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                Rechazar o Devolver
                            </button>
                        </div>
                    </div>
                    @endif

                    <!-- Stepper Log -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 p-6 dark:border-gray-700">
                        <h3 class="text-sm font-semibold text-gray-900 border-b border-gray-100 pb-3 mb-6 dark:text-white dark:border-gray-700">Flujo de Autorizaciones</h3>
                        
                        <div class="relative">
                            <!-- Track -->
                            <div class="absolute left-4 top-0 bottom-0 w-px bg-gray-200 dark:bg-gray-700"></div>

                            @php
                                $steps = [
                                    ['label' => 'Revisión N1 (Director)', 'id' => $reimbursement->approved_by_director_id, 'name' => $reimbursement->directorApprover->name ?? 'Por asignar', 'at' => $reimbursement->approved_by_director_at],
                                    ['label' => 'Control de Obra', 'id' => $reimbursement->approved_by_control_id, 'name' => $reimbursement->controlApprover->name ?? 'Por asignar', 'at' => $reimbursement->approved_by_control_at],
                                    ['label' => 'Dirección Ejecutiva', 'id' => $reimbursement->approved_by_executive_id, 'name' => $reimbursement->executiveApprover->name ?? 'Por asignar', 'at' => $reimbursement->approved_by_executive_at],
                                    ['label' => 'Subdirección CXP', 'id' => $reimbursement->approved_by_cxp_id, 'name' => $reimbursement->cxpApprover->name ?? 'Por asignar', 'at' => $reimbursement->approved_by_cxp_at],
                                    ['label' => 'Dirección Gral.', 'id' => $reimbursement->approved_by_direccion_id, 'name' => $reimbursement->direccionApprover->name ?? 'Por asignar', 'at' => $reimbursement->approved_by_direccion_at],
                                    ['label' => 'Tesorería y Pagos', 'id' => $reimbursement->approved_by_treasury_id, 'name' => $reimbursement->treasuryApprover->name ?? 'Por asignar', 'at' => $reimbursement->approved_by_treasury_at],
                                ];
                            @endphp

                            @foreach($steps as $index => $step)
                                @php
                                    $isCompleted = (bool)$step['id'];
                                    $isCurrent = false;
                                    if (!$isCompleted) {
                                        if ($index === 0 && $reimbursement->status === 'pendiente') $isCurrent = true;
                                        elseif ($index === 1 && $reimbursement->status === 'aprobado_director') $isCurrent = true;
                                        elseif ($index === 2 && $reimbursement->status === 'aprobado_control') $isCurrent = true;
                                        elseif ($index === 3 && $reimbursement->status === 'aprobado_ejecutivo') $isCurrent = true;
                                        elseif ($index === 4 && $reimbursement->status === 'aprobado_cxp') $isCurrent = true;
                                        elseif ($index === 5 && $reimbursement->status === 'aprobado_direccion') $isCurrent = true;
                                    }
                                @endphp
                                <div class="relative flex gap-4 pb-6 last:pb-0">
                                    <div class="relative z-10 flex h-8 w-8 items-center justify-center rounded-full bg-white ring-2 ring-white dark:bg-gray-800 dark:ring-gray-800">
                                        @if($isCompleted)
                                            <div class="h-6 w-6 rounded-full bg-indigo-600 flex items-center justify-center"><svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg></div>
                                        @elseif($isCurrent)
                                            <div class="h-6 w-6 rounded-full border-2 border-indigo-600 bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center"><span class="h-2 w-2 rounded-full bg-indigo-600 animate-pulse"></span></div>
                                        @else
                                            <div class="h-6 w-6 rounded-full bg-gray-100 border border-gray-300 dark:bg-gray-700 dark:border-gray-600"></div>
                                        @endif
                                    </div>
                                    <div class="pt-1 w-full">
                                        <p class="text-sm font-semibold {{ $isCompleted ? 'text-gray-900 dark:text-white' : ($isCurrent ? 'text-indigo-700 dark:text-indigo-400' : 'text-gray-500 dark:text-gray-400') }}">{{ $step['label'] }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $step['name'] }}</p>
                                        @if($isCompleted && $step['at'])
                                            <p class="text-[10px] text-gray-400 mt-0.5">{{ $step['at']->format('d/m/Y H:i') }}</p>
                                        @elseif($isCurrent)
                                            <span class="inline-flex items-center px-2 py-0.5 mt-1 rounded text-[10px] font-medium bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200">ACCION REQUERIDA</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Bitácora Notes -->
                    @if($reimbursement->observaciones)
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 p-6 dark:border-gray-700">
                        <h3 class="text-sm font-semibold text-gray-900 border-b border-gray-100 pb-3 mb-4 flex items-center dark:text-white dark:border-gray-700">
                            <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
                            Historial de Comentarios
                        </h3>
                        <ul class="space-y-4">
                            @foreach(array_reverse(explode("\n", $reimbursement->observaciones)) as $observation)
                                @if(trim($observation))
                                    @php
                                        preg_match('/el \d{2}\/\d{2}\/\d{4} \d{2}:\d{2}/', $observation, $matches);
                                        $timestamp = $matches[0] ?? '';
                                        $content = trim(str_replace($timestamp, '', $observation));
                                        $isErr = str_contains($content, 'RECHAZADO') || str_contains($content, 'REQUIERE CORRECCIÓN');
                                    @endphp
                                    <li class="bg-gray-50 rounded-lg p-3 text-sm dark:bg-gray-900/50">
                                        <p class="text-gray-700 dark:text-gray-300 {{ $isErr ? 'text-red-700 dark:text-red-400 font-medium' : '' }}">{{ $content }}</p>
                                        <p class="text-xs text-gray-400 mt-2">{{ str_replace('el ', '', $timestamp) }}</p>
                                    </li>
                                @endif
                            @endforeach
                        </ul>
                    </div>
                    @endif

                </div>
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

                    const extension = this.files[0].name.split('.').pop().toLowerCase();
                    const hasUuid = @json(!empty($reimbursement->uuid));
                    const allowedExtensions = hasUuid ? ['pdf'] : ['pdf', 'jpg', 'jpeg', 'png', 'txt'];

                    if (!allowedExtensions.includes(extension)) {
                        alert(`Archivo Inválido. Solo se acepta: ${allowedExtensions.join(', ')}`);
                        this.value = '';
                        validationResult.classList.add('hidden');
                        return;
                    }

                    validationResult.classList.remove('hidden');
                    validationResult.innerHTML = '<span class="text-xs text-gray-500">Validando documento adjunto...</span>';

                    const formData = new FormData();
                    formData.append('pdf_file', this.files[0]);
                    formData.append('_token', '{{ csrf_token() }}');

                    fetch('{{ route("reimbursements.validate_pdf_correction", $reimbursement->id) }}', {
                        method: 'POST',
                        body: formData,
                        headers: { 'Accept': 'application/json' }
                    })
                    .then(response => {
                        @if(empty($reimbursement->uuid))
                            validationResult.innerHTML = `<span class="text-xs text-indigo-600">Registro manual. No requiere comprobación de sellos XML.</span>`;
                            return null;
                        @endif
                        return response.json();
                    })
                    .then(data => {
                        if (!data) return;
                        if (data.error) {
                            validationResult.innerHTML = `<span class="text-xs text-red-600">Error: ${data.error}</span>`;
                        } else if (data.uuid_match) {
                            validationResult.innerHTML = `<span class="text-xs text-green-600">Validación exitosa (Coincidencia UUID).</span>`;
                        } else {
                            validationResult.innerHTML = `<span class="text-xs text-orange-600">Alerta manual: ${data.message}</span>`;
                        }
                    })
                    .catch(error => {
                        validationResult.innerHTML = `<span class="text-xs text-red-600">Error al validar.</span>`;
                    });
                });
            }
        });
    </script>
    @endpush
</x-app-layout>

<!-- Rejection Modal -->
<div x-data="{ open: false, reasons: [
    'Falta comprobante fiscal (XML/PDF)', 'El monto no coincide con la factura',
    'Gasto no autorizado', 'Fuera de política de viáticos', 'Duplicado de solicitud',
    'Error en centro de costos', 'Falta justificación detallada', 'Otro'
] }" 
     @open-rejection-modal.window="open = true" 
     x-show="open" 
     class="fixed z-50 inset-0 overflow-y-auto" 
     style="display: none;">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="open = false" aria-hidden="true"></div>
        <div class="inline-block bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:max-w-lg sm:w-full dark:bg-gray-800">
            <form action="{{ route('reimbursements.update', $reimbursement->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">Rechazar Solicitud</h3>
                            <div class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                <label class="block mt-4 mb-1">Razón Principal</label>
                                <select name="rejection_reason" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 mb-4" required>
                                    <option value="">Seleccione una razón</option>
                                    <template x-for="r in reasons"><option :value="r" x-text="r"></option></template>
                                </select>
                                
                                <label class="block font-medium text-gray-700 dark:text-gray-300 mt-4 mb-2">Acción de Rechazo</label>
                                <div class="space-y-2">
                                    <div class="flex items-start">
                                        <input id="rt1" name="status" type="radio" value="requiere_correccion" class="mt-1 focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300" required>
                                        <label for="rt1" class="ml-3 block text-sm text-gray-700 dark:text-gray-300">Devolver al usuario para <b>corrección</b>.</label>
                                    </div>
                                    <div class="flex items-start">
                                        <input id="rt2" name="status" type="radio" value="rechazado" class="mt-1 focus:ring-red-500 h-4 w-4 text-red-600 border-gray-300">
                                        <label for="rt2" class="ml-3 block text-sm text-gray-700 dark:text-gray-300"><b>Rechazo definitivo</b> y contable.</label>
                                    </div>
                                </div>
                                <label class="block mt-4 mb-1">Comentario Libre</label>
                                <textarea name="rejection_comment" rows="3" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse dark:bg-gray-900/50">
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">Confirmar Acción</button>
                    <button type="button" @click="open = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>
