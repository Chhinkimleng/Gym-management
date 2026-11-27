<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    protected $redirectTo = '/dashboard';

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }

    protected function authenticated(Request $request, $user)
    {
        if (!$user->is_active) {
            auth()->logout();
            return redirect()->route('login')
                ->withErrors(['email' => 'Your account is deactivated. Contact an administrator.']);
        }

        $user->forceFill(['last_login_at' => now()])->saveQuietly();
        ActivityLog::record('auth.login', "User {$user->name} signed in.");

        return redirect()->intended($this->redirectPath());
    }

    public function demoLogin(Request $request)
    {
        $validated = $request->validate([
            'role' => ['required', 'in:admin,staff'],
        ]);

        $email = $validated['role'] === User::ROLE_ADMIN ? 'admin@example.com' : 'staff@example.com';

        $user = User::where('email', $email)->where('is_active', true)->first()
            ?? User::where('role', $validated['role'])->where('is_active', true)->first();

        if (!$user) {
            return redirect()->route('login')
                ->withErrors(['email' => 'Demo ' . $validated['role'] . ' account is not available.']);
        }

        Auth::login($user);
        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->saveQuietly();
        ActivityLog::record('auth.demo_login', "Demo sign-in as {$user->role} ({$user->name}).");

        return redirect()->route('dashboard');
    }
}
