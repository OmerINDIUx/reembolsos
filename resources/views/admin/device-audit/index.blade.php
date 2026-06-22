<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-[10px] font-black uppercase tracking-[0.25em] text-red-500">Panel oculto · solo admin</p>
                <h2 class="mt-1 text-2xl font-black leading-tight text-gray-900 dark:text-white">
                    Seguridad de accesos
                </h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Detecta cuentas compartidas por señales: dispositivo, IP, navegador, simultaneidad y cruces entre usuarios.
                </p>
            </div>

            <form method="GET" action="{{ route('admin.device-audit.index') }}" class="flex flex-col gap-2 sm:flex-row">
                <select name="days" class="rounded-xl border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                    @foreach([7, 15, 30, 60, 90] as $option)
                        <option value="{{ $option }}" @selected($days === $option)>Últimos {{ $option }} días</option>
                    @endforeach
                </select>
                <input name="search" value="{{ $search }}" type="search" placeholder="Buscar usuario, correo, IP o navegador"
                    class="min-w-[280px] rounded-xl border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                <button class="rounded-xl bg-gray-900 px-5 py-2 text-sm font-black uppercase tracking-wide text-white hover:bg-black">
                    Buscar
                </button>
                @if($search !== '')
                    <a href="{{ route('admin.device-audit.index', ['days' => $days]) }}" class="rounded-xl bg-gray-100 px-5 py-2 text-center text-sm font-black uppercase tracking-wide text-gray-700 hover:bg-gray-200">
                        Limpiar
                    </a>
                @endif
            </form>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="rounded-2xl border border-green-200 bg-green-50 p-4 text-sm font-bold text-green-900">{{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm font-bold text-red-900">{{ $errors->first() }}</div>
            @endif

            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-950">
                <strong>Importante:</strong> estas señales priorizan investigación. Un dispositivo compartido es fuerte evidencia de cuenta compartida; una IP compartida puede ser normal en una oficina. La ubicación se guarda de forma conservadora: red local/privada, encabezados del proxy si existen, o IP pública pendiente de GeoIP.
            </div>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-6">
                @foreach([
                    ['label' => 'Cuentas en riesgo', 'value' => $summary['risk_users'], 'color' => 'text-red-700', 'bg' => 'bg-red-50'],
                    ['label' => 'Dispositivos compartidos', 'value' => $summary['shared_devices'], 'color' => 'text-orange-700', 'bg' => 'bg-orange-50'],
                    ['label' => 'Sesiones simultáneas', 'value' => $summary['simultaneous_logins'], 'color' => 'text-amber-700', 'bg' => 'bg-amber-50'],
                    ['label' => 'Equipos nuevos', 'value' => $summary['new_devices'], 'color' => 'text-indigo-700', 'bg' => 'bg-indigo-50'],
                    ['label' => 'Dispositivos activos', 'value' => $summary['active_devices'], 'color' => 'text-sky-700', 'bg' => 'bg-sky-50'],
                    ['label' => 'Accesos hoy', 'value' => $summary['logins_today'], 'color' => 'text-emerald-700', 'bg' => 'bg-emerald-50'],
                ] as $card)
                    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <p class="text-[10px] font-black uppercase tracking-widest text-gray-500">{{ $card['label'] }}</p>
                        <p class="mt-3 text-4xl font-black {{ $card['color'] }}">{{ number_format($card['value']) }}</p>
                    </div>
                @endforeach
            </div>

            <nav class="sticky top-0 z-10 -mx-4 overflow-x-auto border-y border-gray-200 bg-white/95 px-4 py-3 backdrop-blur dark:border-gray-700 dark:bg-gray-900/95 sm:rounded-2xl sm:border">
                <div class="flex min-w-max gap-2 text-xs font-black uppercase tracking-wide">
                    <a href="#riesgo" class="rounded-full bg-red-100 px-4 py-2 text-red-800">Riesgo primero</a>
                    <a href="#dispositivos-compartidos" class="rounded-full bg-orange-100 px-4 py-2 text-orange-800">Cruce de cuentas</a>
                    <a href="#simultaneas" class="rounded-full bg-amber-100 px-4 py-2 text-amber-800">Sesiones simultáneas</a>
                    <a href="#equipos-nuevos" class="rounded-full bg-indigo-100 px-4 py-2 text-indigo-800">Equipos nuevos</a>
                    <a href="#conocidos" class="rounded-full bg-sky-100 px-4 py-2 text-sky-800">Dispositivos conocidos</a>
                    <a href="#bloqueo" class="rounded-full bg-gray-100 px-4 py-2 text-gray-800">Bloqueos</a>
                </div>
            </nav>

            <section id="riesgo" class="overflow-hidden rounded-3xl border border-red-200 bg-white shadow-sm dark:border-red-900 dark:bg-gray-800">
                <div class="border-b border-red-100 bg-red-50 px-6 py-5 dark:border-red-900 dark:bg-red-950/30">
                    <h3 class="text-lg font-black text-red-950 dark:text-red-200">1. Cuentas con señales de violación</h3>
                    <p class="text-sm text-red-700 dark:text-red-300">Prioriza aquí. Combina dispositivo compartido, equipo nuevo, cambios frecuentes y simultaneidad.</p>
                </div>
                <div class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($riskUsers as $riskUser)
                        <div class="grid gap-5 p-6 xl:grid-cols-[minmax(260px,1fr)_minmax(420px,1.4fr)_minmax(320px,1fr)]">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="text-lg font-black text-gray-900 dark:text-white">{{ $riskUser->name }}</p>
                                    @if($riskUser->blocked_at)
                                        <span class="rounded-full bg-red-600 px-2 py-1 text-[10px] font-black uppercase text-white">Bloqueada</span>
                                    @endif
                                </div>
                                <p class="text-sm text-gray-500">{{ $riskUser->email }}</p>
                                <p class="mt-3 text-xs font-bold uppercase tracking-wide text-gray-400">Última actividad</p>
                                <p class="font-semibold text-gray-700 dark:text-gray-200">{{ \Illuminate\Support\Carbon::parse($riskUser->latest_activity)->format('d/m/Y H:i') }}</p>
                            </div>
                            <div class="grid gap-3 sm:grid-cols-5">
                                <div class="rounded-2xl bg-red-50 p-3 text-center">
                                    <p class="text-[10px] font-black uppercase text-red-500">Riesgo</p>
                                    <p class="text-2xl font-black text-red-700">{{ $riskUser->max_risk_score }}</p>
                                </div>
                                <div class="rounded-2xl bg-gray-50 p-3 text-center">
                                    <p class="text-[10px] font-black uppercase text-gray-500">Dispositivos</p>
                                    <p class="text-2xl font-black text-gray-800">{{ $riskUser->device_count }}</p>
                                </div>
                                <div class="rounded-2xl bg-indigo-50 p-3 text-center">
                                    <p class="text-[10px] font-black uppercase text-indigo-500">Nuevos</p>
                                    <p class="text-2xl font-black text-indigo-700">{{ $riskUser->new_device_count }}</p>
                                </div>
                                <div class="rounded-2xl bg-amber-50 p-3 text-center">
                                    <p class="text-[10px] font-black uppercase text-amber-500">Simult.</p>
                                    <p class="text-2xl font-black text-amber-700">{{ $riskUser->simultaneous_count }}</p>
                                </div>
                                <div class="rounded-2xl bg-orange-50 p-3 text-center">
                                    <p class="text-[10px] font-black uppercase text-orange-500">Cruces</p>
                                    <p class="text-2xl font-black text-orange-700">{{ $riskUser->shared_accounts_count }}</p>
                                </div>
                            </div>
                            <div>
                                @if($riskUser->blocked_at)
                                    <form method="POST" action="{{ route('admin.device-audit.unblock', $riskUser->id) }}" onsubmit="return confirm('¿Desbloquear esta cuenta?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="w-full rounded-xl bg-green-600 px-4 py-3 text-sm font-black uppercase text-white hover:bg-green-700">Desbloquear cuenta</button>
                                    </form>
                                @elseif((int) $riskUser->id === Auth::id())
                                    <p class="rounded-xl bg-gray-100 p-3 text-sm font-semibold text-gray-600">Tu cuenta admin no se puede bloquear desde aquí.</p>
                                @else
                                    <form method="POST" action="{{ route('admin.device-audit.block', $riskUser->id) }}" class="space-y-2" onsubmit="return confirm('¿Bloquear esta cuenta y cerrar sus sesiones?')">
                                        @csrf
                                        <select name="reason" required class="w-full rounded-xl border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                                            <option value="">Motivo del bloqueo</option>
                                            @foreach($blockReasons as $code => $message)
                                                <option value="{{ $code }}">{{ $message }}</option>
                                            @endforeach
                                        </select>
                                        <button class="w-full rounded-xl bg-red-600 px-4 py-3 text-sm font-black uppercase text-white hover:bg-red-700">Bloquear por uso indebido</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="p-10 text-center text-sm font-semibold text-gray-500">No hay cuentas con señales fuertes en este periodo.</p>
                    @endforelse
                </div>
                <div class="border-t border-gray-200 px-6 py-4 dark:border-gray-700">{{ $riskUsers->appends(request()->except('risk_page'))->links() }}</div>
            </section>

            <section id="dispositivos-compartidos" class="overflow-hidden rounded-3xl border border-orange-200 bg-white shadow-sm dark:border-orange-900 dark:bg-gray-800">
                <div class="border-b border-orange-100 bg-orange-50 px-6 py-5 dark:border-orange-900 dark:bg-orange-950/30">
                    <h3 class="text-lg font-black text-orange-950 dark:text-orange-200">2. Cruce de cuentas: mismo dispositivo con varios usuarios</h3>
                    <p class="text-sm text-orange-700 dark:text-orange-300">Esta es la señal más directa de contraseña compartida.</p>
                </div>
                <div class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($sharedDevices as $device)
                        <div class="grid gap-5 p-6 lg:grid-cols-[220px_1fr_260px]">
                            <div>
                                <span class="rounded-lg bg-gray-100 px-3 py-2 font-mono text-xs font-black text-gray-700">{{ strtoupper(substr($device->device_hash, 0, 10)) }}</span>
                                <p class="mt-3 font-bold text-gray-900 dark:text-white">{{ $device->label ?: 'Dispositivo desconocido' }}</p>
                                <p class="text-sm text-gray-500">{{ $device->login_count }} accesos · riesgo {{ $device->max_risk_score }}</p>
                            </div>
                            <div>
                                <p class="mb-2 text-xs font-black uppercase tracking-widest text-gray-500">{{ $device->user_count }} cuentas en el mismo dispositivo</p>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($device->users as $user)
                                        <span class="rounded-full bg-orange-100 px-3 py-1 text-sm font-semibold text-orange-900">{{ $user->name }} · {{ $user->email }}</span>
                                    @endforeach
                                </div>
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-300">
                                <p><strong>Última actividad:</strong> {{ \Illuminate\Support\Carbon::parse($device->latest_activity)->format('d/m/Y H:i') }}</p>
                                <p class="mt-2"><strong>IP:</strong> {{ $device->ip_addresses->join(', ') ?: '—' }}</p>
                                <p class="mt-2"><strong>Ubicación:</strong> {{ $device->locations->join(', ') ?: '—' }}</p>
                            </div>
                        </div>
                    @empty
                        <p class="p-10 text-center text-sm font-semibold text-gray-500">No hay dispositivos compartidos en este periodo.</p>
                    @endforelse
                </div>
                <div class="border-t border-gray-200 px-6 py-4 dark:border-gray-700">{{ $sharedDevices->appends(request()->except('shared_page'))->links() }}</div>
            </section>

            <section id="simultaneas" class="overflow-hidden rounded-3xl border border-amber-200 bg-white shadow-sm dark:border-amber-900 dark:bg-gray-800">
                <div class="border-b border-amber-100 bg-amber-50 px-6 py-5 dark:border-amber-900 dark:bg-amber-950/30">
                    <h3 class="text-lg font-black text-amber-950 dark:text-amber-200">3. Sesiones simultáneas</h3>
                    <p class="text-sm text-amber-700 dark:text-amber-300">Misma cuenta activa en otro dispositivo en una ventana aproximada de 15 minutos.</p>
                </div>
                @include('admin.device-audit.partials.login-table', ['logins' => $simultaneousLogins, 'pageName' => 'simultaneous_page'])
            </section>

            <section id="equipos-nuevos" class="overflow-hidden rounded-3xl border border-indigo-200 bg-white shadow-sm dark:border-indigo-900 dark:bg-gray-800">
                <div class="border-b border-indigo-100 bg-indigo-50 px-6 py-5 dark:border-indigo-900 dark:bg-indigo-950/30">
                    <h3 class="text-lg font-black text-indigo-950 dark:text-indigo-200">4. Equipos nuevos y segundo factor</h3>
                    <p class="text-sm text-indigo-700 dark:text-indigo-300">Cada equipo nuevo queda marcado, se notifica al usuario y aparece aquí como señal para revisión/segundo factor.</p>
                </div>
                @include('admin.device-audit.partials.login-table', ['logins' => $newDeviceLogins, 'pageName' => 'new_device_page'])
            </section>

            <section id="conocidos" class="overflow-hidden rounded-3xl border border-sky-200 bg-white shadow-sm dark:border-sky-900 dark:bg-gray-800">
                <div class="border-b border-sky-100 bg-sky-50 px-6 py-5 dark:border-sky-900 dark:bg-sky-950/30">
                    <h3 class="text-lg font-black text-sky-950 dark:text-sky-200">5. Dispositivos conocidos por usuario</h3>
                    <p class="text-sm text-sky-700 dark:text-sky-300">Inventario paginado para revisar sin cargar miles de usuarios de golpe.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                        <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500 dark:bg-gray-900">
                            <tr>
                                <th class="px-5 py-3">Usuario</th>
                                <th class="px-5 py-3">Dispositivo</th>
                                <th class="px-5 py-3">IP / ubicación</th>
                                <th class="px-5 py-3">Accesos</th>
                                <th class="px-5 py-3">Última actividad</th>
                                <th class="px-5 py-3">Riesgo</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse($knownDevices as $device)
                                <tr>
                                    <td class="px-5 py-4"><p class="font-bold text-gray-900 dark:text-white">{{ $device->name }}</p><p class="text-gray-500">{{ $device->email }}</p></td>
                                    <td class="px-5 py-4"><p class="font-semibold text-gray-800 dark:text-gray-200">{{ $device->device_label ?: '—' }}</p><p class="font-mono text-xs text-gray-500">{{ strtoupper(substr($device->device_hash, 0, 10)) }}</p></td>
                                    <td class="px-5 py-4 text-gray-600 dark:text-gray-300">{{ $device->last_ip_address ?: '—' }}<br><span class="text-xs text-gray-500">{{ $device->approx_location ?: '—' }}</span></td>
                                    <td class="px-5 py-4 font-bold">{{ $device->login_count }}</td>
                                    <td class="px-5 py-4 text-gray-600 dark:text-gray-300">{{ \Illuminate\Support\Carbon::parse($device->latest_activity)->format('d/m/Y H:i') }}</td>
                                    <td class="px-5 py-4"><span class="rounded-full px-3 py-1 text-xs font-black {{ $device->max_risk_score >= 60 ? 'bg-red-100 text-red-800' : ($device->max_risk_score >= 30 ? 'bg-amber-100 text-amber-800' : 'bg-gray-100 text-gray-700') }}">{{ $device->max_risk_score }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-5 py-10 text-center text-gray-500">No hay dispositivos en este periodo.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-gray-200 px-6 py-4 dark:border-gray-700">{{ $knownDevices->appends(request()->except('known_page'))->links() }}</div>
            </section>

            <section id="bloqueo" class="overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="border-b border-gray-200 px-6 py-5 dark:border-gray-700">
                    <h3 class="text-lg font-black text-gray-900 dark:text-white">6. Bloqueo manual de cuentas</h3>
                    <p class="text-sm text-gray-500">Búsqueda paginada. No carga todos los usuarios al mismo tiempo.</p>
                </div>
                <div class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($users as $user)
                        <div class="grid gap-4 p-5 lg:grid-cols-[minmax(260px,1fr)_minmax(360px,1.5fr)] lg:items-center">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="font-bold text-gray-900 dark:text-white">{{ $user->name }}</p>
                                    @if($user->isBlocked())
                                        <span class="rounded-full bg-red-100 px-2 py-1 text-xs font-bold text-red-800">Bloqueada</span>
                                    @else
                                        <span class="rounded-full bg-green-100 px-2 py-1 text-xs font-bold text-green-800">Activa</span>
                                    @endif
                                </div>
                                <p class="text-sm text-gray-500">{{ $user->email }}</p>
                                @if($user->isBlocked())
                                    <p class="mt-2 text-xs text-red-700">{{ $user->blocked_reason_message }}</p>
                                    <p class="mt-1 text-xs text-gray-500">Bloqueó: {{ $user->blockedByUser?->name ?? 'Administrador eliminado' }} · {{ $user->blocked_at->format('d/m/Y H:i') }}</p>
                                @endif
                            </div>
                            <div>
                                @if($user->isBlocked())
                                    <form method="POST" action="{{ route('admin.device-audit.unblock', $user) }}" onsubmit="return confirm('¿Desbloquear esta cuenta?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="rounded-xl bg-green-600 px-4 py-2 text-sm font-black text-white hover:bg-green-700">Desbloquear cuenta</button>
                                    </form>
                                @elseif($user->is(Auth::user()))
                                    <p class="text-sm font-medium text-gray-500">Tu cuenta administrativa no se puede bloquear desde aquí.</p>
                                @else
                                    <form method="POST" action="{{ route('admin.device-audit.block', $user) }}" class="flex flex-col gap-2 sm:flex-row" onsubmit="return confirm('¿Bloquear esta cuenta y cerrar sus sesiones?')">
                                        @csrf
                                        <select name="reason" required class="min-w-0 flex-1 rounded-xl border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                                            <option value="">Selecciona el motivo del bloqueo</option>
                                            @foreach($blockReasons as $code => $message)
                                                <option value="{{ $code }}">{{ $message }}</option>
                                            @endforeach
                                        </select>
                                        <button class="rounded-xl bg-red-600 px-4 py-2 text-sm font-black text-white hover:bg-red-700">Bloquear</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="border-t border-gray-200 px-6 py-4 dark:border-gray-700">{{ $users->appends(request()->except('users_page'))->links() }}</div>
            </section>

            <section class="overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="border-b border-gray-200 px-6 py-5 dark:border-gray-700">
                    <h3 class="text-lg font-black text-gray-900 dark:text-white">Historial reciente de accesos</h3>
                </div>
                @include('admin.device-audit.partials.login-table', ['logins' => $recentLogins, 'pageName' => 'recent_page'])
            </section>

            <section class="overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="border-b border-gray-200 px-6 py-5 dark:border-gray-700">
                    <h3 class="text-lg font-black text-gray-900 dark:text-white">Historial de bloqueos y desbloqueos</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                        <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500 dark:bg-gray-900">
                            <tr><th class="px-5 py-3">Fecha</th><th class="px-5 py-3">Cuenta</th><th class="px-5 py-3">Acción</th><th class="px-5 py-3">Administrador</th><th class="px-5 py-3">Motivo</th></tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse($blockEvents as $event)
                                <tr>
                                    <td class="whitespace-nowrap px-5 py-4 text-gray-500">{{ $event->created_at->format('d/m/Y H:i') }}</td>
                                    <td class="px-5 py-4"><p class="font-semibold text-gray-900 dark:text-white">{{ $event->user?->name ?? 'Usuario eliminado' }}</p><p class="text-gray-500">{{ $event->user?->email }}</p></td>
                                    <td class="px-5 py-4"><span class="rounded-full px-2 py-1 text-xs font-bold {{ $event->action === 'blocked' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' }}">{{ $event->action === 'blocked' ? 'Bloqueó' : 'Desbloqueó' }}</span></td>
                                    <td class="px-5 py-4 text-gray-700 dark:text-gray-300">{{ $event->actor?->name ?? 'Administrador eliminado' }}</td>
                                    <td class="max-w-md px-5 py-4 text-gray-600 dark:text-gray-300">{{ $event->reason_message ?: '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-5 py-8 text-center text-gray-500">Todavía no se han realizado bloqueos.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
