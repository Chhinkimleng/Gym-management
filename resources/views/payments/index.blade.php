@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <h2>Payments</h2>
    <a href="{{ route('payments.create') }}" class="btn btn-primary mb-3">Add Payment</a>

    <div class="card shadow">
        <table class="table mb-0">
            <thead class="table-dark">
                <tr>
                    <th>Member</th>
                    <th>Subscription</th>
                    <th>Amount ($)</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th width="150">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($payments as $payment)
                <tr>
                    <td>{{ $payment->member->name }}</td>
                    <td>#{{ $payment->subscription_id }}</td>
                    <td>{{ number_format($payment->amount, 2) }}</td>
                    <td><span class="badge bg-success">{{ $payment->status }}</span></td>
                    <td>{{ $payment->payment_date->format('d M Y') }}</td>
                    <td>
                        <a href="{{ route('payments.show', $payment->id) }}" class="btn btn-info btn-sm">View</a>
                        <a href="{{ route('payments.edit', $payment->id) }}" class="btn btn-warning btn-sm">Edit</a>

                        <form method="POST" action="{{ route('payments.destroy', $payment->id) }}" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-danger btn-sm"
                                onclick="return confirm('Delete payment?')">Delete</button>
                        </form>

                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{ $payments->links() }}

</div>
@endsection
