@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h2>Subscription Details</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('members.index') }}">Members</a></li>
                    <li class="breadcrumb-item">
                        <a href="{{ route('members.show', $subscription->member) }}">
                            {{ $subscription->member->name }}
                        </a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">Subscription</li>
                </ol>
            </nav>
        </div>
        <div class="col-md-6 text-end">
            {{-- <a href="{{ route('subscriptions.edit', $subscription) }}" class="btn btn-warning">
                <i class="fas fa-edit"></i> Edit
            </a> --}}
            @if($subscription->status == 'active')
                <form action="{{ route('subscriptions.cancel', $subscription) }}" method="POST" class="d-inline">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to cancel this subscription?')">
                        <i class="fas fa-times-circle"></i> Cancel
                    </button>
                </form>
            @endif
        </div>
    </div>

    <div class="row">
        <!-- Subscription Info Card -->
        <div class="col-md-6">
            <div class="card shadow mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-info-circle"></i> Subscription Info</h5>
                </div>
                <div class="card-body">
                    <p><strong>Member:</strong> 
                        <a href="{{ route('members.show', $subscription->member) }}">
                            {{ $subscription->member->name }}
                        </a>
                    </p>
                    <p><strong>Plan:</strong> {{ $subscription->membershipPlan->name }}</p>
                    <p><strong>Price:</strong> ${{ number_format($subscription->membershipPlan->price, 2) }}</p>
                    <p><strong>Duration:</strong> {{ $subscription->membershipPlan->duration_months }} month(s)</p>
                    <p><strong>Start Date:</strong> {{ $subscription->start_date->format('M d, Y') }}</p>
                    <p><strong>End Date:</strong> {{ $subscription->end_date->format('M d, Y') }}</p>
                    <p>
                        <strong>Status:</strong>
                        @if($subscription->status == 'active')
                            <span class="badge bg-success">Active</span>
                        @elseif($subscription->status == 'expired')
                            <span class="badge bg-secondary">Expired</span>
                        @else
                            <span class="badge bg-danger">Cancelled</span>
                        @endif
                    </p>
                    <p>
                        <strong>Days Remaining:</strong>
@if($subscription->status == 'active')
    @php
        $daysRemaining = (int) now()->diffInDays($subscription->end_date, false); // cast to integer
        $daysRemaining = max($daysRemaining, 0); // never negative
    @endphp
    {{ $daysRemaining }} days
@else
    N/A
@endif



                    </p>
                </div>
            </div>
        </div>

        <!-- Payment History -->
        <div class="col-md-6">
            <div class="card shadow mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-dollar-sign"></i> Payment History</h5>
                </div>
                <div class="card-body">
                    @if($subscription->payments->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Method</th>
                                        <th>Status</th>
                                        <th>Transaction ID</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($subscription->payments as $payment)
                                        <tr>
                                            <td>{{ $payment->payment_date->format('M d, Y') }}</td>
                                            <td>${{ number_format($payment->amount, 2) }}</td>
                                            <td>{{ ucfirst(str_replace('_',' ', $payment->payment_method)) }}</td>
                                            <td>
                                                @if($payment->status == 'paid')
                                                    <span class="badge bg-success">Completed</span>
                                                @elseif($payment->status == 'pending')
                                                    <span class="badge bg-warning">Pending</span>
                                                @else
                                                    <span class="badge bg-danger">Failed</span>
                                                @endif
                                            </td>
                                            <td>{{ $payment->transaction_id ?? 'N/A' }}</td>
                                        </tr>
                                    @endforeach
                                    <tr class="table-light">
                                        <th>Total Paid:</th>
                                        <th colspan="4">
                                            ${{ number_format($subscription->payments->where('status','paid')->sum('amount'), 2) }}
                                        </th>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted text-center mb-0">No payments made for this subscription.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
