<x-guest-layout>
    <div class="py-12 text-center">
        <h1 class="text-xl font-bold text-gray-900">Autenticación Microsoft completada</h1>
        <p class="mt-2 text-sm text-gray-600">Regresando a la plataforma...</p>

        @if($context)
            <form id="reauth-continue" method="POST" action="{{ $context['action_url'] }}">
                @csrf
                <noscript><button class="mt-6 rounded bg-indigo-600 px-4 py-2 text-white">Continuar</button></noscript>
            </form>
        @endif
    </div>
    <script>
        const context = @json($context);

        if (window.opener && !window.opener.closed) {
            window.opener.postMessage({ type: 'microsoft-reauth-complete' }, window.location.origin);
            window.close();
        } else if (context) {
            const form = document.getElementById('reauth-continue');
            const payload = context.payload || {};
            const append = (name, value) => {
                const input = document.createElement('input');
                input.type = 'hidden'; input.name = name; input.value = value ?? '';
                form.appendChild(input);
            };
            const walk = (value, name) => {
                if (Array.isArray(value)) return value.forEach((item, index) => walk(item, `${name}[${index}]`));
                if (value !== null && typeof value === 'object') return Object.entries(value).forEach(([key, item]) => walk(item, name ? `${name}[${key}]` : key));
                append(name, value);
            };
            Object.entries(payload).forEach(([key, value]) => walk(value, key));
            form.submit();
        }
    </script>
</x-guest-layout>