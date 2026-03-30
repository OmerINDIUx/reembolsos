<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h2 class="font-black text-3xl text-gray-900 dark:text-white leading-tight uppercase tracking-tighter">
                    Nuevo Registro (Viaje o Evento)
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 font-medium">Asegúrate de completar todos los campos obligatorios para generar tu código interno.</p>
            </div>
            
            <a href="{{ route('travel_events.index') }}" class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-black uppercase tracking-widest text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition-all shadow-sm">
                &larr; Volver
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            
            <form action="{{ route('travel_events.store') }}" method="POST">
                @csrf
                <div class="bg-white dark:bg-gray-800 rounded-[2.5rem] shadow-sm border border-gray-100 dark:border-gray-700 p-10 space-y-8">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <!-- Centro de Costos Relacionado -->
                        <div class="md:col-span-2">
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Centro de Costos Relacionado *</label>
                            <select name="cost_center_id" class="w-full border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-2xl shadow-sm focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all py-4 px-6 font-bold" required>
                                <option value="">Selecciona el centro de costos que cubrirá los gastos...</option>
                                @foreach($costCenters as $cc)
                                    <option value="{{ $cc->id }}">{{ $cc->name }}</option>
                                @endforeach
                            </select>
                            <p class="mt-2 text-[10px] text-gray-400 font-medium italic">Nota: La línea de aprobación del reembolso se basará en este centro de costos.</p>
                        </div>

                        <!-- Nombre del Evento -->
                        <div class="md:col-span-2">
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Nombre del Viaje / Evento *</label>
                            <input type="text" name="name" class="w-full border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-2xl shadow-sm focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all py-4 px-6 font-bold truncate uppercase" required placeholder="Ej: ASAMBLEA SINDICAL 2026">
                        </div>

                        <!-- Código Sugerido -->
                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Código Interno *</label>
                            <input type="text" name="code" class="w-full border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-2xl shadow-sm focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all py-4 px-6 font-bold text-indigo-600 uppercase" required placeholder="Ej: VIAJE-QRO-01">
                        </div>

                        <!-- Director Responsable -->
                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Director Autorizador *</label>
                            <select name="director_id" class="w-full border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-2xl shadow-sm focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all py-4 px-6 font-bold" required>
                                <option value="">Selecciona al director...</option>
                                @foreach($directors as $director)
                                    <option value="{{ $director->id }}">{{ $director->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Ubicación -->
                        <div class="md:col-span-2">
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Ubicación / Destino</label>
                            <input type="text" name="location" class="w-full border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-2xl shadow-sm focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all py-4 px-6 font-bold uppercase" placeholder="Ej: CIUDAD DE MÉXICO">
                        </div>

                        <!-- Usuarios Autorizados -->
                        <div class="md:col-span-2" x-data="{ 
                            search: '',
                            users: {{ $users->map(fn($u) => ['id' => $u->id, 'name' => strtoupper($u->name)])->toJson() }},
                            selected: [],
                            isOpen: false,
                            get filteredUsers() {
                                if (this.search === '') return this.users.filter(u => !this.selected.some(s => s.id === u.id));
                                return this.users.filter(u => 
                                    !this.selected.some(s => s.id === u.id) && 
                                    u.name.includes(this.search.toUpperCase())
                                );
                            },
                            toggleUser(user) {
                                if (this.selected.some(s => s.id === user.id)) {
                                    this.selected = this.selected.filter(s => s.id !== user.id);
                                } else {
                                    this.selected.push(user);
                                }
                                this.search = '';
                            }
                        }">
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Usuarios Autorizados para Registros *</label>
                            
                            <div class="relative">
                                <!-- Multi-select Trigger / Selected Tags -->
                                <div class="w-full border-gray-200 dark:border-gray-700 dark:bg-gray-900 rounded-2xl shadow-sm focus-within:ring-4 focus-within:ring-indigo-500/10 focus-within:border-indigo-500 transition-all p-2 flex flex-wrap gap-2 min-h-[4rem] cursor-text" @click="$refs.searchInput.focus(); isOpen = true">
                                    <template x-for="user in selected" :key="user.id">
                                        <div class="inline-flex items-center bg-indigo-50 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 px-3 py-1 rounded-xl text-[10px] font-black uppercase tracking-tight">
                                            <span x-text="user.name"></span>
                                            <button type="button" @click.stop="toggleUser(user)" class="ml-2 hover:text-indigo-900 dark:hover:text-white">&times;</button>
                                            <input type="hidden" name="user_ids[]" :value="user.id">
                                        </div>
                                    </template>
                                    
                                    <input 
                                        x-ref="searchInput"
                                        type="text" 
                                        x-model="search" 
                                        @keydown.escape="isOpen = false"
                                        @click.away="isOpen = false"
                                        @focus="isOpen = true"
                                        placeholder="Buscar y agregar usuarios..."
                                        class="flex-grow border-none focus:ring-0 bg-transparent text-sm font-bold uppercase py-2 px-3 placeholder-gray-400 min-w-[150px]">
                                </div>

                                <!-- Dropdown List -->
                                <div x-show="isOpen && filteredUsers.length > 0" 
                                     class="absolute z-50 w-full mt-2 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 shadow-2xl rounded-2xl max-h-60 overflow-y-auto transform origin-top transition-all"
                                     x-transition:enter="transition ease-out duration-100"
                                     x-transition:enter-start="opacity-0 scale-95"
                                     x-transition:enter-end="opacity-100 scale-100">
                                    <template x-for="user in filteredUsers" :key="user.id">
                                        <div @click="toggleUser(user)" 
                                             class="px-6 py-4 hover:bg-gray-50 dark:hover:bg-gray-900/50 cursor-pointer flex items-center justify-between group">
                                            <span class="text-xs font-bold text-gray-700 dark:text-gray-300 group-hover:text-indigo-600 transition-colors uppercase" x-text="user.name"></span>
                                            <svg class="w-4 h-4 text-gray-300 group-hover:text-indigo-500 opacity-0 group-hover:opacity-100 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <p class="mt-3 text-[10px] text-gray-400 font-medium italic">Empieza a escribir para filtrar por nombre. Solo los seleccionados podrán cargar reembolsos.</p>
                        </div>

                        <!-- Fecha Inicio -->
                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Vigencia: Fecha Inicio</label>
                            <input type="date" name="start_date" class="w-full border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-2xl shadow-sm focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all py-4 px-6 font-bold">
                        </div>

                        <!-- Fecha Fin -->
                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Vigencia: Fecha Fin</label>
                            <input type="date" name="end_date" class="w-full border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-2xl shadow-sm focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all py-4 px-6 font-bold">
                        </div>

                        <!-- Descripción -->
                        <div class="md:col-span-2">
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Descripción / Justificación</label>
                            <textarea name="description" rows="5" class="w-full border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-2xl shadow-sm focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all p-6 font-medium text-sm" placeholder="Escribe aquí el motivo o detalles adicionales..."></textarea>
                        </div>
                    </div>

                    <div class="flex items-center justify-end pt-8">
                        <button type="submit" class="inline-flex items-center px-12 py-5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-black uppercase tracking-widest rounded-2xl transition-all shadow-xl shadow-indigo-500/20 transform hover:scale-105 active:scale-95">
                            Guardar Registro &rarr;
                        </button>
                    </div>
                </div>
            </form>
            
        </div>
    </div>
</x-app-layout>
