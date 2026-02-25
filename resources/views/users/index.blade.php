<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Gestión de Usuarios') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="flex flex-col md:flex-row justify-between items-center mb-6 space-y-4 md:space-y-0">
                        <h3 class="text-lg font-medium">Lista de Usuarios</h3>
                        @if(!Auth::user()->isAdminView())
                        <a href="{{ route('users.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                            Nuevo Usuario
                        </a>
                        @endif
                    </div>
                    
                    <!-- Search & Filter Form -->
                    <div class="mb-6 bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                        <form id="filter-form" action="{{ route('users.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                            <!-- Search Input -->
                            <div class="col-span-1 md:col-span-2">
                                <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Buscar (Nombre, Email)</label>
                                <input type="text" name="search" id="search" value="{{ request('search') }}" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="Buscar...">
                            </div>

                            <!-- Role Filter -->
                            <div>
                                <label for="role" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Rol</label>
                                <select name="role" id="role" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    <option value="">Todos</option>
                                    <option value="admin" {{ request('role') == 'admin' ? 'selected' : '' }}>Admin</option>
                                    <option value="director" {{ request('role') == 'director' ? 'selected' : '' }}>Director</option>
                                    <option value="control_obra" {{ request('role') == 'control_obra' ? 'selected' : '' }}>Control de Obra</option>
                                    <option value="director_ejecutivo" {{ request('role') == 'director_ejecutivo' ? 'selected' : '' }}>Director Ejecutivo</option>
                                    <option value="accountant" {{ request('role') == 'accountant' ? 'selected' : '' }}>Subdirección</option>
                                    <option value="direccion" {{ request('role') == 'direccion' ? 'selected' : '' }}>Dirección General</option>
                                    <option value="tesoreria" {{ request('role') == 'tesoreria' ? 'selected' : '' }}>Cuentas por Pagar</option>
                                    <option value="user" {{ request('role') == 'user' ? 'selected' : '' }}>Usuario</option>
                                </select>
                             </div>

                            <!-- Status Filter -->
                            <div>
                                <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Estatus (Acceso)</label>
                                <select name="status" id="status" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    <option value="">Todos</option>
                                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Activos (Contraseña Cambiada)</option>
                                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactivos (Pendiente Cambio)</option>
                                </select>
                            </div>

                            <!-- Buttons -->
                            <div class="col-span-1 flex justify-end items-end space-x-2">
                                <a href="{{ route('users.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-200 dark:bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-gray-700 dark:text-gray-200 uppercase tracking-widest hover:bg-gray-300 dark:hover:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150 h-10">
                                    Limpiar
                                </a>
                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150 h-10">
                                    Filtrar
                                </button>
                            </div>
                        </form>
                    </div>

                    @if (session('success'))
                        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                            <span class="block sm:inline">{{ session('success') }}</span>
                        </div>
                    @endif
                    @if (session('error'))
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                            <span class="block sm:inline">{{ session('error') }}</span>
                        </div>
                    @endif

                    <div id="results-container">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nombre</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Email</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Rol</th>
                                    @if(Auth::user()->isAdmin())
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Estatus</th>
                                    @endif

                                    <th scope="col" class="relative px-6 py-3">
                                        <span class="sr-only">Acciones</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach ($users as $user)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {{ $user->name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ $user->email }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            {{ $user->role === 'admin' ? 'bg-purple-100 text-purple-800' : '' }}
                                            {{ $user->role === 'director' ? 'bg-blue-100 text-blue-800' : '' }}
                                            {{ $user->role === 'accountant' ? 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300' : '' }}
                                            {{ $user->role === 'direccion' ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/50 dark:text-indigo-300' : '' }}
                                            {{ $user->role === 'tesoreria' ? 'bg-teal-100 text-teal-800 dark:bg-teal-900/50 dark:text-teal-300' : '' }}
                                            {{ $user->role === 'user' ? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' : '' }}"
                                        >
                                            @if($user->role === 'accountant') Subdirección 
                                            @elseif($user->role === 'direccion') Dirección General
                                            @elseif($user->role === 'tesoreria') Cuentas por Pagar 
                                            @elseif($user->role === 'user') Usuario 
                                            @elseif($user->role === 'admin_view') Admin (Lectura)
                                            @else {{ ucfirst($user->role) }} 
                                            @endif
                                        </span>
                                    </td>
                                    @if(Auth::user()->isAdmin())
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        @if($user->must_change_password)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">
                                                <span class="w-2 h-2 mr-1.5 rounded-full bg-red-500"></span>
                                                Inactivo
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400">
                                                <span class="w-2 h-2 mr-1.5 rounded-full bg-emerald-500"></span>
                                                Activo
                                            </span>
                                        @endif
                                    </td>
                                    @endif

                                     <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                        @if(!Auth::user()->isAdminView())
                                        <a href="{{ route('users.edit', $user->id) }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-600">Editar</a>
                                        
                                        @if($user->id !== auth()->id())
                                        <form action="{{ route('users.destroy', $user->id) }}" method="POST" class="inline" onsubmit="return confirm('¿Estás seguro de eliminar este usuario?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-600 ml-2">Eliminar</button>
                                        </form>
                                        @endif
                                        @else
                                        <span class="text-gray-400 italic">Solo lectura</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <div class="mt-4">
                            {{ $users->links() }}
                        </div>
                    </div>
                    </div> <!-- End results-container -->
                </div>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('filter-form');
            const container = document.getElementById('results-container');
            
            // Function to handle fetching and updating
            function fetchResults(url) {
                // simple opacity fade for feedback
                container.style.opacity = '0.5';
                
                fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newContent = doc.getElementById('results-container').innerHTML;
                    container.innerHTML = newContent;
                    container.style.opacity = '1';
                    
                    // Update URL without reload
                    window.history.pushState({}, '', url);
                    
                    // Re-attach pagination listeners
                    attachPaginationListeners();
                })
                .catch(error => {
                    console.error('Error:', error);
                    container.style.opacity = '1';
                });
            }
            
            // Handle Form Submit (Manual Filter)
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                submitFilter();
            });

            function submitFilter() {
                const url = new URL(form.action);
                const params = new URLSearchParams(new FormData(form));
                url.search = params.toString();
                fetchResults(url);
            }

            // Real-time Search Logic with Debounce
            let debounceTimer;
            
            // Listen to inputs
            form.querySelectorAll('input').forEach(input => {
                input.addEventListener('input', function() {
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(() => {
                        submitFilter();
                    }, 500); // 500ms delay
                });
            });

            // Listen to selects (immediate trigger)
            form.querySelectorAll('select').forEach(select => {
                select.addEventListener('change', function() {
                    submitFilter();
                });
            });
            
            // Handle Pagination Clicks
            function attachPaginationListeners() {
                const links = container.querySelectorAll('a.page-link, .pagination a'); // Adapt selector to Laravel's pagination classes
                links.forEach(link => {
                     link.addEventListener('click', function(e) {
                         e.preventDefault();
                         fetchResults(this.href);
                     });
                });
            }
            
            // Initial attach
            attachPaginationListeners();
        });
    </script>
</x-app-layout>
