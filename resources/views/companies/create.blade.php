<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Crear Empresa') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <form action="{{ route('companies.store') }}" method="POST">
                        @csrf

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label for="name" class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Nombre de la Empresa *</label>
                                <input type="text" name="name" id="name" value="{{ old('name') }}" class="w-full border-gray-200 dark:border-gray-700 dark:bg-gray-900 rounded-2xl shadow-sm focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all font-bold py-3 uppercase" required>
                                @error('name')
                                    <p class="mt-2 text-xs font-bold text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="rfc" class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">RFC *</label>
                                <input type="text" name="rfc" id="rfc" value="{{ old('rfc') }}" maxlength="13" class="w-full border-gray-200 dark:border-gray-700 dark:bg-gray-900 rounded-2xl shadow-sm focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all font-bold py-3 uppercase" required>
                                @error('rfc')
                                    <p class="mt-2 text-xs font-bold text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="account" class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Cuenta de la Empresa *</label>
                                <input type="text" name="account" id="account" value="{{ old('account') }}" class="w-full border-gray-200 dark:border-gray-700 dark:bg-gray-900 rounded-2xl shadow-sm focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all font-bold py-3" required>
                                @error('account')
                                    <p class="mt-2 text-xs font-bold text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="flex items-center justify-end mt-12 pt-8 border-t border-gray-100 dark:border-gray-800">
                            <a href="{{ route('companies.index') }}" class="text-sm font-bold text-gray-500 hover:text-gray-900 dark:hover:text-gray-100 mr-6 transition-colors">Cancelar</a>
                            <button type="submit" class="inline-flex items-center px-8 py-4 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-gray-800 dark:hover:bg-gray-100 transition-all shadow-xl shadow-gray-900/20 dark:shadow-white/10">
                                Crear Empresa
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
