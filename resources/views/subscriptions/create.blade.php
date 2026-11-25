@extends('layouts.app')

@section('title', 'Assign Membership')
@section('page-title', 'Assign Membership')
@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-[#202520]">Dashboard</a>
    <span>/</span>
    <a href="{{ route('memberships.index') }}" class="hover:text-[#202520]">Memberships</a>
    <span>/</span>
    <span class="text-[#202520] font-medium">Assign</span>
@endsection

@section('content')
@php $curr = $currencySymbol ?? '$'; @endphp

<div class="max-w-3xl mx-auto space-y-5">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="font-display font-bold text-2xl text-[#202520]">Assign Membership Plan</h2>
            <p class="text-sm text-[#858B84]">Expiration date and plan pricing snapshot are calculated automatically.</p>
        </div>
        <a href="{{ $member ? route('members.show', $member) : route('memberships.index') }}" class="btn-outline">
            <x-icon name="arrow-left" class="w-4 h-4" /> Back
        </a>
    </div>

    <form action="{{ route('subscriptions.store') }}" method="POST" class="gym-card p-6 sm:p-8 space-y-5">
        @csrf

        <div>
            <label for="member_id" class="gym-label">Member *</label>
            <select id="member_id" name="member_id" required class="gym-input">
                <option value="">-- Choose a Member --</option>
                @foreach($members as $m)
                    <option value="{{ $m->id }}" {{ old('member_id', $member?->id) == $m->id ? 'selected' : '' }}>
                        {{ $m->name }} ({{ $m->membership_id }}) — {{ ucfirst($m->status) }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="membership_plan_id" class="gym-label">Membership Plan *</label>
            <select id="membership_plan_id" name="membership_plan_id" required class="gym-input">
                <option value="">-- Choose an Active Plan --</option>
                @foreach($plans as $plan)
                    <option value="{{ $plan->id }}"
                            data-months="{{ $plan->duration_months ?? 0 }}"
                            data-days="{{ $plan->duration_days ?? 0 }}"
                            data-cycle="{{ $plan->billing_cycle }}"
                            data-price="{{ number_format((float) $plan->price, 2) }}"
                            {{ old('membership_plan_id') == $plan->id ? 'selected' : '' }}>
                        {{ $plan->name }} — {{ $curr }}{{ number_format((float) $plan->price, 2) }} ({{ $plan->formattedDuration() }})
                    </option>
                @endforeach
            </select>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="start_date" class="gym-label">Start Date *</label>
                <input id="start_date" type="date" name="start_date" value="{{ old('start_date', now()->toDateString()) }}" required class="gym-input">
            </div>

            <div>
                <label class="gym-label">Calculated Expiration Date</label>
                <input id="end_date_preview" type="text" readonly class="gym-input font-semibold" value="Select a plan...">
            </div>
        </div>

        <div>
            <label for="notes" class="gym-label">Notes (Optional)</label>
            <textarea id="notes" name="notes" rows="2" class="gym-input" placeholder="Contract notes or promo details...">{{ old('notes') }}</textarea>
        </div>

        <div class="flex items-center justify-end gap-3 pt-4 border-t border-[#E8EAE3]">
            <a href="{{ $member ? route('members.show', $member) : route('memberships.index') }}" class="btn-outline">Cancel</a>
            <button type="submit" class="btn-lime">
                <x-icon name="check" class="w-4 h-4" />
                <span class="btn-label">Assign Membership</span>
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
const planEl = document.getElementById('membership_plan_id');
const startEl = document.getElementById('start_date');
const previewEl = document.getElementById('end_date_preview');

function updateExpirationPreview() {
    const opt = planEl.selectedOptions[0];
    const startVal = startEl.value;
    if (!opt || !opt.value || !startVal) {
        previewEl.value = 'Select a plan & start date';
        return;
    }
    const d = new Date(startVal + 'T00:00:00');
    const cycle = opt.dataset.cycle;
    const days = parseInt(opt.dataset.days || '0', 10);
    const months = parseInt(opt.dataset.months || '1', 10);

    if (cycle === 'custom' && days > 0) {
        d.setDate(d.getDate() + days);
    } else {
        d.setMonth(d.getMonth() + months);
    }
    previewEl.value = d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
}

planEl.addEventListener('change', updateExpirationPreview);
startEl.addEventListener('change', updateExpirationPreview);
updateExpirationPreview();
</script>
@endpush
