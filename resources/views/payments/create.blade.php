@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <h2>Add New Payment</h2>
    <a href="{{ route('payments.index') }}" class="btn btn-secondary mb-3">Back</a>

    <div class="card shadow">
        <div class="card-body">
            <form method="POST" action="{{ route('payments.store') }}">
                @csrf

                <!-- Member -->
                <div class="mb-3">
                    <label class="form-label">Member</label>
                    <select name="member_id" id="member_id" class="form-select" required>
                        @foreach(isset($member) ? [$member] : $members as $m)
                            <option value="{{ $m->id }}" 
                                {{ (old('member_id') ?? ($member->id ?? '')) == $m->id ? 'selected' : '' }}>
                                {{ $m->name }} ({{ $m->membership_id }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Subscription -->
                <div class="mb-3">
                    <label class="form-label">Subscription</label>
                    <select name="subscription_id" id="subscription_id" class="form-select" required>
                        @if(isset($subscriptions))
                            @foreach($subscriptions as $sub)
                                <option value="{{ $sub->id }}" 
                                    {{ old('subscription_id') == $sub->id ? 'selected' : '' }}
                                    data-price="{{ $sub->membershipPlan->price }}">
                                    {{ $sub->membershipPlan->name }} 
                                    ({{ $sub->start_date->format('M d, Y') }} - {{ $sub->end_date->format('M d, Y') }})
                                </option>
                            @endforeach
                        @endif
                    </select>
                </div>

                <!-- Amount (auto-calculated) -->
                <div class="mb-3">
                    <label class="form-label">Amount ($)</label>
                    <input type="number" step="0.01" name="amount" id="amount" class="form-control" 
                        value="{{ old('amount', isset($subscriptions) && $subscriptions->count() ? $subscriptions->first()->membershipPlan->price : '') }}" 
                        readonly required>
                </div>

                <!-- Payment Date -->
                <div class="mb-3">
                    <label class="form-label">Payment Date</label>
                    <input type="date" name="payment_date" class="form-control" value="{{ old('payment_date', now()->format('Y-m-d')) }}" required>
                </div>

                <!-- Payment Method -->
                <div class="mb-3">
                    <label class="form-label">Payment Method</label>
                    <select name="payment_method" class="form-select" required>
                        <option value="cash">Cash</option>
                        <option value="card">Card</option>
                        <option value="bank_transfer">Bank Transfer</option>
                    </select>
                </div>

                <!-- Status -->
                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select" required>
                        <option value="paid">Paid</option>
                        <option value="pending">Pending</option>
                        <option value="failed">Failed</option>
                    </select>
                </div>

                <!-- Notes -->
                <div class="mb-3">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control">{{ old('notes') }}</textarea>
                </div>

                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> Save Payment
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    const subscriptionSelect = document.getElementById('subscription_id');
    const amountInput = document.getElementById('amount');

    subscriptionSelect.addEventListener('change', function() {
        const selected = this.selectedOptions[0];
        amountInput.value = selected ? selected.dataset.price : '';
    });
</script>
@endsection
