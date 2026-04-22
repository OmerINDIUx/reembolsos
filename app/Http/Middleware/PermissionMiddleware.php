<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        if (!Auth::check()) {
            return redirect('login');
        }

        $user = Auth::user();

        // Check if user has the requested permission
        if ($user->canPerform($permission)) {
            // Special restriction for admin_view (Lectura)
            if ($user->isAdminView()) {
                if (!$request->isMethod('GET') && !$request->isMethod('HEAD')) {
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
