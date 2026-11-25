@extends('layouts.app')

@section('title', 'Add New Member')
@section('page-title', 'Register Member')
@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-[#202520]">Dashboard</a>
    <span>/</span>
    <a href="{{ route('members.index') }}" class="hover:text-[#202520]">Members</a>
    <span>/</span>
    <span class="text-[#202520] font-medium">New</span>
@endsection

@section('content')
<div class="max-w-4xl mx-auto space-y-5">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="font-display font-bold text-2xl text-[#202520]">Register New Member</h2>
            <p class="text-sm text-[#858B84]">A unique Gym Member ID will be generated automatically.</p>
        </div>
        <a href="{{ route('members.index') }}" class="btn-outline">
            <x-icon name="arrow-left" class="w-4 h-4" /> Back
        </a>
    </div>

    <form method="POST" action="{{ route('members.store') }}" enctype="multipart/form-data" class="gym-card p-6 sm:p-8 space-y-6">
        @csrf

        <!-- Photo & Basic Identity -->
        <div class="flex flex-col sm:flex-row sm:items-center gap-5 pb-6 border-b border-[#E8EAE3]">
            <div id="photo-preview-box" class="w-20 h-20 rounded-2xl bg-[#171A18] text-[#C5F467] flex items-center justify-center overflow-hidden shrink-0">
                <x-icon name="users" class="w-8 h-8" />
            </div>
            <div class="flex-1">
                <label for="photo" class="gym-label">Profile Photo (Optional)</label>
                <input id="photo" type="file" name="photo" accept="image/jpeg,image/png,image/webp" class="gym-input py-2 text-xs">
                <p class="text-xs text-[#858B84] mt-1">JPG, PNG, or WebP up to 2MB.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="sm:col-span-2">
                <label for="name" class="gym-label">Full Name *</label>
                <input id="name" type="text" name="name" value="{{ old('name') }}" required class="gym-input" placeholder="e.g. Jordan Taylor">
            </div>

            <div>
                <label for="email" class="gym-label">Email Address *</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required class="gym-input" placeholder="jordan@example.com">
            </div>

            <div>
                <label for="phone" class="gym-label">Phone Number *</label>
                <input id="phone" type="text" name="phone" value="{{ old('phone') }}" required class="gym-input" placeholder="+1 (555) 000-0000">
            </div>

            <div>
                <label for="date_of_birth" class="gym-label">Date of Birth *</label>
                <input id="date_of_birth" type="date" name="date_of_birth" value="{{ old('date_of_birth') }}" required class="gym-input">
            </div>

            <div>
                <label for="gender" class="gym-label">Gender *</label>
                <select id="gender" name="gender" required class="gym-input">
                    <option value="">Select gender...</option>
                    <option value="male" {{ old('gender') === 'male' ? 'selected' : '' }}>Male</option>
                    <option value="female" {{ old('gender') === 'female' ? 'selected' : '' }}>Female</option>
                    <option value="other" {{ old('gender') === 'other' ? 'selected' : '' }}>Other</option>
                </select>
            </div>

            <div>
                <label for="joining_date" class="gym-label">Registration Date *</label>
                <input id="joining_date" type="date" name="joining_date" value="{{ old('joining_date', now()->toDateString()) }}" required class="gym-input">
            </div>

            <div>
                <label for="status" class="gym-label">Initial Status</label>
                <select id="status" name="status" class="gym-input">
                    <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>

            <div class="sm:col-span-2">
                <label for="address" class="gym-label">Street Address</label>
                <textarea id="address" name="address" rows="2" class="gym-input" placeholder="Full residential address">{{ old('address') }}</textarea>
            </div>
        </div>

        <!-- Emergency Contact -->
        <div class="pt-4 border-t border-[#E8EAE3]">
            <h3 class="font-display font-bold text-sm text-[#202520] mb-3">Emergency Contact</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="emergency_contact_name" class="gym-label">Contact Name</label>
                    <input id="emergency_contact_name" type="text" name="emergency_contact_name" value="{{ old('emergency_contact_name') }}" class="gym-input" placeholder="Full name">
                </div>
                <div>
                    <label for="emergency_contact_phone" class="gym-label">Contact Phone</label>
                    <input id="emergency_contact_phone" type="text" name="emergency_contact_phone" value="{{ old('emergency_contact_phone') }}" class="gym-input" placeholder="+1 (555) 000-0000">
                </div>
            </div>
        </div>

        <div>
            <label for="notes" class="gym-label">Internal Notes</label>
            <textarea id="notes" name="notes" rows="2" class="gym-input" placeholder="Fitness goals, medical alerts, or front-desk notes...">{{ old('notes') }}</textarea>
        </div>

        <div class="flex items-center justify-end gap-3 pt-4 border-t border-[#E8EAE3]">
            <a href="{{ route('members.index') }}" class="btn-outline">Cancel</a>
            <button type="submit" class="btn-lime">
                <x-icon name="check" class="w-4 h-4" />
                <span class="btn-label">Save Member</span>
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('photo')?.addEventListener('change', (e) => {
    const file = e.target.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = (ev) => {
        document.getElementById('photo-preview-box').innerHTML = `<img src="${ev.target.result}" class="w-full h-full object-cover" alt="Preview">`;
    };
    reader.readAsDataURL(file);
});
</script>
@endpush
