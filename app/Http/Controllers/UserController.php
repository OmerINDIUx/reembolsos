<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Mail\UserInvitation;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Models\Profile;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = User::with('director')->orderBy('name');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->whereNull('invitation_token');
            } elseif ($request->status === 'inactive') {
                $query->whereNotNull('invitation_token');
            }
        }

        $users = $query->paginate(10)->appends($request->all());
        return view('users.index', compact('users'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $directors = User::whereIn('role', ['admin', 'director'])->get();
        $profiles = $this->availableProfilesFor(Auth::user());
        return view('users.create', compact('directors', 'profiles'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'string', 'email', 'max:255', 'unique:users',
                function ($attribute, $value, $fail) {
                    $blockedDomains = ['gmail.com', 'outlook.com', 'hotmail.com', 'live.com', 'icloud.com', 'yahoo.com', 'msn.com'];
                    $domain = substr(strrchr($value, "@"), 1);
                    if (in_array(strtolower($domain), $blockedDomains)) {
                        $fail('No se permiten correos personales (Gmail, Outlook, etc.). Por favor usa un correo empresarial.');
                    }
                },
            ],
            'role' => ['nullable', 'string'],
            'profile_id' => ['required', 'exists:profiles,id'],
        ]);

        $profile = Profile::findOrFail($request->profile_id);
        if ($this->isAdminProfile($profile) && !Auth::user()->isAdmin()) {
            return back()
                ->withInput()
                ->with('error', 'Solo un administrador puede crear otro usuario administrador.');
        }

        $token = Str::random(64);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => null, // Password will be set via invitation
            'role' => in_array($profile->name, ['admin', 'admin_view', 'director', 'control_obra', 'director_ejecutivo', 'accountant', 'direccion', 'tesoreria', 'user']) ? $profile->name : 'user',
            'profile_id' => $request->profile_id,
            'invitation_token' => $token,
            'invitation_sent_at' => null,
        ]);

        if (!$this->sendInvitationEmail($user)) {
            return redirect()
                ->route('users.index')
                ->with('error', 'El usuario fue creado, pero no se pudo enviar la invitación. Revisa la configuración de correo en el servidor.');
        }

        return redirect()->route('users.index')->with('success', 'Usuario creado exitosamente. Se ha enviado una invitación por correo.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, User $user)
    {
        $periods = \App\Models\Reimbursement::getAvailableTimePeriods();
        $user->load(['director', 'subordinates', 'costCenters', 'substitutes.user']);

        // 1. Personal Spending Stats
        $pendingQuery = $user->reimbursements()->applyTimeFilters($request)->whereNotIn('status', ['aprobado', 'rechazado', 'borrador']);
        $approvedQuery = $user->reimbursements()->applyTimeFilters($request)->where('status', 'aprobado');

        $stats = [
            'pending_count' => (clone $pendingQuery)->count(),
            'pending_amount' => (clone $pendingQuery)->sum(DB::raw('total + COALESCE(propina, 0)')),
            'approved_count' => (clone $approvedQuery)->count(),
            'approved_amount' => (clone $approvedQuery)->sum(DB::raw('total + COALESCE(propina, 0)')),
            'rejected_count' => $user->reimbursements()->applyTimeFilters($request)->where('status', 'rechazado')->count(),
        ];

        // 2. Category Breakdown (Personal)
        $categoryBreakdown = $user->reimbursements()
            ->applyTimeFilters($request)
            ->where('status', '!=', 'borrador')
            ->select('category', DB::raw('sum(total + COALESCE(propina, 0)) as amount'), DB::raw('count(*) as count'))
            ->groupBy('category')
            ->orderBy('amount', 'desc')
            ->get();

        // 3. Status Breakdown
        $statusBreakdown = $user->reimbursements()
            ->applyTimeFilters($request)
            ->where('status', '!=', 'borrador')
            ->select('status', DB::raw('count(*) as count'), DB::raw('sum(total + COALESCE(propina, 0)) as amount'))
            ->groupBy('status')
            ->get();

        // 4. Monthly Trend (Last 6 months)
        $monthlyTrend = $user->reimbursements()
            ->where('status', 'aprobado')
            ->select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                DB::raw('sum(total + COALESCE(propina, 0)) as amount')
            )
            ->where('created_at', '>=', now()->subMonths(6))
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->get();

        // 5. Recent Activity
        $recentReimbursements = $user->reimbursements()
            ->where('status', '!=', 'borrador')
            ->with(['costCenter', 'currentStep'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // 6. Approval Task Stats (if they are an approver)
        $pendingApprovalsCount = \App\Models\Reimbursement::whereHas('currentStep', function($q) use ($user) {
            $q->where('user_id', $user->id);
        })->whereNotIn('status', ['aprobado', 'rechazado', 'borrador'])->count();

        // 7. Substitutes
        $allUsers = User::where('id', '!=', $user->id)->orderBy('name')->get();

        return view('users.show', compact('user', 'stats', 'categoryBreakdown', 'statusBreakdown', 'monthlyTrend', 'recentReimbursements', 'pendingApprovalsCount', 'periods', 'allUsers'));
    }

    /**
     * Add a substitute for the user.
     */
    public function addSubstitute(Request $request, User $user)
    {
        $request->validate([
            'substitute_id' => 'required|exists:users,id|different:' . $user->id,
        ]);

        \App\Models\UserSubstitute::updateOrCreate(
            [
                'original_user_id' => $user->id,
                'user_id' => $request->substitute_id
            ],
            ['is_active' => true]
        );

        return back()->with('success', 'Sustituto asignado correctamente.');
    }

    /**
     * Toggle substitute status.
     */
    public function toggleSubstitute(User $user, $substituteId)
    {
        $sub = \App\Models\UserSubstitute::where('original_user_id', $user->id)
            ->where('user_id', $substituteId)
            ->firstOrFail();
            
        $sub->is_active = !$sub->is_active;
        $sub->save();

        return back()->with('success', 'Estado de la sustitución actualizado.');
    }

    /**
     * Remove a substitute.
     */
    public function removeSubstitute(User $user, $substituteId)
    {
        \App\Models\UserSubstitute::where('original_user_id', $user->id)
            ->where('user_id', $substituteId)
            ->delete();

        return back()->with('success', 'Sustitución eliminada.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        if ($this->isAdminUser($user) && !Auth::user()->isAdmin()) {
            abort(403, 'Solo un administrador puede editar usuarios administradores.');
        }

        $directors = User::whereIn('role', ['admin', 'director'])->where('id', '!=', $user->id)->get();
        $profiles = $this->availableProfilesFor(Auth::user(), $user->profile_id);
        return view('users.edit', compact('user', 'directors', 'profiles'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        if ($this->isAdminUser($user) && !Auth::user()->isAdmin()) {
            abort(403, 'Solo un administrador puede editar usuarios administradores.');
        }

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id),
                function ($attribute, $value, $fail) {
                    $blockedDomains = ['gmail.com', 'outlook.com', 'hotmail.com', 'live.com', 'icloud.com', 'yahoo.com', 'msn.com'];
                    $domain = substr(strrchr($value, "@"), 1);
                    if (in_array(strtolower($domain), $blockedDomains)) {
                        $fail('No se permiten correos personales (Gmail, Outlook, etc.). Por favor usa un correo empresarial.');
                    }
                },
            ],
            'role' => ['nullable', 'string'],
            'profile_id' => ['required', 'exists:profiles,id'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'clabe' => ['nullable', 'string', 'size:18', 'regex:/^[0-9]+$/'],
        ]);

        $profile = Profile::findOrFail($request->profile_id);
        if ($this->isAdminProfile($profile) && !Auth::user()->isAdmin()) {
            return back()
                ->withInput()
                ->with('error', 'Solo un administrador puede asignar un perfil administrador.');
        }

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'role' => in_array($profile->name, ['admin', 'admin_view', 'director', 'control_obra', 'director_ejecutivo', 'accountant', 'direccion', 'tesoreria', 'user']) ? $profile->name : 'user',
            'profile_id' => $request->profile_id,
            'bank_name' => $request->bank_name,
            'clabe' => $request->clabe,
        ];

        if ($request->filled('password')) {
            $request->validate([
                'password' => ['required', 'string', 'min:8', 'confirmed'],
            ]);
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return redirect()->route('users.index')->with('success', 'Usuario actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        if ($user->getKey() === Auth::id()) {
            return back()->with('error', 'No puedes eliminar tu propia cuenta.');
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'Usuario eliminado.');
    }

    /**
     * Resend invitation to a user.
     */
    public function resendInvitation(User $user)
    {
        if ($user->isRegistered()) {
            return back()->with('error', 'Este usuario ya ha completado su registro.');
        }

        if (!$user->invitation_token) {
            $user->update(['invitation_token' => Str::random(64)]);
        }

        if (!$this->sendInvitationEmail($user)) {
            return back()->with('error', 'No se pudo reenviar la invitación. Revisa la configuración de correo en el servidor.');
        }

        return back()->with('success', 'La invitación ha sido reenviada exitosamente.');
    }

    private function sendInvitationEmail(User $user): bool
    {
        try {
            Mail::to($user->email)->send(new UserInvitation($user));
            $user->forceFill(['invitation_sent_at' => now()])->save();

            return true;
        } catch (\Throwable $e) {
            Log::error('Error enviando invitación de usuario.', [
                'user_id' => $user->id,
                'email' => $user->email,
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function availableProfilesFor(User $actor, ?int $currentProfileId = null)
    {
        $query = Profile::orderBy('display_name');

        if (!$actor->isAdmin() && $currentProfileId) {
            $query->where(function ($profiles) use ($currentProfileId) {
                $profiles->whereNotIn('name', ['admin', 'admin_view'])
                    ->orWhere('id', $currentProfileId);
            });
        } elseif (!$actor->isAdmin()) {
            $query->whereNotIn('name', ['admin', 'admin_view']);
        }

        return $query->get();
    }

    private function isAdminProfile(Profile $profile): bool
    {
        return in_array($profile->name, ['admin', 'admin_view'], true);
    }

    private function isAdminUser(User $user): bool
    {
        return in_array($user->role, ['admin', 'admin_view'], true)
            || ($user->profile && $this->isAdminProfile($user->profile));
    }
}
