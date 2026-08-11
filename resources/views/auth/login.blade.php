<x-guest-layout>
    <x-auth-session-status class="mb-4" :status="session('status')" />
    <x-input-error :messages="$errors->all()" class="mb-4" />

    <div class="mt-6">
        <div class="mb-5 rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-3 text-center">
            <p class="text-sm font-bold text-indigo-900">Elige una opción según la terminación de tu correo</p>
            <p class="mt-1 text-xs text-indigo-700">Cada dominio debe ingresar con su proveedor correspondiente.</p>
        </div>

        <div class="space-y-4">
            <div>
                <p class="mb-2 text-center text-sm font-bold text-gray-800">¿Tu correo termina en <span class="text-indigo-700">@grupoindi.com</span>?</p>
                <a href="{{ route('auth.microsoft') }}" class="flex w-full items-center justify-center gap-3 rounded-md bg-[#2f2f2f] px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-[#1f1f1f] focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <svg class="h-5 w-5" viewBox="0 0 23 23" aria-hidden="true"><path fill="#f25022" d="M1 1h10v10H1z"/><path fill="#7fba00" d="M12 1h10v10H12z"/><path fill="#00a4ef" d="M1 12h10v10H1z"/><path fill="#ffb900" d="M12 12h10v10H12z"/></svg>
                    Iniciar sesión con Microsoft
                </a>
            </div>

            <div class="border-t border-gray-200 pt-4">
                <p class="mb-2 text-center text-sm font-bold text-gray-800">¿Tu correo termina en <span class="text-indigo-700">@construlerma.com</span> o <span class="text-indigo-700">@archandel.com</span>?</p>
                <a href="{{ route('auth.google') }}" class="flex w-full items-center justify-center gap-3 rounded-md border border-gray-300 bg-white px-4 py-3 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" aria-hidden="true"><path fill="#4285F4" d="M21.6 12.23c0-.71-.06-1.4-.18-2.07H12v3.92h5.38a4.6 4.6 0 0 1-2 3.02v2.55h3.24c1.9-1.75 2.98-4.33 2.98-7.42z"/><path fill="#34A853" d="M12 22c2.7 0 4.97-.9 6.63-2.35l-3.24-2.55c-.9.6-2.05.96-3.39.96-2.61 0-4.82-1.76-5.61-4.13H3.04v2.63A10 10 0 0 0 12 22z"/><path fill="#FBBC05" d="M6.39 13.93A6 6 0 0 1 6.07 12c0-.67.12-1.32.32-1.93V7.44H3.04A10 10 0 0 0 2 12c0 1.64.39 3.19 1.04 4.56l3.35-2.63z"/><path fill="#EA4335" d="M12 5.94c1.47 0 2.79.5 3.82 1.5l2.88-2.88A9.64 9.64 0 0 0 12 2a10 10 0 0 0-8.96 5.44l3.35 2.63C7.18 7.7 9.39 5.94 12 5.94z"/></svg>
                    Iniciar sesión con Google
                </a>
            </div>
        </div>
    </div>
</x-guest-layout>
