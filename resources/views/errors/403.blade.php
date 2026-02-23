<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Acceso Denegado') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100 text-center">
                    <div class="mb-4">
                        <svg class="mx-auto h-12 w-12 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <h1 class="text-2xl font-bold mb-2">403 - No autorizado</h1>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">
                        {{ $exception->getMessage() ?: "Aún no tienes permiso para ver este reembolso. Está en una etapa anterior de aprobación." }}
                    </p>
                    <p class="text-sm text-gray-500">
                        Serás redirigido al listado de reembolsos en <span id="countdown">10</span> segundos...
                    </p>
                    <div class="mt-6">
                        <a href="{{ route('reimbursements.index') }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 font-medium">
                            Volver al listado ahora
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let seconds = 10;
        const countdownEl = document.getElementById('countdown');
        
        const timer = setInterval(() => {
            seconds--;
            countdownEl.textContent = seconds;
            if (seconds <= 0) {
                clearInterval(timer);
                window.location.href = "{{ route('reimbursements.index') }}";
            }
        }, 1000);
    </script>
</x-app-layout>
