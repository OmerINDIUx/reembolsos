<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Editar Usuario') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <form action="{{ route('users.update', $user->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-4">
                            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nombre</label>
                            <input type="text" name="name" id="name" value="{{ $user->name }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                            @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-4">
                            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                            <input type="email" name="email" id="email" value="{{ $user->email }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                            @error('email') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nueva Contraseña (Opcional)</label>
                                <input type="password" name="password" id="password" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <p class="text-xs text-gray-500">Dejar en blanco para mantener la actual.</p>
                                @error('password') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Confirmar Nueva Contraseña</label>
                                <input type="password" name="password_confirmation" id="password_confirmation" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <div id="password-match-msg" class="text-xs mt-1 hidden"></div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="profile_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Perfil (Permisos)</label>
                            <select name="profile_id" id="profile_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="">Seleccione un perfil...</option>
                                @foreach($profiles as $profile)
                                    <option value="{{ $profile->id }}" {{ (old('profile_id', $user->profile_id) == $profile->id) ? 'selected' : '' }}>
                                        {{ $profile->display_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('profile_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label for="bank_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nombre del Banco</label>
                                <input type="text" name="bank_name" id="bank_name" value="{{ old('bank_name', $user->bank_name) }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm uppercase">
                                @error('bank_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label for="clabe" class="block text-sm font-medium text-gray-700 dark:text-gray-300">CLABE (18 dígitos)</label>
                                <input type="text" name="clabe" id="clabe" value="{{ old('clabe', $user->clabe) }}" maxlength="18" minlength="18" oninput="this.value = this.value.replace(/[^0-9]/g, '')" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                @error('clabe') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>



                        <div class="flex items-center justify-end">
                            <a href="{{ route('users.index') }}" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 mr-4">Cancelar</a>
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white focus:bg-gray-700 dark:focus:bg-white active:bg-gray-900 dark:active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                                Actualizar Usuario
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const password = document.getElementById('password');
            const confirm = document.getElementById('password_confirmation');
            const msg = document.getElementById('password-match-msg');

            function checkMatch() {
                if (password.value === '' && confirm.value === '') {
                    msg.classList.add('hidden');
                    return;
                }

                msg.classList.remove('hidden');
                if (password.value === confirm.value) {
                    msg.textContent = '✓ Las contraseñas coinciden';
                    msg.className = 'text-xs mt-1 text-green-600 font-medium';
                } else {
                    msg.textContent = '✗ Las contraseñas no coinciden';
                    msg.className = 'text-xs mt-1 text-red-600 font-medium';
                }
            }

            password.addEventListener('input', checkMatch);
            confirm.addEventListener('input', checkMatch);
        });
    </script>
    @endpush
</x-app-layout>
