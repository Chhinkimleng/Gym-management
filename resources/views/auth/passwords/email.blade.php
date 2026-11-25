@extends('layouts.app')

@section('title', 'Reset Password')

@section('content')
<div class="min-h-screen bg-[#F5F5EF] flex items-center justify-center p-4">
    <div class="w-full max-w-md gym-card p-8">
        <div class="flex items-center gap-2.5 mb-6">
            <div class="w-9 h-9 rounded-xl bg-[#C5F467] text-[#171A18] flex items-center justify-center">
                <x-icon name="dumbbell" class="w-5 h-5" />
            </div>
            <span class="font-display font-bold text-lg text-[#202520]">Reset Password</span>
        </div>

        @if (session('status'))
            <div class="mb-4 px-4 py-3 rounded-xl bg-[#4CAF78]/15 text-[#267A4B] text-xs font-medium">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
            @csrf
            <div>
                <label for="email" class="gym-label">Email Address</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus class="gym-input">
                @error('email')
                    <p class="text-xs text-[#E76B62] mt-1">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit" class="btn-lime w-full">Send Password Reset Link</button>
            <a href="{{ route('login') }}" class="btn-outline w-full">Back to Sign In</a>
        </form>
    </div>
</div>
@endsection
