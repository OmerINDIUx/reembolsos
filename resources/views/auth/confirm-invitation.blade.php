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
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirmar Contraseña')" />
            <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
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
</x-guest-layout>
