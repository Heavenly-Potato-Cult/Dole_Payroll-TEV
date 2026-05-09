<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\SharedKernel\Models\Employee;
use App\Models\User;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            return redirect()->intended(route('payroll.dashboard'));
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => 'The provided credentials do not match our records.']);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }

    /**
     * Payroll HRIS SSO callback.
     * Officers go to dashboard, employees go to my-payslip.
     */
    public function hrisAuth(Request $request)
    {
        $hrisUser = session('hris_user');

        if (!$hrisUser) {
            return redirect()->route('login')->with('error', 'HRIS authentication failed.');
        }

        $user = $this->resolveHrisUser($hrisUser);

        // Determine redirect based on role
        $isEmployee = $user->hasRole('employee');
        $isOfficer = $user->hasAnyRole(\App\SharedKernel\Services\RoleService::getRoleGroup('payroll'));

        if ($isEmployee && !$isOfficer) {
            // Pure employee - redirect to my-payslip
            $redirectTo = route('my-payslip');
        } else {
            // Officer/staff - redirect to dashboard
            $redirectTo = route('payroll.dashboard');
        }

        return $this->handleHrisAuth($request, $redirectTo);
    }

    /**
     * TEV HRIS SSO callback.
     */
    public function tevHrisAuth(Request $request)
    {
        return $this->handleHrisAuth($request, route('tev.dashboard'));
    }

    // ── Shared logic ──────────────────────────────────────────────────────────

    /**
     * Core HRIS SSO handler. Resolves or auto-provisions the user,
     * logs them in, and redirects to the given destination.
     */
    private function handleHrisAuth(Request $request, string $redirectTo)
    {
        $hrisUser = session('hris_user');

        if (!$hrisUser) {
            return redirect()->route('login')->with('error', 'HRIS authentication failed.');
        }

        \Log::info('HRIS Authentication', [
            'employee_id' => $hrisUser['employee_id'],
            'name'        => $hrisUser['name'],
            'department'  => $hrisUser['department'],
            'redirect_to' => $redirectTo,
        ]);

        $user = $this->resolveHrisUser($hrisUser);

        Auth::login($user);

        session([
            'hris_employee_id'  => $hrisUser['employee_id'],
            'hris_full_profile' => $hrisUser['full_profile'],
        ]);

        return redirect($redirectTo)->with('success', 'Welcome from HRIS, ' . $hrisUser['name'] . '!');
    }

    /**
     * Find or auto-provision a User from HRIS data.
     *
     * Resolution order:
     *  1. Employee record found + linked User + name matches → registered user (keep roles)
     *  2. Employee record found + linked User + name mismatch → treat as unregistered
     *  3. Anything else → auto-provision with 'employee' role only
     */
    private function resolveHrisUser(array $hrisUser): User
    {
        $employee = Employee::where('employee_no', $hrisUser['employee_id'])->first();

        if ($employee) {
            $user = User::where('employee_id', $employee->id)->first();

            if ($user) {
                if ($this->namesMatch($user->name, $hrisUser['name'])) {
                    // Registered system user — log in with their assigned roles
                    \Log::info('HRIS: registered user matched', [
                        'employee_id' => $hrisUser['employee_id'],
                        'user_id'     => $user->id,
                        'roles'       => $user->getRoleNames()->toArray(),
                    ]);

                    // Ensure employee link is set
                    if (!$user->employee_id) {
                        $user->employee()->associate($employee);
                        $user->save();
                    }

                    return $user;
                }

                // Name mismatch — log and fall through to auto-provision
                \Log::warning('HRIS: name mismatch, auto-provisioning new user', [
                    'employee_id' => $hrisUser['employee_id'],
                    'hris_name'   => $hrisUser['name'],
                    'system_name' => $user->name,
                ]);
            }
        }

        // Auto-provision: create a new user with employee role only
        $user = User::create([
            'name'        => $hrisUser['name'],
            'email'       => null,
            'password'    => bcrypt(uniqid()),
            'employee_id' => $employee?->id,
        ]);

        $user->assignRole('employee');

        \Log::info('HRIS: user auto-provisioned as employee', [
            'employee_id' => $hrisUser['employee_id'],
            'name'        => $hrisUser['name'],
        ]);

        return $user;
    }

    /**
     * Match first + last name only, ignoring middle initials and casing.
     */
    private function namesMatch(string $systemName, string $hrisName): bool
    {
        $extract = function (string $name): array {
            $parts = explode(' ', trim($name));
            return [
                'first' => strtolower($parts[0] ?? ''),
                'last'  => strtolower(end($parts)),
            ];
        };

        $system = $extract($systemName);
        $hris   = $extract($hrisName);

        return $system['first'] === $hris['first']
            && $system['last']  === $hris['last'];
    }
}