<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!Auth::check()) {
            return redirect('login');
        }

        $user = Auth::user();

        // If no roles specified, just allow (basic auth check already passed)
        if (empty($roles)) {
            return $next($request);
        }

        // Check if user has one of the allowed roles
        if (in_array($user->role, $roles)) {
            
            // Special restriction for 'admin_view' (Solo Lectura)
            if ($user->role === 'admin_view') {
                // If it's a modification request (POST, PUT, PATCH, DELETE)
                if (!$request->isMethod('GET') && !$request->isMethod('HEAD')) {
                    // Exceptions: Logout, Profile Update, and Password Update
                    $allowedRoutes = ['logout', 'profile.update', 'password.update', 'password.force_change.store'];
                    
                    if (!$request->routeIs($allowedRoutes)) {
                        return back()->with('error', 'Su perfil es de SOLO LECTURA. No tiene permiso para realizar modificaciones.');
                    }
                }
            }
            
            return $next($request);
        }

        abort(403, 'No tienes permiso para acceder a esta sección.');
    }
}
