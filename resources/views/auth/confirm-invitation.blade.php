<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
        {{ __('Hola :name, por favor establece tu contraseña para activar tu cuenta en el Sistema de Reembolsos.', ['name' => $user->name]) }}
    </div>

    <form method="POST" action="{{ route('invitation.complete') }}">
        @csrf

        <input type="hidden" name="token" value="{{ $token }}">

        <!-- Email Address (Disabled, just for reference) -->
        <div class="mb-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full bg-gray-100 dark:bg-gray-700 cursor-not-allowed" type="email" name="email" :value="$user->email" disabled />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Nueva Contraseña')" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" autofocus />
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
            <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
            <div id="match-indicator" class="mt-2 text-[10px] font-black uppercase tracking-widest hidden"></div>
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="border-t border-gray-100 dark:border-gray-800 my-6"></div>

        <!-- Bank Name -->
        <div class="mt-4">
            <x-input-label for="bank_name" :value="__('Nombre del Banco')" />
            <x-text-input id="bank_name" class="block mt-1 w-full uppercase placeholder:normal-case shadow-sm" type="text" name="bank_name" :value="old('bank_name')" required placeholder="Ej. BBVA, Santander..." />
            <x-input-error :messages="$errors->get('bank_name')" class="mt-2" />
        </div>

        <!-- CLABE -->
        <div class="mt-4">
            <x-input-label for="clabe" :value="__('CLABE (18 dígitos)')" />
            <x-text-input id="clabe" class="block mt-1 w-full shadow-sm" type="text" name="clabe" :value="old('clabe')" required maxlength="18" minlength="18" oninput="this.value = this.value.replace(/[^0-9]/g, '')" placeholder="000 000 0000000000 0" />
            <x-input-error :messages="$errors->get('clabe')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button>
                {{ __('Activar Cuenta') }}
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
                    strengthBar.className = 'bg-red-500 h-full transition-all duration-300';
                    strengthText.innerText = 'Débil';
                    strengthText.className = 'text-red-500';
                } else if (strength === 2 || strength === 3) {
                    strengthBar.style.width = '66%';
                    strengthBar.className = 'bg-amber-500 h-full transition-all duration-300';
                    strengthText.innerText = 'Media';
                    strengthText.className = 'text-amber-500 font-bold';
                } else {
                    strengthBar.style.width = '100%';
                    strengthBar.className = 'bg-emerald-500 h-full transition-all duration-300';
                    strengthText.innerText = 'Fuerte';
                    strengthText.className = 'text-emerald-500 font-black';
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
