<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Profile;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProfileManagementController extends Controller
{
    public function index()
    {
        $profiles = Profile::withCount(['users', 'permissions'])->get();
        return view('profiles.index', compact('profiles'));
    }

    public function create()
    {
        $permissions = Permission::all()->groupBy('module');
        return view('profiles.create', compact('permissions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $profile = Profile::create([
            'name' => Str::slug($request->display_name),
            'display_name' => $request->display_name,
            'description' => $request->description,
        ]);

        if ($request->has('permissions')) {
            $profile->permissions()->sync($request->permissions);
        }

        return redirect()->route('profiles.index')->with('success', 'Perfil creado exitosamente.');
    }

    public function edit(Profile $profile)
    {
        $permissions = Permission::all()->groupBy('module');
        $profilePermissions = $profile->permissions->pluck('id')->toArray();
        return view('profiles.edit', compact('profile', 'permissions', 'profilePermissions'));
    }

    public function update(Request $request, Profile $profile)
    {
        $request->validate([
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $profile->update([
            'display_name' => $request->display_name,
            'description' => $request->description,
        ]);

        if ($request->has('permissions')) {
            $profile->permissions()->sync($request->permissions);
        } else {
            $profile->permissions()->detach();
        }

        return redirect()->route('profiles.index')->with('success', 'Perfil actualizado exitosamente.');
    }

    public function destroy(Profile $profile)
    {
        if ($profile->users()->count() > 0) {
            return back()->with('error', 'No se puede eliminar un perfil que tiene usuarios asociados.');
        }

        if (in_array($profile->name, ['admin', 'director', 'accountant'])) {
            return back()->with('error', 'Los perfiles del sistema no pueden ser eliminados.');
        }

        $profile->delete();
        return redirect()->route('profiles.index')->with('success', 'Perfil eliminado exitosamente.');
    }
}
