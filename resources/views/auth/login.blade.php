<x-guest-layout>
    <x-auth-session-status class="mb-4" :status="session('status')" />
    <x-input-error :messages="$errors->all()" class="mb-4" />

    <div class="mt-6">
        <a href="{{ route('auth.microsoft') }}" class="flex w-full items-center justify-center gap-3 rounded-md bg-[#2f2f2f] px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-[#1f1f1f] focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <svg class="h-5 w-5" viewBox="0 0 23 23" aria-hidden="true"><path fill="#f25022" d="M1 1h10v10H1z"/><path fill="#7fba00" d="M12 1h10v10H12z"/><path fill="#00a4ef" d="M1 12h10v10H1z"/><path fill="#ffb900" d="M12 12h10v10H12z"/></svg>
            Continuar con Microsoft
        </a>
        <p class="mt-4 text-center text-xs text-gray-500">El acceso se realiza exclusivamente con tu cuenta de Microsoft.</p>
    </div>
</x-guest-layout>