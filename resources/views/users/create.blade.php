<x-app-layout>
<x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">Agregar usuario</h2></x-slot>
<div class="py-12"><div class="max-w-4xl mx-auto sm:px-6 lg:px-8"><div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6 text-gray-900 dark:text-gray-100">
<form action="{{ route('users.store') }}" method="POST">@csrf
<div class="grid md:grid-cols-2 gap-4">
<div><label>Nombre</label><input name="name" value="{{ old('name') }}" required class="mt-1 block w-full rounded border-gray-300"></div>
<div><label>Correo corporativo</label><input type="email" name="email" value="{{ old('email') }}" required class="mt-1 block w-full rounded border-gray-300"><p class="text-xs text-gray-500">Se guardará sin espacios y en minúsculas.</p></div>
<div><label>Perfil</label><select name="profile_id" required class="mt-1 block w-full rounded border-gray-300"><option value="">Selecciona un perfil</option>@foreach($profiles as $profile)<option value="{{ $profile->id }}" @selected(old('profile_id') == $profile->id)>{{ $profile->display_name }}</option>@endforeach</select></div>
</div>
<div class="mt-6"><label class="font-semibold">Centros de costos</label><div class="grid md:grid-cols-2 gap-2 mt-2">@foreach($costCenters as $center)<label class="text-sm"><input type="checkbox" name="cost_centers[]" value="{{ $center->id }}" @checked(in_array($center->id, old('cost_centers', [])))> {{ $center->code }} — {{ $center->name }}</label>@endforeach</div></div>
<div class="mt-6"><label class="font-semibold">Permisos directos</label><div class="grid md:grid-cols-2 gap-2 mt-2">@foreach($permissions as $permission)<label class="text-sm"><input type="checkbox" name="permissions[]" value="{{ $permission->id }}" @checked(in_array($permission->id, old('permissions', [])))> {{ $permission->display_name }}</label>@endforeach</div></div>
<label class="flex items-center gap-2 mt-6"><input type="checkbox" name="send_invitation" value="1" @checked(old('send_invitation', true))> Enviar invitación por correo (usará Google o Microsoft según su dominio)</label>
<div class="mt-6 flex justify-end gap-3"><a href="{{ route('users.index') }}">Cancelar</a><button class="px-4 py-2 bg-gray-800 text-white rounded">Crear usuario</button></div>
@foreach(['name','email','profile_id'] as $field) @error($field)<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror @endforeach
</form></div></div></div>
</x-app-layout>