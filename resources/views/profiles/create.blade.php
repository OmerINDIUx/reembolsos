<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center space-x-4">
            <a href="{{ route('profiles.index') }}" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
            </a>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Nuevo Perfil de Usuario') }}
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <form action="{{ route('profiles.store') }}" method="POST">
                @csrf
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg border border-gray-200 dark:border-gray-700 mb-6">
                    <div class="p-8 border-b border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/20">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Información General</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div>
                                <label for="display_name" class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2 uppercase tracking-wider">Nombre del Perfil</label>
                                <input type="text" name="display_name" id="display_name" value="{{ old('display_name') }}" required
                                    class="w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm transition-all duration-200 placeholder-gray-400"
                                    placeholder="Ej. Auditor de Gastos">
                                <x-input-error :messages="$errors->get('display_name')" class="mt-2" />
                            </div>
                            <div>
                                <label for="description" class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2 uppercase tracking-wider">Descripción</label>
                                <input type="text" name="description" id="description" value="{{ old('description') }}"
                                    class="w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm transition-all duration-200 placeholder-gray-400"
                                    placeholder="Breve explicación de las responsabilidades">
                                <x-input-error :messages="$errors->get('description')" class="mt-2" />
                            </div>
                        </div>
                    </div>

                    <div class="p-8">
                        <div class="flex items-center justify-between mb-8">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Matriz de Permisos</h3>
                            <button type="button" onclick="toggleAllPermissions(true)" class="text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-widest hover:underline">Seleccionar Todos</button>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                            @foreach($permissions as $module => $modulePermissions)
                            <div class="bg-gray-50 dark:bg-gray-900/40 rounded-2xl p-6 border border-gray-100 dark:border-gray-700/50 hover:shadow-md transition-shadow duration-300">
                                <div class="flex items-center justify-between mb-4 border-b border-gray-200 dark:border-gray-700 pb-3">
                                    <h4 class="font-black text-xs uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">
                                        {{ str_replace('_', ' ', $module) }}
                                    </h4>
                                    <input type="checkbox" onchange="toggleModulePermissions('{{ $module }}', this.checked)" class="rounded-md border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                </div>
                                <div class="space-y-3">
                                    @foreach($modulePermissions as $permission)
                                    <label class="flex items-center group cursor-pointer">
                                        <div class="relative flex items-center">
                                            <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" 
                                                data-module="{{ $module }}"
                                                class="permission-checkbox h-5 w-5 rounded-lg border-gray-300 dark:border-gray-700 text-indigo-600 focus:ring-indigo-500 transition-all duration-200">
                                        </div>
                                        <span class="ml-3 text-sm font-medium text-gray-700 dark:text-gray-300 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">
                                            {{ trim(str_ireplace(str_replace('_', ' ', $module), '', $permission->display_name)) }}
                                        </span>
                                    </label>
                                    @endforeach
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="p-8 bg-gray-50 dark:bg-gray-900/20 border-t border-gray-200 dark:border-gray-700 flex justify-end space-x-4">
                        <a href="{{ route('profiles.index') }}" class="inline-flex items-center px-6 py-3 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-xl font-bold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150">
                            Cancelar
                        </a>
                        <button type="submit" class="inline-flex items-center px-8 py-3 bg-indigo-600 border border-transparent rounded-xl font-black text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 shadow-lg shadow-indigo-500/20">
                            Crear Perfil
                        </button>
                    </div>
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
