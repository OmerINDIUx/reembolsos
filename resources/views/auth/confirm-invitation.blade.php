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

        <div class="flex items-center justify-end mt-4">
            <x-primary-button>
                {{ __('Activar Cuenta') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
