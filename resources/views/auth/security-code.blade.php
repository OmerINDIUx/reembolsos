<x-guest-layout>
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-950">
        <p class="font-black">Verificación de seguridad requerida</p>
        <p class="mt-1">Detectamos un inicio de sesión con riesgo alto. Enviamos un código al correo de la cuenta. Ingresa el código para terminar de iniciar sesión.</p>
        <p class="mt-2 text-xs font-bold uppercase tracking-wide text-amber-700">
            Vence: {{ $challenge->expires_at->format('d/m/Y H:i') }}
        </p>
    </div>

    <form method="POST" action="{{ route('login.security_code.verify') }}">
        @csrf

        <div>
            <x-input-label for="code" value="Código de 6 dígitos" />
            <x-text-input id="code" class="block mt-1 w-full text-center text-2xl font-black tracking-[0.4em]"
                type="text"
                name="code"
                inputmode="numeric"
                pattern="[0-9]{6}"
                maxlength="6"
                required
                autofocus
                autocomplete="one-time-code" />
            <x-input-error :messages="$errors->get('code')" class="mt-2" />
        </div>

        <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <a class="text-sm font-semibold text-gray-600 underline hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100" href="{{ route('login') }}">
                Cancelar e iniciar de nuevo
            </a>

            <x-primary-button>
                Verificar código
            </x-primary-button>
        </div>
    </form>

    <form method="POST" action="{{ route('login.security_code.resend') }}" class="mt-4">
        @csrf
        <button class="text-sm font-bold text-indigo-600 underline hover:text-indigo-800" type="submit">
            Enviar otro código
        </button>
    </form>
</x-guest-layout>
