<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Crear Centro de Costos') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <form action="{{ route('cost_centers.store') }}" method="POST">
                        @csrf

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <label for="name" class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Nombre del Centro de Costos *</label>
                                <input type="text" name="name" id="name" class="w-full border-gray-200 dark:border-gray-700 dark:bg-gray-900 rounded-2xl shadow-sm focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all font-bold py-3 uppercase" required>
                            </div>
                            <div>
                                <label for="budget" class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Presupuesto Inicial *</label>
                                <div class="relative">
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 font-bold">$</span>
                                    <input type="number" step="0.01" name="budget" id="budget" class="w-full border-gray-200 dark:border-gray-700 dark:bg-gray-900 rounded-2xl shadow-sm focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all font-bold py-3 pl-8" required placeholder="0.00">
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="description" class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Descripción (Opcional)</label>
                            <textarea name="description" id="description" rows="2" class="w-full border-gray-200 dark:border-gray-700 dark:bg-gray-900 rounded-2xl shadow-sm focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all font-medium py-3"></textarea>
                        </div>

                        <!-- Dynamic Steps with Alpine.js -->
                        <div x-data="{ 
                            steps: [
                                { user_id: '', name: 'Director N1' },
                                { user_id: '', name: 'Control de Obra N2' },
                                { user_id: '', name: 'Director Ejecutivo N3' }
                            ],
                            addStep() {
                                this.steps.push({ user_id: '', name: 'Aprobador N' + (this.steps.length + 1) });
                            },
                            removeStep(index) {
                                this.steps.splice(index, 1);
                            }
                        }" class="mb-8 mt-8">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-md font-black uppercase tracking-widest text-indigo-600 dark:text-indigo-400">Flujo de Aprobación Personalizado</h3>
                                <button type="button" @click="addStep()" class="inline-flex items-center px-3 py-1.5 bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 rounded-lg text-xs font-bold hover:bg-indigo-200 transition-colors">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                    Añadir Nivel
                                </button>
                            </div>

                            <div class="space-y-3">
                                <template x-for="(step, index) in steps" :key="index">
                                    <div class="group flex items-center gap-3 bg-gray-50 dark:bg-gray-900/50 p-3 rounded-xl border border-gray-100 dark:border-gray-700 transition-all hover:border-indigo-200">
                                        <div class="flex-none flex items-center justify-center w-8 h-8 rounded-full bg-indigo-600 text-white font-black text-xs" x-text="index + 1"></div>
                                        
                                        <div class="flex-1 grid grid-cols-1 md:grid-cols-2 gap-3">
                                            <div>
                                                <input type="text" :name="'steps['+index+'][name]'" x-model="step.name" placeholder="Nombre del Nivel (ej: Director)" class="block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                                            </div>
                                            <div>
                                                <select :name="'steps['+index+'][user_id]'" x-model="step.user_id" class="block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                                                    <option value="">Seleccione Usuario...</option>
                                                    @foreach($users as $u)
                                                        <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->role }})</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        <button type="button" @click="removeStep(index)" class="flex-none p-2 text-gray-400 hover:text-red-500 transition-colors">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div class="flex items-center justify-end">

                        <div class="flex items-center justify-end">
                            <a href="{{ route('cost_centers.index') }}" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 mr-4">Cancelar</a>
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white focus:bg-gray-700 dark:focus:bg-white active:bg-gray-900 dark:active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                                Crear Centro de Costo
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
