<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <a href="{{ route('profiles.index') }}" class="text-gray-500 hover:text-indigo-600 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
                <h2 class="font-bold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    {{ __('Nuevo Perfil de Usuario') }}
                </h2>
            </div>
        </div>
    </x-slot>

    <div class="py-12 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <form action="{{ route('profiles.store') }}" method="POST">
                @csrf
                
                <!-- Section 1: Basic Info -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden mb-8">
                    <div class="p-8">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-6 flex items-center">
                            <span class="bg-indigo-100 text-indigo-600 p-2 rounded-lg mr-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                                </svg>
                            </span>
                            Información del Perfil
                        </h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="display_name" class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2 ml-1">Nombre del Perfil</label>
                                <input type="text" name="display_name" id="display_name" value="{{ old('display_name') }}" required
                                    class="w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm transition-all duration-200"
                                    placeholder="Ej. Auditor de Finanzas">
                                <x-input-error :messages="$errors->get('display_name')" class="mt-2" />
                            </div>
                            <div>
                                <label for="description" class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2 ml-1">Descripción corta</label>
                                <input type="text" name="description" id="description" value="{{ old('description') }}"
                                    class="w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm transition-all duration-200"
                                    placeholder="Define brevemente el alcance de este rol">
                                <x-input-error :messages="$errors->get('description')" class="mt-2" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Permission Matrix -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden mb-8">
                    <div class="px-8 py-6 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between bg-gray-50/50 dark:bg-gray-900/20">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center">
                            <span class="bg-indigo-100 text-indigo-600 p-2 rounded-lg mr-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                                </svg>
                            </span>
                            Matriz de Permisos
                        </h3>
                        <div class="flex items-center space-x-4">
                            <button type="button" onclick="toggleAllPermissions(true)" class="text-xs font-bold text-indigo-600 hover:text-indigo-700 uppercase tracking-widest">Seleccionar Todo</button>
                            <span class="text-gray-300">|</span>
                            <button type="button" onclick="toggleAllPermissions(false)" class="text-xs font-bold text-gray-400 hover:text-gray-600 uppercase tracking-widest">Limpiar</button>
                        </div>
                    </div>

                    <div class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach($permissions as $module => $modulePermissions)
                        <div class="p-8">
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="text-sm font-black text-gray-900 dark:text-white uppercase tracking-[0.1em] flex items-center">
                                    Módulo: {{ str_replace('_', ' ', $module) }}
                                </h4>
                                <label class="inline-flex items-center cursor-pointer group">
                                    <span class="mr-2 text-[10px] font-bold text-gray-400 group-hover:text-indigo-600 uppercase transition-colors">Marcar Módulo</span>
                                    <input type="checkbox" onchange="toggleModulePermissions('{{ $module }}', this.checked)" 
                                        class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 shadow-sm transition-all duration-200">
                                </label>
                            </div>
                            
                            <div class="overflow-x-auto rounded-xl border border-gray-100 dark:border-gray-700">
                                <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700">
                                    <thead class="bg-gray-50/50 dark:bg-gray-900/40">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 text-left text-[10px] font-bold text-gray-500 uppercase tracking-widest">Acción</th>
                                            <th scope="col" class="px-6 py-3 text-left text-[10px] font-bold text-gray-500 uppercase tracking-widest">Descripción del Permiso</th>
                                            <th scope="col" class="px-6 py-3 text-center text-[10px] font-bold text-gray-500 uppercase tracking-widest">Habilitar</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-50 dark:divide-gray-700">
                                        @foreach($modulePermissions as $permission)
                                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-900/20 transition-colors">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 dark:text-white">
                                                {{ $permission->display_name }}
                                            </td>
                                            <td class="px-6 py-4 text-xs text-gray-500 dark:text-gray-400">
                                                {{ $permission->description ?? 'Permite realizar acciones en el módulo.' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                                <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" 
                                                    data-module="{{ $module }}"
                                                    class="permission-checkbox h-5 w-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 transition-all duration-200 cursor-pointer">
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex items-center justify-end space-x-4 pb-12">
                    <a href="{{ route('profiles.index') }}" class="px-6 py-3 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-xl font-bold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest hover:bg-gray-50 transition-colors">
                        Cancelar
                    </a>
                    <button type="submit" class="px-10 py-3 bg-indigo-600 border border-transparent rounded-xl font-black text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-all duration-200 shadow-lg shadow-indigo-500/25">
                        Crear Perfil
                    </button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
        function toggleModulePermissions(module, checked) {
            const checkboxes = document.querySelectorAll(`.permission-checkbox[data-module="${module}"]`);
            checkboxes.forEach(cb => cb.checked = checked);
        }

        function toggleAllPermissions(checked) {
            const checkboxes = document.querySelectorAll('.permission-checkbox');
            checkboxes.forEach(cb => cb.checked = checked);
        }
    </script>
    @endpush
</x-app-layout>


