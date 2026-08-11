<section x-data="{ enabled: {{ ($user->email_notifications_enabled ?? true) ? 'true' : 'false' }} }">
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Notificaciones por correo</h2>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            Elige qué avisos deseas recibir. Los avisos dentro del sistema seguirán disponibles aunque desactives el correo.
        </p>
    </header>

    <form method="post" action="{{ route('profile.notifications.update') }}" class="mt-6 space-y-4">
        @csrf
        @method('patch')

        <label class="flex items-start gap-3 rounded-2xl border border-indigo-200 bg-indigo-50 p-4 text-sm text-indigo-900 dark:border-indigo-900 dark:bg-indigo-950/30 dark:text-indigo-100">
            <input type="hidden" name="email_notifications_enabled" value="0">
            <input type="checkbox" name="email_notifications_enabled" value="1" x-model="enabled" class="mt-1 rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
            <span><strong>Recibir notificaciones por correo</strong><br><span class="text-xs opacity-80">Desactiva esta opción para detener todos los correos de reembolsos.</span></span>
        </label>

        <div class="space-y-3 rounded-2xl border border-gray-200 p-4 dark:border-gray-700" :class="{ 'opacity-50': !enabled }">
            <label class="flex items-start gap-3 text-sm text-gray-700 dark:text-gray-200">
                <input type="hidden" name="email_workflow_notifications" value="0">
                <input type="checkbox" name="email_workflow_notifications" value="1" @checked($user->email_workflow_notifications ?? true) class="mt-1 rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                <span><strong>Seguimiento y acciones requeridas</strong><br><span class="text-xs text-gray-500">Aprobaciones, rechazos, correcciones y cambios relevantes. No incluye el paso a las colas de CXP Revisadores o Pagadores.</span></span>
            </label>
            <label class="flex items-start gap-3 text-sm text-gray-700 dark:text-gray-200">
                <input type="hidden" name="email_payment_notifications" value="0">
                <input type="checkbox" name="email_payment_notifications" value="1" @checked($user->email_payment_notifications ?? true) class="mt-1 rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                <span><strong>Pago</strong><br><span class="text-xs text-gray-500">Aviso cuando un reembolso entra al Módulo de Pago y cuando el pago queda confirmado.</span></span>
            </label>
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>Guardar preferencias</x-primary-button>
            @if (session('status') === 'notification-preferences-updated')
                <p class="text-sm text-green-600 dark:text-green-400">Preferencias guardadas.</p>
            @endif
        </div>
    </form>
</section>
