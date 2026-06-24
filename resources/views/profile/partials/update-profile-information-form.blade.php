<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __("Actualiza tu nombre completo, RFC, banco y CLABE interbancaria.") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" :value="__('Nombre Completo')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full uppercase" :value="old('name', $user->name)" required autofocus autocomplete="name" placeholder="Nombre Apellido Paterno Apellido Materno" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="rfc" :value="__('RFC')" />
            <x-text-input id="rfc" name="rfc" type="text" class="mt-1 block w-full uppercase" :value="old('rfc', $user->rfc)" required maxlength="13" minlength="12" autocomplete="off" oninput="this.value = this.value.toUpperCase().replace(/[^A-ZÑ&0-9]/g, '')" placeholder="ABCD000000XXX" />
            <x-input-error class="mt-2" :messages="$errors->get('rfc')" />
        </div>

        <div>
            <x-input-label for="bank_name" :value="__('Institución Bancaria')" />
            <x-text-input id="bank_name" name="bank_name" type="text" class="mt-1 block w-full uppercase" :value="old('bank_name', $user->bank_name)" placeholder="Ej. BBVA, Santander, etc." autocomplete="off" required />
            <x-input-error class="mt-2" :messages="$errors->get('bank_name')" />
        </div>

        <div>
            <x-input-label for="clabe" :value="__('Cuenta CLABE (18 dígitos)')" />
            <x-text-input id="clabe" name="clabe" type="text" class="mt-1 block w-full" :value="old('clabe', $user->clabe)" placeholder="000000000000000000" maxlength="18" autocomplete="off" oninput="this.value = this.value.replace(/[^0-9]/g, '')" required />
            <x-input-error class="mt-2" :messages="$errors->get('clabe')" />
        </div>

        <label class="flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-200">
            <input type="checkbox" name="personal_info_confirmed" value="1" class="mt-1 rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" required>
            <span>Confirmo que mi nombre completo, RFC y datos bancarios son correctos. Entiendo que esta información personal es requerida para recibir mis reembolsos.</span>
        </label>
        <x-input-error class="mt-2" :messages="$errors->get('personal_info_confirmed')" />

        <div>
            <x-input-label for="email" :value="__('Correo Electrónico')" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full bg-gray-100 dark:bg-gray-700 cursor-not-allowed" :value="old('email', $user->email)" readonly />
            <p class="mt-2 text-xs text-gray-500 italic">El correo electrónico no puede ser modificado por el usuario.</p>

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-gray-800 dark:text-gray-200">
                        {{ __('Your email address is unverified.') }}

                        <button form="send-verification" class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-600 dark:text-green-400">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600 dark:text-gray-400"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
