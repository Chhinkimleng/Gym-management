@extends('layouts.app')

@section('title', 'Edit ' . $plan->name)
@section('page-title', 'Edit Membership Plan')
@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-[#202520]">Dashboard</a>
    <span>/</span>
    <a href="{{ route('membership-plans.index') }}" class="hover:text-[#202520]">Membership Plans</a>
    <span>/</span>
    <span class="text-[#202520] font-medium">{{ $plan->name }}</span>
@endsection

@section('content')
<div class="max-w-3xl mx-auto space-y-5">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="font-display font-bold text-2xl text-[#202520]">Edit Plan: {{ $plan->name }}</h2>
            <p class="text-sm text-[#858B84]">Price &amp; duration changes apply only to future memberships; historical records are preserved.</p>
        </div>
        <a href="{{ route('membership-plans.index') }}" class="btn-outline">
            <x-icon name="arrow-left" class="w-4 h-4" /> Back
        </a>
    </div>

    <form action="{{ route('membership-plans.update', $plan) }}" method="POST" class="gym-card p-6 sm:p-8 space-y-5">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="sm:col-span-2">
                <label for="name" class="gym-label">Plan Name *</label>
                <input id="name" type="text" name="name" value="{{ old('name', $plan->name) }}" required class="gym-input">
            </div>

            <div>
                <label for="billing_cycle" class="gym-label">Billing Cycle *</label>
                <select id="billing_cycle" name="billing_cycle" required class="gym-input">
                    @foreach(\App\Models\MembershipPlan::billingCycles() as $key => $label)
                        <option value="{{ $key }}" {{ old('billing_cycle', $plan->billing_cycle) === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="price" class="gym-label">Price ({{ $currencySymbol ?? '$' }}) *</label>
                <input id="price" type="number" step="0.01" min="0" name="price" value="{{ old('price', $plan->price) }}" required class="gym-input">
            </div>

            <div id="months-field">
                <label for="duration_months" class="gym-label">Duration (Months) *</label>
                <input id="duration_months" type="number" min="1" max="120" name="duration_months" value="{{ old('duration_months', $plan->duration_months ?? 1) }}" class="gym-input">
            </div>

            <div id="days-field" class="hidden">
                <label for="duration_days" class="gym-label">Custom Duration (Days) *</label>
                <input id="duration_days" type="number" min="1" max="3650" name="duration_days" value="{{ old('duration_days', $plan->duration_days ?? 14) }}" class="gym-input">
            </div>
        </div>

        <div>
            <label for="description" class="gym-label">Description</label>
            <textarea id="description" name="description" rows="2" class="gym-input">{{ old('description', $plan->description) }}</textarea>
        </div>

        @php
            $benefitsText = old('benefits_text', is_array($plan->benefits) ? implode("\n", $plan->benefits) : '');
        @endphp
        <div>
            <label for="benefits_text" class="gym-label">Plan Benefits (one per line)</label>
            <textarea id="benefits_text" name="benefits_text" rows="4" class="gym-input">{{ $benefitsText }}</textarea>
        </div>

        <label class="flex items-center gap-2.5 text-sm font-medium text-[#202520] cursor-pointer">
            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $plan->is_active) ? 'checked' : '' }} class="w-4 h-4 rounded border-[#E8EAE3]">
            <span>Active</span>
        </label>

        <div class="flex items-center justify-end gap-3 pt-4 border-t border-[#E8EAE3]">
            <a href="{{ route('membership-plans.index') }}" class="btn-outline">Cancel</a>
            <button type="submit" class="btn-lime">
                <x-icon name="check" class="w-4 h-4" />
                <span class="btn-label">Update Plan</span>
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
const cycleSelect = document.getElementById('billing_cycle');
const monthsField = document.getElementById('months-field');
const daysField = document.getElementById('days-field');

function syncCycleFields() {
    if (cycleSelect.value === 'custom') {
        monthsField.classList.add('hidden');
        daysField.classList.remove('hidden');
    } else {
        monthsField.classList.remove('hidden');
        daysField.classList.add('hidden');
    }
}
cycleSelect.addEventListener('change', syncCycleFields);
syncCycleFields();
</script>
@endpush
