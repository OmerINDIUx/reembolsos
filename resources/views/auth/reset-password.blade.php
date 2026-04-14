<x-guest-layout>
    <form method="POST" action="{{ route('password.store') }}">
        @csrf

        <!-- Password Reset Token -->
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <!-- Email Address -->
        <input type="hidden" name="email" value="{{ old('email', $request->email) }}">
        <div>
            <x-input-label for="email_display" :value="__('Correo Electrónico')" />
            <div class="mt-1 block w-full px-3 py-2 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-gray-600 dark:text-gray-400 font-medium">
                {{ $request->email }}
            </div>
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Nueva Contraseña')" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" />
            
            <div id="password-strength-container" class="mt-2 hidden">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-[10px] font-black uppercase tracking-widest text-gray-500">Blindaje: <span id="strength-text">Débil</span></span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5 overflow-hidden">
                    <div id="strength-bar" class="bg-red-500 h-full transition-all duration-300" style="width: 33%"></div>
                </div>
            </div>

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirmar Contraseña')" />

            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                                type="password"
                                name="password_confirmation" required autocomplete="new-password" />

            <div id="match-indicator" class="mt-2 text-[10px] font-black uppercase tracking-widest hidden"></div>
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-6">
            <x-primary-button class="w-full justify-center">
                {{ __('Restablecer Contraseña') }}
            </x-primary-button>
        </div>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const password = document.getElementById('password');
            const confirm = document.getElementById('password_confirmation');
            const strengthContainer = document.getElementById('password-strength-container');
            const strengthBar = document.getElementById('strength-bar');
            const strengthText = document.getElementById('strength-text');
            const matchIndicator = document.getElementById('match-indicator');

            function checkStrength(val) {
                if (!val) {
                    strengthContainer.classList.add('hidden');
                    return;
                }
                strengthContainer.classList.remove('hidden');

                let strength = 0;
                if (val.length >= 8) strength++;
                if (/[A-Z]/.test(val)) strength++;
                if (/[0-9]/.test(val)) strength++;
                if (/[^A-Za-z0-9]/.test(val)) strength++;

                if (strength <= 1) {
                    strengthBar.style.width = '33%';
                    strengthBar.style.backgroundColor = '#ef4444'; // Red
                    strengthText.innerText = 'Débil';
                    strengthText.style.color = '#ef4444';
                    strengthText.className = 'font-bold';
                } else if (strength === 2 || strength === 3) {
                    strengthBar.style.width = '66%';
                    strengthBar.style.backgroundColor = '#f59e0b'; // Amber
                    strengthText.innerText = 'Media';
                    strengthText.style.color = '#f59e0b';
                    strengthText.className = 'font-bold';
                } else {
                    strengthBar.style.width = '100%';
                    strengthBar.style.backgroundColor = '#10b981'; // Emerald
                    strengthText.innerText = 'Fuerte';
                    strengthText.style.color = '#10b981';
                    strengthText.className = 'font-black';
                }
            }

            function checkMatch() {
                if (!confirm.value) {
                    matchIndicator.classList.add('hidden');
                    return;
                }
                matchIndicator.classList.remove('hidden');

                if (password.value === confirm.value && password.value !== '') {
                    matchIndicator.innerText = '✓ Las contraseñas coinciden';
                    matchIndicator.className = 'mt-2 text-[10px] font-black uppercase tracking-widest text-emerald-600';
                } else if (password.value !== confirm.value) {
                    matchIndicator.innerText = '✗ Las contraseñas no coinciden';
                    matchIndicator.className = 'mt-2 text-[10px] font-black uppercase tracking-widest text-red-600';
                } else {
                    matchIndicator.classList.add('hidden');
                }
            }

            password.addEventListener('input', (e) => { checkStrength(e.target.value); checkMatch(); });
            confirm.addEventListener('input', checkMatch);
        });
    </script>
</x-guest-layout>
