@extends('layouts.app')

@section('title', __('Sign In'))

@section('content')
<div class="min-h-screen bg-[#F5F5EF] flex items-center justify-center p-4 sm:p-6 lg:p-10">
    <div class="w-full max-w-5xl grid grid-cols-1 lg:grid-cols-12 bg-white rounded-3xl border border-[#E8EAE3] shadow-xl overflow-hidden">
        <!-- Editorial Left Brand Panel -->
        <div class="lg:col-span-5 bg-[#171A18] text-white p-8 sm:p-10 flex flex-col justify-between relative overflow-hidden">
            <div>
                <div class="inline-flex items-center gap-2.5 px-3 py-1.5 rounded-full bg-white/10 border border-white/10 text-xs font-semibold text-[#C5F467] mb-8">
                    <x-icon name="dumbbell" class="w-4 h-4" />
                    <span>PulseCore Fitness OS</span>
                </div>

                <h1 class="font-display font-extrabold text-3xl sm:text-4xl leading-tight tracking-tight text-white">
                    Run your gym with <span class="text-[#C5F467]">precision</span> &amp; energy.
                </h1>
                <p class="mt-4 text-sm text-[#A2A8A0] leading-relaxed">
                    Unified member profiles, automated membership tracking, partial &amp; full payment ledgers, live check-ins, and real-time analytics.
                </p>
            </div>

            <div class="mt-10 pt-6 border-t border-white/10 grid grid-cols-2 gap-4">
                <div class="p-3.5 rounded-2xl bg-white/5 border border-white/10">
                    <div class="text-xs text-[#858B84]">Live Front Desk</div>
                    <div class="font-display font-bold text-lg text-[#C5F467] mt-0.5">Check-In Ready</div>
                </div>
                <div class="p-3.5 rounded-2xl bg-white/5 border border-white/10">
                    <div class="text-xs text-[#858B84]">Financial Ledger</div>
                    <div class="font-display font-bold text-lg text-white mt-0.5">PDF Receipts</div>
                </div>
            </div>
        </div>

        <!-- Login Form Panel -->
        <div class="lg:col-span-7 p-8 sm:p-12 flex flex-col justify-center">
            <div class="max-w-md w-full mx-auto">
                <h2 class="font-display font-bold text-2xl text-[#202520]">{{ __('Welcome back') }}</h2>
                <p class="text-sm text-[#858B84] mt-1">{{ __('Sign in to manage members, memberships, payments, and attendance.') }}</p>

                @if($errors->any())
                    <div class="mt-5 px-4 py-3 rounded-xl bg-[#E76B62]/12 border border-[#E76B62]/30 text-xs text-[#B83A31] font-medium">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4">
                    @csrf

                    <div>
                        <label for="email" class="gym-label">{{ __('Email Address') }}</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email"
                               placeholder="admin@example.com" class="gym-input">
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="password" class="gym-label mb-0">{{ __('Password') }}</label>
                            @if (Route::has('password.request'))
                                <a href="{{ route('password.request') }}" class="text-xs font-semibold text-[#202520] hover:text-[#FF795B]">
                                    {{ __('Forgot password?') }}
                                </a>
                            @endif
                        </div>
                        <input id="password" type="password" name="password" required autocomplete="current-password"
                               placeholder="••••••••" class="gym-input">
                    </div>

                    <label class="flex items-center gap-2.5 text-sm text-[#565C55] cursor-pointer">
                        <input type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}
                               class="w-4 h-4 rounded border-[#E8EAE3] text-[#171A18] focus:ring-[#C5F467]">
                        <span>{{ __('Keep me signed in') }}</span>
                    </label>

                    <button type="submit" class="btn-lime w-full py-3 text-sm mt-2">
                        <x-icon name="log-in" class="w-4 h-4" />
                        <span class="btn-label">{{ __('Sign In to Dashboard') }}</span>
                    </button>
                </form>

                <div class="mt-6 p-4 rounded-2xl bg-[#F5F5EF] border border-[#E8EAE3] space-y-3">
                    <div>
                        <div class="text-xs font-bold text-[#202520]">{{ __('Quick Demo Preview (For HR & Reviewers)') }}</div>
                        <p class="text-[11px] text-[#858B84] mt-0.5">{{ __('Explore the live system instantly with role-based permissions.') }}</p>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <form method="POST" action="{{ route('demo.login') }}">
                            @csrf
                            <input type="hidden" name="role" value="admin">
                            <button type="submit" class="btn-dark w-full py-2.5 text-xs">
                                <x-icon name="shield" class="w-4 h-4 text-[#C5F467]" />
                                <span class="btn-label">{{ __('View as Admin') }}</span>
                            </button>
                        </form>
                        <form method="POST" action="{{ route('demo.login') }}">
                            @csrf
                            <input type="hidden" name="role" value="staff">
                            <button type="submit" class="btn-outline w-full py-2.5 text-xs bg-white">
                                <x-icon name="users" class="w-4 h-4 text-[#202520]" />
                                <span class="btn-label">{{ __('View as Staff') }}</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
