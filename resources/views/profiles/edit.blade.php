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
                    {{ __('Editar Perfil:') }} <span class="text-indigo-600">{{ $profile->display_name }}</span>
                </h2>
            </div>
        </div>
    </x-slot>

    <div class="py-12 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <form action="{{ route('profiles.update', $profile) }}" method="POST">
                @csrf
                @method('PATCH')
                
                <!-- Section 1: Basic Info & Users -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-8">
                    <div class="md:col-span-2 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-8">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-6 flex items-center">
                            <span class="bg-indigo-100 text-indigo-600 p-2 rounded-lg mr-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd" />
                                </svg>
                            </span>
                            Configuración Base
                        </h3>
                        
                        <div class="space-y-6">
                            <div>
                                <label for="display_name" class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2 ml-1">Nombre Descriptivo</label>
                                <input type="text" name="display_name" id="display_name" value="{{ old('display_name', $profile->display_name) }}" required
                                    class="w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm transition-all duration-200">
                                <x-input-error :messages="$errors->get('display_name')" class="mt-2" />
                            </div>
                            <div>
                                <label for="description" class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2 ml-1">Alcance del Rol</label>
                                <textarea name="description" id="description" rows="2"
                                    class="w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm transition-all duration-200"
                                    placeholder="¿Qué permite hacer este perfil?">{{ old('description', $profile->description) }}</textarea>
                                <x-input-error :messages="$errors->get('description')" class="mt-2" />
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-900 rounded-2xl shadow-sm p-8 border border-gray-800">
                        <h3 class="text-white font-bold text-sm mb-4 flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 text-indigo-400" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z" />
                            </svg>
                            Usuarios Activos
                        </h3>
                        <div class="space-y-3 max-h-[160px] overflow-y-auto pr-2 custom-scrollbar">
                            @forelse($profile->users as $user)
                            <div class="p-2 bg-white/5 rounded-lg border border-white/5">
                                <p class="text-xs font-bold text-white truncate">{{ $user->name }}</p>
                                <p class="text-[10px] text-gray-500 truncate">{{ $user->email }}</p>
                            </div>
                            @empty
                            <p class="text-[10px] text-gray-600 italic">Sin usuarios asignados</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                <!-- Section 2: Permission Matrix -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden mb-8">
                    <div class="px-8 py-6 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between bg-gray-50/50 dark:bg-gray-900/20">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center">
                            <span class="bg-indigo-100 text-indigo-600 p-2 rounded-lg mr-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                            </span>
                            Matriz de Capacidades
                        </h3>
                        <div class="flex items-center space-x-4">
                            <button type="button" onclick="toggleAllPermissions(true)" class="text-xs font-bold text-indigo-600 hover:text-indigo-700 uppercase tracking-widest">Marcar Todo</button>
                            <span class="text-gray-300">|</span>
                            <button type="button" onclick="toggleAllPermissions(false)" class="text-xs font-bold text-gray-400 hover:text-gray-600 uppercase tracking-widest">Desmarcar</button>
                        </div>
                    </div>

                    <div class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach($permissions as $module => $modulePermissions)
                        <div class="p-8">
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="text-sm font-black text-gray-900 dark:text-white uppercase tracking-[0.15em]">
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
                                            <th scope="col" class="px-6 py-3 text-left text-[10px] font-bold text-gray-500 uppercase tracking-widest">Capacidad</th>
                                            <th scope="col" class="px-6 py-3 text-left text-[10px] font-bold text-gray-500 uppercase tracking-widest">Descripción Funcional</th>
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
                                                {{ $permission->description ?? 'Acceso estándar al módulo.' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                                <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" 
                                                    data-module="{{ $module }}"
                                                    @checked(in_array($permission->id, $profilePermissions))
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
                        Guardar Cambios
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
    
    <style>
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #4f46e5; border-radius: 10px; }
    </style>
</x-app-layout>


