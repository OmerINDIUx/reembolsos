<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Editar ') . $travelEvent->name }}
        </h2>
    </x-slot>

    <div class="py-12 relative">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-8 flex justify-between items-center">
                <a href="{{ route('travel_events.index') }}" class="inline-flex items-center text-sm font-bold text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    VOLVER A VIAJES Y EVENTOS
                </a>
            </div>
            
            <form action="{{ route('travel_events.update', $travelEvent) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="bg-white dark:bg-gray-800 shadow-xl rounded-3xl mb-10 overflow-hidden border border-gray-100 dark:border-gray-700 p-8 md:p-10 space-y-8">
                    
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

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <!-- Centro de Costos Relacionado -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Centro de Costos Relacionado *</label>
                            <select name="cost_center_id" class="w-full border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-xl shadow-sm focus:ring-indigo-500 text-sm py-3" required>
                                @foreach($costCenters as $cc)
                                    <option value="{{ $cc->id }}" {{ old('cost_center_id', $travelEvent->cost_center_id) == $cc->id ? 'selected' : '' }}>{{ $cc->name }}</option>
                                @endforeach
                            </select>
                            
                        </div>

                        <!-- Nombre del Evento -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Nombre del Viaje / Evento *</label>
                            <input type="text" name="name" value="{{ old('name', $travelEvent->name) }}" class="w-full border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-xl shadow-sm focus:ring-indigo-500 text-sm py-3 uppercase" required>
                            
                        </div>

                        <!-- Código -->
                        <div>
                            <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Código Interno *</label>
                            <input type="text" name="code" value="{{ old('code', $travelEvent->code) }}" class="w-full border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-xl shadow-sm focus:ring-indigo-500 text-sm py-3 text-indigo-600 uppercase" required>
                            
                        </div>

                        <!-- Tipo de Viaje -->
                        <div>
                            <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Tipo de Viaje *</label>
                            <select name="trip_type" class="w-full border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-xl shadow-sm focus:ring-indigo-500 text-sm py-3" required>
                                <option value="nacional" {{ old('trip_type', $travelEvent->trip_type) == 'nacional' ? 'selected' : '' }}>Nacional</option>
                                <option value="internacional" {{ old('trip_type', $travelEvent->trip_type) == 'internacional' ? 'selected' : '' }}>Internacional</option>
                            </select>
                            
                        </div>

                        <!-- Director Responsable -->
                        <div>
                            <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Aprobador Principal *</label>
                            <select name="director_id" class="w-full border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-xl shadow-sm focus:ring-indigo-500 text-sm py-3" required>
                                @foreach($directors as $director)
                                    <option value="{{ $director->id }}" {{ old('director_id', $travelEvent->director_id) == $director->id ? 'selected' : '' }}>{{ $director->name }}</option>
                                @endforeach
                            </select>
                            
                        </div>

                        <!-- Evidencia de Aprobación -->
                        <div>
                            <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Evidencia de Aprobación (PDF/JPG)</label>
                            <input type="file" name="approval_evidence" class="block w-full text-xs text-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl cursor-pointer bg-gray-50 dark:bg-gray-900 dark:text-gray-400 focus:outline-none p-3" accept=".pdf,.jpg,.jpeg,.png">
                            @if($travelEvent->approval_evidence_path)
                                <div class="mt-2 flex items-center space-x-2">
                                    <a href="{{ Storage::url($travelEvent->approval_evidence_path) }}" target="_blank" class="text-[10px] font-black text-indigo-600 hover:text-indigo-800 uppercase tracking-widest bg-indigo-50 px-3 py-1 rounded-lg transition-all">
                                        VER EVIDENCIA ACTUAL
                                    </a>
                                </div>
                            @endif
                        </div>

                        <!-- Estatus -->
                        <div>
                            <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Estatus del Registro *</label>
                            <select name="status" class="w-full border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-xl shadow-sm focus:ring-indigo-500 text-sm py-3 uppercase" required>
                                <option value="active" {{ old('status', $travelEvent->status) == 'active' ? 'selected' : '' }}>Activo (En Proceso)</option>
                                <option value="completed" {{ old('status', $travelEvent->status) == 'completed' ? 'selected' : '' }}>Completado (Cerrado)</option>
                                <option value="cancelled" {{ old('status', $travelEvent->status) == 'cancelled' ? 'selected' : '' }}>Cancelado</option>
                            </select>
                            
                        </div>

                        <!-- Ubicación -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Ubicación / Destino</label>
                            <input type="text" name="location" value="{{ old('location', $travelEvent->location) }}" class="w-full border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-xl shadow-sm focus:ring-indigo-500 text-sm py-3 uppercase">
                            
                        </div>

                        <!-- Usuarios Autorizados (Misma lógica que CostCenter) -->
                        <div class="md:col-span-2" x-data="{ 
                            authorizedUsers: {{ old('user_ids') ? json_encode(array_map(fn($id) => ['user_id' => $id], old('user_ids'))) : $travelEvent->participants->map(fn($u) => ['user_id' => $u->id])->toJson() }},
                            addUser() {
                                this.authorizedUsers.push({ user_id: '' });
                            },
                            removeUser(index) {
                                this.authorizedUsers.splice(index, 1);
                            }
                        }">
                            <div class="flex justify-between items-center mb-4">
                                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300">Usuarios Autorizados para Registros *</label>
                                <button type="button" @click="addUser()" class="inline-flex items-center px-4 py-2 bg-indigo-50 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 rounded-xl text-[10px] font-black uppercase tracking-tight hover:bg-indigo-100 transition-colors">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                    Añadir Usuario
                                </button>
                            </div>
                                                 <div class="space-y-3">
                                <template x-for="(user, index) in authorizedUsers" :key="index">
                                    <div class="flex items-center gap-4 bg-gray-50/50 dark:bg-gray-900/50 p-3 rounded-2xl border border-gray-100 dark:border-gray-700 group hover:border-indigo-100 transition-all">
                                        <div class="flex-1">
                                            <select :name="'user_ids[]'" x-model="user.user_id" class="w-full border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-xl shadow-sm focus:ring-indigo-500 text-sm py-3" required>
                                                <option value="">Selecciona un usuario...</option>
                                                @foreach($users as $u)
                                                    <option value="{{ $u->id }}" :selected="user.user_id == {{ $u->id }}">{{ strtoupper($u->name) }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <button type="button" @click="removeUser(index)" class="p-2 text-gray-300 hover:text-red-500 transition-colors">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </div>
                                </template>

                                @error('user_ids')
                                    <p class="text-[10px] text-red-500 font-black uppercase tracking-widest mt-2">{{ $message }}</p>
                                @enderror

                                <div x-show="authorizedUsers.length === 0" class="text-center py-10 border-2 border-dashed border-gray-100 dark:border-gray-800 rounded-3xl transition-all">
                                    <p class="text-[10px] font-black uppercase text-gray-350 tracking-[0.2em]">No hay colaboradores autorizados</p>
                                </div>
                            </div>
 </div>
                            <p class="mt-3 text-[10px] text-gray-400 font-medium italic">Modifica el acceso añadiendo o eliminando usuarios de esta lista.</p>
                        </div>

                        <!-- Fecha Inicio -->
                        <div>
                            <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Vigencia: Inicio</label>
                            <input type="date" name="start_date" value="{{ old('start_date', $travelEvent->start_date) }}" class="w-full border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-xl shadow-sm focus:ring-indigo-500 text-sm py-3">
                            
                        </div>

                        <!-- Fecha Fin -->
                        <div>
                            <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Vigencia: Fin</label>
                            <input type="date" name="end_date" value="{{ old('end_date', $travelEvent->end_date) }}" class="w-full border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-xl shadow-sm focus:ring-indigo-500 text-sm py-3">
                            
                        </div>

                        <!-- Descripción -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Descripción / Justificación</label>
                            <textarea name="description" rows="4" class="w-full border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-2xl shadow-sm focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all p-6 font-medium text-sm">{{ old('description', $travelEvent->description) }}</textarea>
                            
                        </div>
                    </div>

                    <div class="flex items-center justify-end pt-8">
                        <button type="submit" class="inline-flex items-center px-12 py-5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-black uppercase tracking-widest rounded-2xl transition-all shadow-xl shadow-indigo-500/20 transform hover:scale-105 active:scale-95">
                            Guardar Cambios &rarr;
                        </button>
                    </div>
                </div>
            </form>
            
        </div>
    </div>
</x-app-layout>
