@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2>Add Subscription</h2>
            <p class="text-muted">Member: <strong>{{ $member->name }}</strong> (ID: {{ $member->membership_id }})</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('members.show', $member) }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Profile
            </a>
        </div>
    </div>

    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-plus"></i> New Subscription</h5>
        </div>

        <div class="card-body">

            <form action="{{ route('subscriptions.store') }}" method="POST">
                @csrf

                <!-- Hidden member ID -->
                <input type="hidden" name="member_id" value="{{ $member->id }}">

                <div class="mb-3">
                    <label for="membership_plan_id" class="form-label">Select Plan</label>
                    <select name="membership_plan_id" id="membership_plan_id" class="form-select" required>
                        <option value="">-- Choose a Plan --</option>
                        @foreach($plans as $plan)
                            <option value="{{ $plan->id }}">
                                {{ $plan->name }} — ${{ number_format($plan->price, 2) }} ({{ $plan->duration_months }} months)
                            </option>
                        @endforeach
                    </select>
                    @error('membership_plan_id')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input type="date" name="start_date" id="start_date" class="form-control" required>
                        @error('start_date')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select name="status" id="status" class="form-select">
                            <option value="active">Active</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                        @error('status')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                <button type="submit" class="btn btn-success w-100">
                    <i class="fas fa-save"></i> Save Subscription
                </button>

            </form>

        </div>
    </div>

</div>
@endsection
