<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Empresas') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="flex flex-col md:flex-row justify-between items-center mb-6 space-y-4 md:space-y-0">
                        <h3 class="text-lg font-medium">Catálogo de Empresas</h3>
                        @if(!Auth::user()->isAdminView())
                            <a href="{{ route('companies.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                                Nueva Empresa
                            </a>
                        @endif
                    </div>

                    <div class="mb-6 bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                        <form action="{{ route('companies.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div class="col-span-1 md:col-span-3">
                                <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Buscar (Nombre, RFC, Cuenta)</label>
                                <input type="text" name="search" id="search" value="{{ request('search') }}" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="Buscar...">
                            </div>
                            <div class="col-span-1 flex justify-end items-end space-x-2">
                                <a href="{{ route('companies.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-200 dark:bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-gray-700 dark:text-gray-200 uppercase tracking-widest hover:bg-gray-300 dark:hover:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150 h-10">
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

                    <div class="overflow-x-auto relative shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-black text-gray-500 dark:text-gray-300 uppercase tracking-widest">Empresa</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-black text-gray-500 dark:text-gray-300 uppercase tracking-widest">RFC</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-black text-gray-500 dark:text-gray-300 uppercase tracking-widest">Cuenta</th>
                                    <th scope="col" class="px-6 py-3 text-center text-xs font-black text-gray-500 dark:text-gray-300 uppercase tracking-widest">Centros de Costos</th>
                                    <th scope="col" class="relative px-6 py-3">
                                        <span class="sr-only">Acciones</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse ($companies as $company)
                                    <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-black text-gray-900 dark:text-white">{{ $company->name }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-500 dark:text-gray-400">
                                            {{ $company->rfc ?? 'S/RFC' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-500 dark:text-gray-400">
                                            {{ $company->account }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-300">
                                                {{ $company->cost_centers_count }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                            @if(!Auth::user()->isAdminView())
                                                <a href="{{ route('companies.edit', $company) }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-600">Editar</a>
                                                <form action="{{ route('companies.destroy', $company) }}" method="POST" class="inline"
                                                      data-confirm="Se eliminará esta empresa del catálogo. Esta acción no se puede deshacer."
                                                      data-confirm-title="¿Eliminar empresa?"
                                                      data-confirm-type="danger"
                                                      data-confirm-btn="SÍ, ELIMINAR">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-600 ml-2">Eliminar</button>
                                                </form>
                                            @else
                                                <span class="text-gray-400 italic">Solo lectura</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                                            No hay empresas registradas.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-8 px-4 py-3 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-700 sm:rounded-b-lg">
                        {{ $companies->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
