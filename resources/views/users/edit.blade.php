<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <a href="{{ route('users.index') }}" class="flex h-10 w-10 items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-500 shadow-sm transition hover:border-indigo-200 hover:text-indigo-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
            </a>
            <div><p class="text-[10px] font-black uppercase tracking-[0.22em] text-indigo-500">Administración de usuarios</p><h2 class="text-xl font-black tracking-tight text-gray-900 dark:text-white">Editar usuario</h2></div>
        </div>
    </x-slot>
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
                                <input id="status" type="text" value="{{ match ($user->status) { 'pending' => 'Pendiente de primer acceso', 'active' => 'Activo', 'disabled' => 'Deshabilitado', default => ucfirst($user->status) } }}" readonly aria-readonly="true" class="block w-full cursor-not-allowed rounded-2xl border-gray-200 bg-gray-100 px-4 py-3 font-semibold text-gray-600 shadow-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-400">
                                <p class="ml-1 mt-2 text-xs text-gray-400">El estado se administra desde las acciones de la lista de usuarios.</p>
                            </div>
                        </div>
                    </div>
                </section>
                <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <a href="{{ route('users.index') }}" class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-6 py-3 text-xs font-black uppercase tracking-widest text-gray-600 shadow-sm transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Cancelar</a>
                    <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-8 py-3 text-xs font-black uppercase tracking-widest text-white shadow-lg shadow-indigo-500/25 transition hover:bg-indigo-700 focus:ring-4 focus:ring-indigo-500/20">Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>