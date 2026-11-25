@extends('layouts.app')

@section('title', 'Set New Password')

@section('content')
<div class="min-h-screen bg-[#F5F5EF] flex items-center justify-center p-4">
    <div class="w-full max-w-md gym-card p-8">
        <h2 class="font-display font-bold text-xl text-[#202520] mb-4">Set New Password</h2>
        <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <div>
                <label for="email" class="gym-label">Email Address</label>
                <input id="email" type="email" name="email" value="{{ $email ?? old('email') }}" required class="gym-input">
            </div>
            <div>
                <label for="password" class="gym-label">New Password</label>
                <input id="password" type="password" name="password" required class="gym-input">
            </div>
            <div>
                <label for="password-confirm" class="gym-label">Confirm Password</label>
                <input id="password-confirm" type="password" name="password_confirmation" required class="gym-input">
            </div>
            <button type="submit" class="btn-lime w-full">Reset Password</button>
        </form>
    </div>
</div>
@endsection
