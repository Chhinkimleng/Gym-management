<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Setting;
use App\Models\User;
use App\Services\BackupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class SettingsController extends Controller
{
    public function index()
    {
        $settings = Setting::defaults();
        foreach (array_keys($settings) as $key) {
            $settings[$key] = Setting::getValue($key, $settings[$key]);
        }

        $users = auth()->user()->isAdmin()
            ? User::orderBy('role')->orderBy('name')->get()
            : collect();

        $activityLogs = auth()->user()->isAdmin()
            ? ActivityLog::with('user')->latest()->take(25)->get()
            : collect();

        return view('settings.index', compact('settings', 'users', 'activityLogs'));
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'current_password' => ['nullable', 'required_with:new_password', 'current_password'],
            'new_password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $payload = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
        ];

        if (!empty($validated['new_password'])) {
            $payload['password'] = Hash::make($validated['new_password']);
        }

        $user->update($payload);
        ActivityLog::record('profile.updated', "User {$user->name} updated their account profile.", $user);

        return back()->with('success', 'Your profile settings have been saved.');
    }

    public function updateGymSettings(Request $request)
    {
        $validated = $request->validate([
            'gym_name' => ['required', 'string', 'max:120'],
            'gym_tagline' => ['nullable', 'string', 'max:255'],
            'gym_email' => ['nullable', 'email', 'max:255'],
            'gym_phone' => ['nullable', 'string', 'max:40'],
            'gym_address' => ['nullable', 'string', 'max:300'],
            'currency_symbol' => ['required', 'string', 'max:5'],
            'currency_code' => ['required', 'string', 'max:10'],
            'membership_overlap_policy' => ['required', 'in:prevent_overlap,queue_after_active,allow_multiple'],
            'expiring_notice_days' => ['required', 'integer', 'min:1', 'max:60'],
            'payment_methods' => ['required', 'array', 'min:1'],
            'payment_methods.*' => ['string', 'max:50'],
            'receipt_footer' => ['nullable', 'string', 'max:400'],
        ]);

        foreach ($validated as $key => $value) {
            Setting::setValue($key, $value, 'gym');
        }

        ActivityLog::record('settings.updated', 'Updated gym configuration and business policies.');

        return back()->with('success', 'Gym settings and policies updated.');
    }

    public function storeUser(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', 'in:admin,staff'],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'phone' => $validated['phone'] ?? null,
            'is_active' => true,
            'password' => Hash::make($validated['password']),
        ]);

        ActivityLog::record('user.created', "Created {$user->role} account for {$user->name}.", $user);

        return back()->with('success', "User {$user->name} ({$user->role}) created.");
    }

    public function toggleUserStatus(User $user)
    {
        if ((int) $user->id === (int) auth()->id()) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        $user->update(['is_active' => !$user->is_active]);
        $state = $user->is_active ? 'activated' : 'deactivated';

        ActivityLog::record('user.toggled', "{$state} user account {$user->name}.", $user);

        return back()->with('success', "Account {$user->name} {$state}.");
    }

    public function updateUserRole(Request $request, User $user)
    {
        if ((int) $user->id === (int) auth()->id()) {
            return back()->with('error', 'You cannot change your own role.');
        }

        $validated = $request->validate([
            'role' => ['required', 'in:admin,staff'],
        ]);

        $user->update(['role' => $validated['role']]);
        ActivityLog::record('user.role_updated', "Changed role of {$user->name} to {$user->role}.", $user);

        return back()->with('success', "Updated {$user->name}'s role to " . ucfirst($user->role) . ".");
    }

    public function downloadBackup(BackupService $backupService)
    {
        $snapshot = $backupService->createJsonSnapshot();
        $filename = 'gym-backup-' . now()->format('Y-m-d-His') . '.json';

        return response()->streamDownload(function () use ($snapshot) {
            echo json_encode($snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }, $filename, ['Content-Type' => 'application/json']);
    }
}
