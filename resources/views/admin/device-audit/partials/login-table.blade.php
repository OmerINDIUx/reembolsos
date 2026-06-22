<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
        <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500 dark:bg-gray-900">
            <tr>
                <th class="px-5 py-3">Fecha</th>
                <th class="px-5 py-3">Usuario</th>
                <th class="px-5 py-3">Dispositivo / navegador</th>
                <th class="px-5 py-3">IP / ubicación</th>
                <th class="px-5 py-3">Señales</th>
                <th class="px-5 py-3">Estado</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
            @forelse($logins as $login)
                <tr>
                    <td class="whitespace-nowrap px-5 py-4 text-gray-600 dark:text-gray-300">
                        {{ $login->logged_in_at->format('d/m/Y H:i') }}
                    </td>
                    <td class="px-5 py-4">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="font-bold text-gray-900 dark:text-white">{{ $login->user?->name ?? 'Usuario eliminado' }}</p>
                            @if($login->user?->blocked_at)
                                <span class="rounded-full bg-red-100 px-2 py-1 text-[10px] font-black uppercase text-red-800">Bloqueada</span>
                            @endif
                        </div>
                        <p class="text-gray-500">{{ $login->user?->email }}</p>
                    </td>
                    <td class="px-5 py-4">
                        <p class="font-semibold text-gray-800 dark:text-gray-200">{{ $login->device_label ?: 'Dispositivo desconocido' }}</p>
                        <p class="font-mono text-xs text-gray-500">{{ $login->device_code }}</p>
                        <p class="mt-1 max-w-xs truncate text-xs text-gray-400" title="{{ $login->user_agent }}">{{ $login->user_agent }}</p>
                    </td>
                    <td class="px-5 py-4 text-gray-600 dark:text-gray-300">
                        {{ $login->ip_address ?: '—' }}
                        <br>
                        <span class="text-xs text-gray-500">{{ $login->approx_location ?: '—' }}</span>
                    </td>
                    <td class="px-5 py-4">
                        <div class="flex flex-col gap-1">
                            <span class="w-fit rounded-full px-3 py-1 text-xs font-black {{ $login->risk_score >= 60 ? 'bg-red-100 text-red-800' : ($login->risk_score >= 30 ? 'bg-amber-100 text-amber-800' : 'bg-gray-100 text-gray-700') }}">
                                Riesgo {{ $login->risk_score }}
                            </span>
                            @if($login->is_new_device)
                                <span class="w-fit rounded-full bg-indigo-100 px-3 py-1 text-xs font-bold text-indigo-800">Equipo nuevo</span>
                            @endif
                            @if($login->simultaneous_devices_count > 0)
                                <span class="w-fit rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-800">{{ $login->simultaneous_devices_count }} simultáneo(s)</span>
                            @endif
                            @if($login->shared_accounts_count > 0)
                                <span class="w-fit rounded-full bg-orange-100 px-3 py-1 text-xs font-bold text-orange-800">{{ $login->shared_accounts_count }} cuenta(s) cruzadas</span>
                            @endif
                            @foreach(($login->risk_reasons ?? []) as $reason)
                                <p class="max-w-sm text-xs text-gray-500">• {{ $reason }}</p>
                            @endforeach
                        </div>
                    </td>
                    <td class="px-5 py-4">
                        @if($login->logged_out_at)
                            <span class="rounded-full bg-gray-100 px-2 py-1 text-xs text-gray-700">Sesión cerrada</span>
                        @elseif($login->last_seen_at?->gte(now()->subMinutes(15)))
                            <span class="rounded-full bg-green-100 px-2 py-1 text-xs font-bold text-green-800">Activa reciente</span>
                        @else
                            <span class="rounded-full bg-blue-100 px-2 py-1 text-xs text-blue-800">Sin cierre registrado</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-5 py-10 text-center text-sm font-semibold text-gray-500">No hay accesos para mostrar en esta subsección.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="border-t border-gray-200 px-6 py-4 dark:border-gray-700">
    {{ $logins->appends(request()->except($pageName))->links() }}
</div>
