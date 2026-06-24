<x-guest-layout>
    <form method="POST" action="{{ route('register') }}">
        @csrf

        <!-- Name -->
        <div>
            <x-input-label for="name" :value="__('Nombre completo con dos apellidos')" />
            <x-text-input id="name" class="block mt-1 w-full uppercase" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" placeholder="Nombre Apellido Paterno Apellido Materno" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div class="mt-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Bank Name -->
        <div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-xs text-amber-800 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-200">
            <p class="font-black uppercase tracking-widest">Información personal requerida</p>
            <p class="mt-1 leading-5">Tu nombre completo, RFC y cuenta bancaria son datos personales requeridos para poder procesar tus reembolsos y pagos correctamente.</p>
        </div>

        <div class="mt-4">
            <x-input-label for="rfc" value="RFC" />
            <x-text-input id="rfc" class="block mt-1 w-full uppercase" type="text" name="rfc" :value="old('rfc')" required maxlength="13" minlength="12" oninput="this.value = this.value.toUpperCase().replace(/[^A-ZÑ&0-9]/g, '')" />
            <x-input-error :messages="$errors->get('rfc')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="bank_name" value="Nombre del Banco" />
            <x-text-input id="bank_name" class="block mt-1 w-full uppercase" type="text" name="bank_name" :value="old('bank_name')" required />
            <x-input-error :messages="$errors->get('bank_name')" class="mt-2" />
        </div>

        <label class="mt-5 flex items-start gap-3 rounded-2xl border border-gray-200 p-4 text-sm text-gray-700 dark:border-gray-700 dark:text-gray-300">
            <input type="checkbox" name="personal_info_confirmed" value="1" class="mt-1 rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" required>
            <span>Confirmo que mi nombre completo, RFC y datos bancarios son correctos. Entiendo que esta información personal es requerida para recibir mis reembolsos.</span>
        </label>
        <x-input-error :messages="$errors->get('personal_info_confirmed')" class="mt-2" />

        <!-- CLABE -->
        <div class="mt-4">
            <x-input-label for="clabe" value="CLABE (18 dígitos)" />
            <x-text-input id="clabe" class="block mt-1 w-full" type="text" name="clabe" :value="old('clabe')" required maxlength="18" minlength="18" oninput="this.value = this.value.replace(/[^0-9]/g, '')" />
            <x-input-error :messages="$errors->get('clabe')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />

            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                            type="password"
                            name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <a class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800" href="{{ route('login') }}">
                {{ __('Already registered?') }}
            </a>

            <x-primary-button class="ms-4">
                {{ __('Register') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
