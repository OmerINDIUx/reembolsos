<nav class="flex flex-wrap items-center gap-2" aria-label="Secciones de administración">
    <a href="{{ route('admin.device-audit.index') }}" class="rounded-xl px-4 py-2 text-sm font-bold {{ request()->routeIs('admin.device-audit.index') ? 'bg-gray-900 text-white dark:bg-white dark:text-gray-900' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-200' }}">Seguridad</a>
    <a href="{{ route('admin.device-audit.reimbursements-dashboard') }}" class="rounded-xl px-4 py-2 text-sm font-bold {{ request()->routeIs('admin.device-audit.reimbursements-dashboard') ? 'bg-indigo-600 text-white' : 'bg-indigo-50 text-indigo-700 hover:bg-indigo-100 dark:bg-indigo-950 dark:text-indigo-200' }}">Dashboard de reembolsos</a>
    <details class="relative">
        <summary class="cursor-pointer list-none rounded-xl bg-emerald-600 px-4 py-2 text-sm font-bold text-white hover:bg-emerald-700">Descargas <span aria-hidden="true">⌄</span></summary>
        <div class="absolute right-0 z-20 mt-2 w-72 overflow-hidden rounded-xl border border-gray-200 bg-white py-1 shadow-xl dark:border-gray-700 dark:bg-gray-900">
            <a href="{{ route('admin.device-audit.users.export', ['search' => request('search')]) }}" class="block px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-800">Usuarios y centros autorizados</a>
            <a href="{{ route('admin.device-audit.approvers.export') }}" class="block px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-800">Matriz de aprobadores por centro de costos</a>
        </div>
    </details>
</nav>
