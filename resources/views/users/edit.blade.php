<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <a href="{{ route('users.index') }}" class="flex h-10 w-10 items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-500 shadow-sm transition hover:border-indigo-200 hover:text-indigo-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
            </a>
            <div><p class="text-[10px] font-black uppercase tracking-[0.22em] text-indigo-500">Administración de usuarios</p><h2 class="text-xl font-black tracking-tight text-gray-900 dark:text-white">Editar usuario</h2></div>
        </div>
    </x-slot>
    @php
        $selectedCostCenters = collect(old('cost_centers', $user->authorizedCostCenters->pluck('id')->all()))->map(fn ($id) => (int) $id)->all();
        $selectedPermissions = collect(old('permissions', $user->permissions->pluck('id')->all()))->map(fn ($id) => (int) $id)->all();
    @endphp
    <div class="min-h-screen bg-gray-50 py-12 dark:bg-gray-900">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <form action="{{ route('users.update', $user) }}" method="POST" class="space-y-8">
                @csrf
                @method('PUT')
                <section class="overflow-hidden rounded-[2rem] border border-indigo-100 bg-white shadow-xl shadow-indigo-950/5 dark:border-indigo-900/50 dark:bg-gray-800">
                    <div class="bg-gradient-to-r from-indigo-600 to-blue-600 px-8 py-7 text-white">
                        <div class="flex items-center gap-4">
                            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white/15 text-xl font-black ring-1 ring-white/20">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</div>
                            <div class="min-w-0"><h3 class="truncate text-lg font-black">{{ $user->name }}</h3><p class="mt-1 truncate text-sm font-medium text-indigo-100">{{ $user->email }}</p></div>
                        </div>
                    </div>
                    <div class="p-8">
                        <div class="mb-6"><p class="text-[10px] font-black uppercase tracking-[0.22em] text-indigo-500">Información de la cuenta</p><h3 class="mt-1 text-lg font-black text-gray-900 dark:text-white">Datos generales</h3></div>
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div>
                                <label for="name" class="mb-2 ml-1 block text-[10px] font-black uppercase tracking-widest text-gray-500 dark:text-gray-400">Nombre completo</label>
                                <input id="name" name="name" value="{{ old('name', $user->name) }}" required autocomplete="name" class="block w-full rounded-2xl border-gray-200 bg-gray-50 px-4 py-3 font-semibold text-gray-900 shadow-sm transition focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                                <x-input-error :messages="$errors->get('name')" class="mt-2" />
                            </div>
                            <div>
                                <label for="email" class="mb-2 ml-1 block text-[10px] font-black uppercase tracking-widest text-gray-500 dark:text-gray-400">Correo corporativo</label>
                                <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" required autocomplete="email" pattern=".+@(grupoindi\.com|construlerma\.com|archandel\.com)" title="Usa un correo @grupoindi.com, @construlerma.com o @archandel.com" class="block w-full rounded-2xl border-gray-200 bg-gray-50 px-4 py-3 font-semibold text-gray-900 shadow-sm transition focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                                <p class="ml-1 mt-2 text-xs text-gray-400">Dominios permitidos: @grupoindi.com, @construlerma.com y @archandel.com.</p>
                                <x-input-error :messages="$errors->get('email')" class="mt-2" />
                            </div>
                            <div>
                                <label for="profile_id" class="mb-2 ml-1 block text-[10px] font-black uppercase tracking-widest text-gray-500 dark:text-gray-400">Perfil</label>
                                <select id="profile_id" name="profile_id" required class="block w-full rounded-2xl border-gray-200 bg-gray-50 px-4 py-3 font-semibold text-gray-900 shadow-sm transition focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                                    @foreach($profiles as $profile)<option value="{{ $profile->id }}" @selected(old('profile_id', $user->profile_id) == $profile->id)>{{ $profile->display_name }}</option>@endforeach
                                </select>
                                <x-input-error :messages="$errors->get('profile_id')" class="mt-2" />
                            </div>
                            <div>
                                <label for="status" class="mb-2 ml-1 block text-[10px] font-black uppercase tracking-widest text-gray-500 dark:text-gray-400">Estado</label>
                                <select id="status" name="status" required class="block w-full rounded-2xl border-gray-200 bg-gray-50 px-4 py-3 font-semibold text-gray-900 shadow-sm transition focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                                    <option value="pending" @selected(old('status', $user->status) === 'pending')>Pendiente de primer acceso</option>
                                    <option value="active" @selected(old('status', $user->status) === 'active')>Activo</option>
                                    <option value="disabled" @selected(old('status', $user->status) === 'disabled')>Deshabilitado</option>
                                </select>
                                <x-input-error :messages="$errors->get('status')" class="mt-2" />
                            </div>
                        </div>
                    </div>
                </section>
                <section class="rounded-[2rem] border border-gray-200 bg-white p-8 shadow-lg shadow-gray-900/5 dark:border-gray-700 dark:bg-gray-800">
                    <div class="mb-6 flex items-start gap-4">
                        <div class="flex h-11 w-11 flex-none items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-300"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5" /></svg></div>
                        <div><h3 class="font-black text-gray-900 dark:text-white">Centros de costos</h3><p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Selecciona los centros que este usuario puede consultar y utilizar.</p></div>
                    </div>
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        @forelse($costCenters as $center)
                            <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-gray-200 bg-gray-50 p-4 transition hover:border-indigo-300 hover:bg-indigo-50/50 dark:border-gray-700 dark:bg-gray-900 dark:hover:border-indigo-700">
                                <input type="checkbox" name="cost_centers[]" value="{{ $center->id }}" @checked(in_array((int) $center->id, $selectedCostCenters, true)) class="mt-0.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <span class="min-w-0"><span class="block text-[10px] font-black uppercase tracking-widest text-indigo-500">{{ $center->code }}</span><span class="mt-1 block text-sm font-bold text-gray-800 dark:text-gray-200">{{ $center->name }}</span></span>
                            </label>
                        @empty
                            <p class="col-span-full rounded-2xl bg-gray-50 px-5 py-4 text-sm text-gray-500 dark:bg-gray-900 dark:text-gray-400">No hay centros de costos disponibles.</p>
                        @endforelse
                    </div>
                    <x-input-error :messages="$errors->get('cost_centers')" class="mt-3" />
                </section>
                <section class="rounded-[2rem] border border-gray-200 bg-white p-8 shadow-lg shadow-gray-900/5 dark:border-gray-700 dark:bg-gray-800">
                    <div class="mb-6 flex items-start gap-4">
                        <div class="flex h-11 w-11 flex-none items-center justify-center rounded-2xl bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-300"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622" /></svg></div>
                        <div><h3 class="font-black text-gray-900 dark:text-white">Permisos directos</h3><p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Complementan los permisos incluidos en el perfil asignado.</p></div>
                    </div>
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        @forelse($permissions as $permission)
                            <label class="flex cursor-pointer items-center gap-3 rounded-2xl border border-gray-200 bg-gray-50 p-4 transition hover:border-blue-300 hover:bg-blue-50/50 dark:border-gray-700 dark:bg-gray-900 dark:hover:border-blue-700">
                                <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" @checked(in_array((int) $permission->id, $selectedPermissions, true)) class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="text-sm font-bold text-gray-800 dark:text-gray-200">{{ $permission->display_name }}</span>
                            </label>
                        @empty
                            <p class="col-span-full rounded-2xl bg-gray-50 px-5 py-4 text-sm text-gray-500 dark:bg-gray-900 dark:text-gray-400">No hay permisos directos disponibles.</p>
                        @endforelse
                    </div>
                    <x-input-error :messages="$errors->get('permissions')" class="mt-3" />
                </section>
                <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <a href="{{ route('users.index') }}" class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-6 py-3 text-xs font-black uppercase tracking-widest text-gray-600 shadow-sm transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Cancelar</a>
                    <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-8 py-3 text-xs font-black uppercase tracking-widest text-white shadow-lg shadow-indigo-500/25 transition hover:bg-indigo-700 focus:ring-4 focus:ring-indigo-500/20">Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>