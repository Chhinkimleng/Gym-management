@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Payment Details</h2>

    <div class="card p-4">
        <p><strong>Member:</strong> {{ $payment->member->name }}</p>
        <p><strong>Subscription:</strong> {{ $payment->subscription->name }}</p>
        <p><strong>Amount:</strong> ${{ $payment->amount }}</p>
        <p><strong>Payment Date:</strong> {{ $payment->payment_date }}</p>
        <p><strong>Method:</strong> {{ ucfirst($payment->payment_method) }}</p>
        <p><strong>Status:</strong> 
            <span class="badge bg-success">{{ ucfirst($payment->status) }}</span>
        </p>
        <p><strong>Notes:</strong> {{ $payment->notes }}</p>
    </div>

    <a href="{{ route('payments.receipt', $payment->id) }}" class="btn btn-primary mt-3">
        Download Receipt PDF
    </a>

    <a href="{{ route('payments.index') }}" class="btn btn-secondary mt-3">Back</a>
</div>
@endsection
