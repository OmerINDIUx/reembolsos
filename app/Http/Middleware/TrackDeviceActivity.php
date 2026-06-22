<?php

namespace App\Http\Middleware;

use App\Models\DeviceLogin;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class TrackDeviceActivity
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->user() && Schema::hasTable('device_logins')) {
            $loginId = $request->session()->get('device_login_id');

            $lastTouch = $request->session()->get('device_activity_touched_at');

            if ($loginId && (! $lastTouch || now()->timestamp - (int) $lastTouch >= 300)) {
                DeviceLogin::whereKey($loginId)
                    ->where('user_id', $request->user()->id)
                    ->update(['last_seen_at' => now()]);

                $request->session()->put('device_activity_touched_at', now()->timestamp);
            }
        }

        return $response;
    }
}
