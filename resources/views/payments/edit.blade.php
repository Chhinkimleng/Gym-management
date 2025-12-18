@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <h2>Edit Payment</h2>
    <a href="{{ route('payments.index') }}" class="btn btn-secondary mb-3">Back</a>

    <div class="card shadow">
        <div class="card-body">
            <form method="POST" action="{{ route('payments.update', $payment) }}">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label class="form-label">Member</label>
                    <select name="member_id" class="form-select" required>
                        @foreach($members as $member)
                            <option value="{{ $member->id }}" {{ $payment->member_id == $member->id ? 'selected' : '' }}>
                                {{ $member->name }} ({{ $member->membership_id }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Subscription</label>
                    <select name="subscription_id" class="form-select" required>
                        @foreach($subscriptions as $sub)
                            <option value="{{ $sub->id }}" {{ $payment->subscription_id == $sub->id ? 'selected' : '' }}>
                                {{ $sub->membershipPlan->name }} - ${{ $sub->membershipPlan->price }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Amount ($)</label>
                    <input type="number" name="amount" class="form-control" step="0.01" value="{{ $payment->amount }}" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Payment Date</label>
                    <input type="date" name="payment_date" class="form-control" value="{{ $payment->payment_date->format('Y-m-d') }}" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Payment Method</label>
                    <select name="payment_method" class="form-select" required>
                        <option value="cash" {{ $payment->payment_method == 'cash' ? 'selected' : '' }}>Cash</option>
                        <option value="card" {{ $payment->payment_method == 'card' ? 'selected' : '' }}>Card</option>
                        <option value="bank_transfer" {{ $payment->payment_method == 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select" required>
                        <option value="paid" {{ $payment->status == 'paid' ? 'selected' : '' }}>Paid</option>
                        <option value="pending" {{ $payment->status == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="failed" {{ $payment->status == 'failed' ? 'selected' : '' }}>Failed</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="2">{{ $payment->notes }}</textarea>
                </div>

                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Payment</button>
            </form>
        </div>
    </div>
</div>
@endsection
