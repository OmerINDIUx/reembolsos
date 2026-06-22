<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeviceLogin;
use App\Models\AccountBlockEvent;
use App\Models\User;
use App\Services\AccountBlockService;
use App\Support\AccountBlockReasons;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DeviceAuditController extends Controller
{
    public function index(Request $request): View
    {
        $days = (int) $request->integer('days', 30);
        $days = in_array($days, [7, 15, 30, 60, 90], true) ? $days : 30;
        $since = now()->subDays($days);
        $search = trim((string) $request->input('search'));

        $sharedDevices = DeviceLogin::query()
            ->select('device_hash')
            ->selectRaw('COUNT(*) as login_count')
            ->selectRaw('COUNT(DISTINCT user_id) as user_count')
            ->selectRaw('MAX(last_seen_at) as latest_activity')
            ->selectRaw('MAX(risk_score) as max_risk_score')
            ->where('last_seen_at', '>=', $since)
            ->groupBy('device_hash')
            ->havingRaw('COUNT(DISTINCT user_id) > 1')
            ->when($search !== '', function ($query) use ($search) {
                $query->whereExists(function ($subQuery) use ($search) {
                    $subQuery->select(DB::raw(1))
                        ->from('device_logins as dl_search')
                        ->join('users as u_search', 'u_search.id', '=', 'dl_search.user_id')
                        ->whereColumn('dl_search.device_hash', 'device_logins.device_hash')
                        ->where(function ($innerQuery) use ($search) {
                            $innerQuery->where('u_search.name', 'like', "%{$search}%")
                                ->orWhere('u_search.email', 'like', "%{$search}%")
                                ->orWhere('dl_search.ip_address', 'like', "%{$search}%")
                                ->orWhere('dl_search.device_label', 'like', "%{$search}%");
                        });
                });
            })
            ->orderByDesc('latest_activity')
            ->paginate(12, ['*'], 'shared_page')
            ->through(function ($device) use ($since) {
                $logins = DeviceLogin::with('user:id,name,email')
                    ->where('device_hash', $device->device_hash)
                    ->where('last_seen_at', '>=', $since)
                    ->latest('last_seen_at')
                    ->get();

                $device->users = $logins->unique('user_id')->pluck('user')->filter()->values();
                $device->label = $logins->first()?->device_label;
                $device->ip_addresses = $logins->pluck('ip_address')->filter()->unique()->take(3)->values();
                $device->locations = $logins->pluck('approx_location')->filter()->unique()->take(3)->values();

                return $device;
            });

        $riskUsers = DeviceLogin::query()
            ->join('users', 'users.id', '=', 'device_logins.user_id')
            ->select('users.id', 'users.name', 'users.email', 'users.blocked_at', 'users.blocked_reason_message')
            ->selectRaw('COUNT(DISTINCT device_logins.device_hash) as device_count')
            ->selectRaw('COUNT(device_logins.id) as login_count')
            ->selectRaw('MAX(device_logins.risk_score) as max_risk_score')
            ->selectRaw('SUM(CASE WHEN device_logins.is_new_device = 1 THEN 1 ELSE 0 END) as new_device_count')
            ->selectRaw('SUM(CASE WHEN device_logins.simultaneous_devices_count > 0 THEN 1 ELSE 0 END) as simultaneous_count')
            ->selectRaw('MAX(device_logins.shared_accounts_count) as shared_accounts_count')
            ->selectRaw('MAX(device_logins.last_seen_at) as latest_activity')
            ->where('device_logins.last_seen_at', '>=', $since)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery->where('users.name', 'like', "%{$search}%")
                        ->orWhere('users.email', 'like', "%{$search}%")
                        ->orWhere('device_logins.ip_address', 'like', "%{$search}%")
                        ->orWhere('device_logins.device_label', 'like', "%{$search}%");
                });
            })
            ->groupBy('users.id', 'users.name', 'users.email', 'users.blocked_at', 'users.blocked_reason_message')
            ->havingRaw('MAX(device_logins.risk_score) >= 30 OR COUNT(DISTINCT device_logins.device_hash) >= 3 OR SUM(CASE WHEN device_logins.simultaneous_devices_count > 0 THEN 1 ELSE 0 END) > 0')
            ->orderByDesc('max_risk_score')
            ->orderByDesc('shared_accounts_count')
            ->orderByDesc('simultaneous_count')
            ->orderByDesc('device_count')
            ->orderByDesc('latest_activity')
            ->paginate(15, ['*'], 'risk_page');

        $simultaneousLogins = DeviceLogin::with('user:id,name,email,blocked_at')
            ->where('last_seen_at', '>=', $since)
            ->where('simultaneous_devices_count', '>', 0)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery->where('ip_address', 'like', "%{$search}%")
                        ->orWhere('device_label', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->latest('logged_in_at')
            ->paginate(15, ['*'], 'simultaneous_page');

        $newDeviceLogins = DeviceLogin::with('user:id,name,email,blocked_at')
            ->where('last_seen_at', '>=', $since)
            ->where('is_new_device', true)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery->where('ip_address', 'like', "%{$search}%")
                        ->orWhere('device_label', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->latest('logged_in_at')
            ->paginate(15, ['*'], 'new_device_page');

        $knownDevices = DeviceLogin::query()
            ->join('users', 'users.id', '=', 'device_logins.user_id')
            ->select('users.id as user_id', 'users.name', 'users.email', 'device_logins.device_hash')
            ->selectRaw('MAX(device_logins.device_label) as device_label')
            ->selectRaw('MAX(device_logins.ip_address) as last_ip_address')
            ->selectRaw('MAX(device_logins.approx_location) as approx_location')
            ->selectRaw('MAX(device_logins.last_seen_at) as latest_activity')
            ->selectRaw('COUNT(device_logins.id) as login_count')
            ->selectRaw('MAX(device_logins.risk_score) as max_risk_score')
            ->where('device_logins.last_seen_at', '>=', $since)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery->where('users.name', 'like', "%{$search}%")
                        ->orWhere('users.email', 'like', "%{$search}%")
                        ->orWhere('device_logins.ip_address', 'like', "%{$search}%")
                        ->orWhere('device_logins.device_label', 'like', "%{$search}%");
                });
            })
            ->groupBy('users.id', 'users.name', 'users.email', 'device_logins.device_hash')
            ->orderByDesc('latest_activity')
            ->paginate(25, ['*'], 'known_page');

        $recentLogins = DeviceLogin::with('user:id,name,email')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery->where('ip_address', 'like', "%{$search}%")
                        ->orWhere('device_label', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->latest('logged_in_at')
            ->paginate(25, ['*'], 'recent_page');

        $users = User::with('blockedByUser:id,name')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderByRaw('blocked_at IS NULL')
            ->orderBy('name')
            ->paginate(20, ['*'], 'users_page');

        $blockEvents = AccountBlockEvent::with([
            'user:id,name,email',
            'actor:id,name',
        ])->latest()->limit(15)->get();

        $blockReasons = AccountBlockReasons::all();

        $summary = [
            'logins_today' => DeviceLogin::where('logged_in_at', '>=', today())->count(),
            'active_devices' => DeviceLogin::where('last_seen_at', '>=', $since)->distinct()->count('device_hash'),
            'shared_devices' => DeviceLogin::where('last_seen_at', '>=', $since)
                ->select('device_hash')
                ->groupBy('device_hash')
                ->havingRaw('COUNT(DISTINCT user_id) > 1')
                ->get()
                ->count(),
            'risk_users' => DeviceLogin::where('last_seen_at', '>=', $since)
                ->where('risk_score', '>=', 30)
                ->distinct()
                ->count('user_id'),
            'simultaneous_logins' => DeviceLogin::where('last_seen_at', '>=', $since)
                ->where('simultaneous_devices_count', '>', 0)
                ->count(),
            'new_devices' => DeviceLogin::where('last_seen_at', '>=', $since)
                ->where('is_new_device', true)
                ->count(),
        ];

        return view('admin.device-audit.index', compact(
            'days',
            'search',
            'sharedDevices',
            'riskUsers',
            'simultaneousLogins',
            'newDeviceLogins',
            'knownDevices',
            'recentLogins',
            'summary',
            'users',
            'blockEvents',
            'blockReasons'
        ));
    }

    public function block(Request $request, User $user, AccountBlockService $service): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', Rule::in(array_keys(AccountBlockReasons::all()))],
        ]);

        $service->block($user, $request->user(), $validated['reason'], $request);

        return back()->with('success', "La cuenta de {$user->name} fue bloqueada y sus sesiones fueron cerradas.");
    }

    public function unblock(Request $request, User $user, AccountBlockService $service): RedirectResponse
    {
        $service->unblock($user, $request->user(), $request);

        return back()->with('success', "La cuenta de {$user->name} fue desbloqueada.");
    }
}
