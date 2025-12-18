@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h2>Member Profile</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('members.index') }}">Members</a></li>
                    <li class="breadcrumb-item active">{{ $member->name }}</li>
                </ol>
            </nav>
        </div>
        <div class="col-md-6 text-end">
            <a href="{{ route('members.edit', $member) }}" class="btn btn-warning">
                <i class="fas fa-edit"></i> Edit Member
            </a>
            <a href="{{ route('subscriptions.create', $member) }}" class="btn btn-success">
                <i class="fas fa-plus"></i> Add Subscription
            </a>
        </div>
            @if($member->activeSubscription)
                <a href="{{ route('members.payments.create', $member) }}" class="btn btn-success mb-3">
                    <i class="fas fa-plus"></i> Add Payment
                </a>
            @else
                <div class="alert alert-warning">
                    This member has no active subscription.
                </div>
             @endif

    </div>

    <div class="row">
        <!-- Member Information Card -->
        <div class="col-md-4">
            <div class="card shadow mb-4">
                <div class="card-body text-center">
                    @if($member->photo)
                        <img src="{{ asset('storage/' . $member->photo) }}" alt="{{ $member->name }}" 
                             class="rounded-circle mb-3" width="150" height="150" style="object-fit: cover;">
                    @else
                        <div class="rounded-circle bg-secondary d-inline-flex align-items-center justify-content-center text-white mb-3" 
                             style="width: 150px; height: 150px; font-size: 48px;">
                            {{ strtoupper(substr($member->name, 0, 1)) }}
                        </div>
                    @endif
                    
                    <h3>{{ $member->name }}</h3>
                    <p class="text-muted mb-2">{{ $member->membership_id }}</p>
                    
                    @if($member->status == 'active')
                        <span class="badge bg-success mb-3">Active</span>
                    @elseif($member->status == 'inactive')
                        <span class="badge bg-secondary mb-3">Inactive</span>
                    @else
                        <span class="badge bg-danger mb-3">Suspended</span>
                    @endif

                    <hr>

                    <div class="text-start">
                        <p class="mb-2">
                            <i class="fas fa-envelope text-primary"></i>
                            <strong>Email:</strong><br>
                            <a href="mailto:{{ $member->email }}">{{ $member->email }}</a>
                        </p>
                        <p class="mb-2">
                            <i class="fas fa-phone text-success"></i>
                            <strong>Phone:</strong><br>
                            <a href="tel:{{ $member->phone }}">{{ $member->phone }}</a>
                        </p>
                        <p class="mb-2">
                            <i class="fas fa-birthday-cake text-danger"></i>
                            <strong>Date of Birth:</strong><br>
                            {{ $member->date_of_birth->format('M d, Y') }}
                            ({{ $member->date_of_birth->age }} years)
                        </p>
                        <p class="mb-2">
                            <i class="fas fa-venus-mars text-info"></i>
                            <strong>Gender:</strong><br>
                            {{ ucfirst($member->gender) }}
                        </p>
                        <p class="mb-2">
                            <i class="fas fa-calendar-alt text-warning"></i>
                            <strong>Joined:</strong><br>
                            {{ $member->joining_date->format('M d, Y') }}
                        </p>
                        @if($member->address)
                        <p class="mb-0">
                            <i class="fas fa-map-marker-alt text-secondary"></i>
                            <strong>Address:</strong><br>
                            {{ $member->address }}
                        </p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Subscriptions and Activity -->
        <div class="col-md-8">
            <!-- Active Subscription -->
            @if($member->activeSubscription)
            <div class="card shadow mb-4 border-left-success">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-check-circle"></i> Active Subscription
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>{{ $member->activeSubscription->membershipPlan->name }}</h6>
                            <p class="mb-1"><strong>Price:</strong> ${{ number_format($member->activeSubscription->membershipPlan->price, 2) }}</p>
                            <p class="mb-1"><strong>Start Date:</strong> {{ $member->activeSubscription->start_date->format('M d, Y') }}</p>
                            <p class="mb-0"><strong>End Date:</strong> {{ $member->activeSubscription->end_date->format('M d, Y') }}</p>
                        </div>
                        <div class="col-md-6 text-end">
                            <div class="alert alert-info mb-0">
    <strong>Days Remaining:</strong><br>
    @php
        $daysRemaining = (int) now()->diffInDays($member->activeSubscription->end_date, false);
        $daysRemaining = max($daysRemaining, 0);
    @endphp
    <h3 class="mb-0">{{ $daysRemaining }}</h3>
</div>

                        </div>
                    </div>
                </div>
            </div>
            @else
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i> <strong>No Active Subscription</strong><br>
                This member doesn't have an active subscription plan.
                <a href="{{ route('subscriptions.create', $member) }}" class="btn btn-sm btn-warning mt-2">
                    Add Subscription
                </a>
            </div>
            @endif

            <!-- Subscription History -->
            <div class="card shadow mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-history"></i> Subscription History
                    </h5>
                </div>
                <div class="card-body">
                    @if($member->subscriptions->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Action</th>
                                    <th>Plan</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Status</th>
                                    <th>Duration</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($member->subscriptions as $subscription)
                                <tr>
                                    <td>
                                        <a href="{{ route('subscriptions.show', $subscription->id) }}" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                    </td>
                                    <td>{{ $subscription->membershipPlan->name }}</td>
                                    <td>{{ $subscription->start_date->format('M d, Y') }}</td>
                                    <td>{{ $subscription->end_date->format('M d, Y') }}</td>
                                    <td>
                                        @if($subscription->status == 'active' && $subscription->end_date >= now())
                                            <span class="badge bg-success">Active</span>
                                        @elseif($subscription->status == 'expired' || $subscription->end_date < now())
                                            <span class="badge bg-secondary">Expired</span>
                                        @else
                                            <span class="badge bg-danger">Cancelled</span>
                                        @endif
                                    </td>
                                    <td>{{ $subscription->membershipPlan->duration_months }} month(s)</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <p class="text-muted text-center mb-0">No subscription history</p>
                    @endif
                    {{-- <div class="col-md-6 text-end">
                    
                        <a href="{{ route('subscriptions.show', $member->activeSubscription->id) }}" class="btn btn-sm btn-info mt-2">
                            <i class="fas fa-eye"></i> View Details
                        </a>
                    </div> --}}
                </div>
                </div>

            

            <!-- Payment History -->
            <div class="card shadow mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-dollar-sign"></i> Payment History
                    </h5>
                </div>
                <div class="card-body">
                    @if($member->payments->count() > 0)
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
                                @foreach($member->payments as $payment)
                                <tr>
                                    <td>{{ $payment->payment_date->format('M d, Y') }}</td>
                                    <td>${{ number_format($payment->amount, 2) }}</td>
                                    <td>
                                        <span class="badge bg-info">{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</span>
                                    </td>
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
                                    <th colspan="1">Total Paid:</th>
                                    <th colspan="4">${{ number_format($member->payments->where('status','paid')->sum('amount'),2) }}</th>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    @else
                    <p class="text-muted text-center mb-0">No payment history</p>
                    @endif
                </div>
            </div>

            <!-- Attendance History -->
            <div class="card shadow mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-calendar-check"></i> Recent Attendance
                    </h5>
                </div>
                <div class="card-body">
                    @if($member->attendances->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Check-in</th>
                                    <th>Check-out</th>
                                    <th>Duration</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($member->attendances->take(10) as $attendance)
                                <tr>
                                    <td>{{ $attendance->check_in->format('M d, Y') }}</td>
                                    <td>{{ $attendance->check_in->format('h:i A') }}</td>
                                    <td>
                                        @if($attendance->check_out)
                                            {{ $attendance->check_out->format('h:i A') }}
                                        @else
                                            <span class="badge bg-success">Active</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($attendance->check_out)
                                            {{ $attendance->check_in->diff($attendance->check_out)->format('%H:%I') }} hrs
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <p class="text-muted small mb-0 mt-2">Total Visits: {{ $member->attendances->count() }}</p>
                    @else
                    <p class="text-muted text-center mb-0">No attendance records</p>
                    @endif
                    <a href="{{ route('members.attendance', $member) }}" class="btn btn-info btn-sm">
                        <i class="fas fa-calendar-check"></i> View Attendance
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.border-left-success {
    border-left: 4px solid #28a745;
}
</style>
@endsection