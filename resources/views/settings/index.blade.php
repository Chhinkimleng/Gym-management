@extends('layouts.app')

@section('title', __('Settings'))
@section('page-title', __('Settings'))
@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-[#202520]">{{ __('Dashboard') }}</a>
    <span>/</span>
    <span class="text-[#202520] font-medium">{{ __('Settings') }}</span>
@endsection

@section('content')
<div class="space-y-6">
    <div>
        <h2 class="font-display font-bold text-2xl text-[#202520]">{{ __('System Settings & Administration') }}</h2>
        <p class="text-sm text-[#858B84]">Manage your account, gym configuration, staff permissions, and database backups.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Personal Profile & Password -->
        <div class="lg:col-span-5 gym-card p-6 space-y-4">
            <h3 class="font-display font-bold text-base text-[#202520]">{{ __('My Account') }}</h3>
            <form action="{{ route('settings.profile.update') }}" method="POST" class="space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="gym-label">Full Name</label>
                    <input type="text" name="name" value="{{ old('name', Auth::user()->name) }}" required class="gym-input">
                </div>
                <div>
                    <label class="gym-label">Email Address</label>
                    <input type="email" name="email" value="{{ old('email', Auth::user()->email) }}" required class="gym-input">
                </div>
                <div>
                    <label class="gym-label">Phone</label>
                    <input type="text" name="phone" value="{{ old('phone', Auth::user()->phone) }}" class="gym-input">
                </div>
                <div class="pt-3 border-t border-[#E8EAE3] space-y-3">
                    <div class="text-xs font-bold uppercase text-[#858B84]">Change Password (Optional)</div>
                    <div>
                        <label class="gym-label">Current Password</label>
                        <input type="password" name="current_password" class="gym-input">
                    </div>
                    <div>
                        <label class="gym-label">New Password</label>
                        <input type="password" name="new_password" class="gym-input">
                    </div>
                    <div>
                        <label class="gym-label">Confirm New Password</label>
                        <input type="password" name="new_password_confirmation" class="gym-input">
                    </div>
                </div>
                <button type="submit" class="btn-lime w-full">Save Profile</button>
            </form>
        </div>

        @if(Auth::user()->isAdmin())
            <!-- Gym Business Configuration -->
            <div class="lg:col-span-7 gym-card p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="font-display font-bold text-base text-[#202520]">Gym Profile &amp; Business Policies</h3>
                    <a href="{{ route('settings.backup') }}" class="btn-dark py-1.5 px-3 text-xs">
                        <x-icon name="database" class="w-4 h-4 text-[#C5F467]" /> Download DB Backup
                    </a>
                </div>
                <form action="{{ route('settings.gym.update') }}" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="gym-label">Gym Name *</label>
                            <input type="text" name="gym_name" value="{{ old('gym_name', $settings['gym_name']) }}" required class="gym-input">
                        </div>
                        <div>
                            <label class="gym-label">Tagline</label>
                            <input type="text" name="gym_tagline" value="{{ old('gym_tagline', $settings['gym_tagline']) }}" class="gym-input">
                        </div>
                        <div>
                            <label class="gym-label">Business Email</label>
                            <input type="email" name="gym_email" value="{{ old('gym_email', $settings['gym_email']) }}" class="gym-input">
                        </div>
                        <div>
                            <label class="gym-label">Business Phone</label>
                            <input type="text" name="gym_phone" value="{{ old('gym_phone', $settings['gym_phone']) }}" class="gym-input">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="gym-label">Address</label>
                            <input type="text" name="gym_address" value="{{ old('gym_address', $settings['gym_address']) }}" class="gym-input">
                        </div>
                        <div>
                            <label class="gym-label">Currency Symbol *</label>
                            <input type="text" name="currency_symbol" value="{{ old('currency_symbol', $settings['currency_symbol']) }}" required class="gym-input">
                        </div>
                        <div>
                            <label class="gym-label">Currency Code *</label>
                            <input type="text" name="currency_code" value="{{ old('currency_code', $settings['currency_code']) }}" required class="gym-input">
                        </div>
                        <div>
                            <label class="gym-label">Membership Overlap Policy *</label>
                            <select name="membership_overlap_policy" class="gym-input">
                                <option value="prevent_overlap" {{ $settings['membership_overlap_policy'] === 'prevent_overlap' ? 'selected' : '' }}>Prevent Overlapping Plans</option>
                                <option value="queue_after_active" {{ $settings['membership_overlap_policy'] === 'queue_after_active' ? 'selected' : '' }}>Queue After Active Ends</option>
                                <option value="allow_multiple" {{ $settings['membership_overlap_policy'] === 'allow_multiple' ? 'selected' : '' }}>Allow Multiple Active</option>
                            </select>
                        </div>
                        <div>
                            <label class="gym-label">Expiring Alert Window (Days) *</label>
                            <input type="number" min="1" max="60" name="expiring_notice_days" value="{{ old('expiring_notice_days', $settings['expiring_notice_days']) }}" required class="gym-input">
                        </div>
                    </div>

                    @php
                        $activeMethods = is_string($settings['payment_methods']) ? (json_decode($settings['payment_methods'], true) ?? []) : (array) $settings['payment_methods'];
                    @endphp
                    <div>
                        <label class="gym-label">Enabled Payment Methods</label>
                        <div class="flex flex-wrap gap-3 pt-1">
                            @foreach(['cash' => 'Cash', 'bank_transfer' => 'Bank Transfer', 'card' => 'Card / POS', 'mobile_money' => 'Mobile Money', 'online' => 'Online'] as $mKey => $mLabel)
                                <label class="inline-flex items-center gap-2 text-sm">
                                    <input type="checkbox" name="payment_methods[]" value="{{ $mKey }}" {{ in_array($mKey, $activeMethods, true) ? 'checked' : '' }} class="w-4 h-4 rounded">
                                    <span>{{ $mLabel }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <label class="gym-label">PDF Receipt Footer</label>
                        <input type="text" name="receipt_footer" value="{{ old('receipt_footer', $settings['receipt_footer']) }}" class="gym-input">
                    </div>

                    <button type="submit" class="btn-lime">Save Gym Settings</button>
                </form>
            </div>

            <!-- Staff & Admin User Management -->
            <div class="lg:col-span-12 gym-card overflow-hidden">
                <div class="px-6 py-4 border-b border-[#E8EAE3] flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <h3 class="font-display font-bold text-base text-[#202520]">Staff &amp; Admin Accounts</h3>
                        <p class="text-xs text-[#858B84]">Role permissions are enforced on the backend for all routes and actions.</p>
                    </div>
                </div>

                <div class="p-6 border-b border-[#E8EAE3] bg-[#F5F5EF]/50">
                    <form action="{{ route('settings.users.store') }}" method="POST" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3 items-end">
                        @csrf
                        <div>
                            <label class="gym-label text-xs">Name *</label>
                            <input type="text" name="name" required class="gym-input" placeholder="Staff name">
                        </div>
                        <div>
                            <label class="gym-label text-xs">Email *</label>
                            <input type="email" name="email" required class="gym-input" placeholder="staff@gym.com">
                        </div>
                        <div>
                            <label class="gym-label text-xs">Role *</label>
                            <select name="role" class="gym-input">
                                <option value="staff">Staff</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div>
                            <label class="gym-label text-xs">Password *</label>
                            <input type="password" name="password" required class="gym-input" placeholder="Min 8 chars">
                        </div>
                        <div>
                            <label class="gym-label text-xs">Confirm *</label>
                            <input type="password" name="password_confirmation" required class="gym-input" placeholder="Repeat">
                        </div>
                        <button type="submit" class="btn-dark">Add User</button>
                    </form>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-[#F5F5EF] text-xs uppercase text-[#858B84]">
                            <tr>
                                <th class="text-left px-6 py-3 font-semibold">User</th>
                                <th class="text-left px-4 py-3 font-semibold">Role</th>
                                <th class="text-left px-4 py-3 font-semibold">Status</th>
                                <th class="text-left px-4 py-3 font-semibold">Last Login</th>
                                <th class="text-right px-6 py-3 font-semibold">Manage</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E8EAE3]">
                            @foreach($users as $u)
                                <tr>
                                    <td class="px-6 py-3">
                                        <div class="font-semibold text-[#202520]">{{ $u->name }}</div>
                                        <div class="text-xs text-[#858B84]">{{ $u->email }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="badge-pill {{ $u->isAdmin() ? 'badge-lime' : 'badge-neutral' }}">{{ ucfirst($u->role) }}</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="badge-pill {{ $u->is_active ? 'badge-success' : 'badge-danger' }}">{{ $u->is_active ? 'Active' : 'Disabled' }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-[#858B84]">
                                        {{ $u->last_login_at ? $u->last_login_at->diffForHumans() : 'Never' }}
                                    </td>
                                    <td class="px-6 py-3 text-right">
                                        @if($u->id !== Auth::id())
                                            <div class="inline-flex items-center gap-2">
                                                <form action="{{ route('settings.users.role', $u) }}" method="POST" class="inline-flex items-center gap-1">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="role" value="{{ $u->isAdmin() ? 'staff' : 'admin' }}">
                                                    <button type="submit" class="btn-outline py-1 px-2.5 text-xs">
                                                        Make {{ $u->isAdmin() ? 'Staff' : 'Admin' }}
                                                    </button>
                                                </form>
                                                <form action="{{ route('settings.users.toggle', $u) }}" method="POST">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="btn-outline py-1 px-2.5 text-xs">
                                                        {{ $u->is_active ? 'Disable' : 'Enable' }}
                                                    </button>
                                                </form>
                                            </div>
                                        @else
                                            <span class="text-xs text-[#858B84]">Current Session</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Recent System Audit Logs -->
            <div class="lg:col-span-12 gym-card overflow-hidden">
                <div class="px-6 py-4 border-b border-[#E8EAE3]">
                    <h3 class="font-display font-bold text-base text-[#202520]">Recent Audit Activity</h3>
                </div>
                <div class="divide-y divide-[#E8EAE3] max-h-80 overflow-y-auto">
                    @forelse($activityLogs as $log)
                        <div class="px-6 py-3 flex items-center justify-between gap-4 text-xs">
                            <div>
                                <span class="font-mono font-semibold text-[#202520] mr-2">{{ $log->action }}</span>
                                <span class="text-[#565C55]">{{ $log->description }}</span>
                            </div>
                            <div class="text-[#858B84] shrink-0">
                                {{ $log->user?->name ?? 'System' }} &bull; {{ $log->created_at->diffForHumans() }}
                            </div>
                        </div>
                    @empty
                        <div class="px-6 py-8 text-center text-xs text-[#858B84]">No activity recorded yet.</div>
                    @endforelse
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
