@extends('layouts.app')

@section('title', 'Edit Payment ' . ($payment->payment_reference ?? '#' . $payment->id))
@section('page-title', 'Edit Payment Details')
@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-[#202520]">Dashboard</a>
    <span>/</span>
    <a href="{{ route('payments.index') }}" class="hover:text-[#202520]">Payments</a>
    <span>/</span>
    <a href="{{ route('payments.show', $payment) }}" class="hover:text-[#202520]">{{ $payment->payment_reference ?? '#' . $payment->id }}</a>
    <span>/</span>
    <span class="text-[#202520] font-medium">Edit</span>
@endsection

@section('content')
@php $curr = $currencySymbol ?? '$'; @endphp

<div class="max-w-2xl mx-auto space-y-5">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="font-display font-bold text-2xl text-[#202520]">Edit Permitted Details</h2>
            <p class="text-sm text-[#858B84]">Historical financial amounts are locked to preserve accounting integrity. Changes are logged.</p>
        </div>
        <a href="{{ route('payments.show', $payment) }}" class="btn-outline">
            <x-icon name="arrow-left" class="w-4 h-4" /> Back
        </a>
    </div>

    <div class="gym-card p-5 bg-[#F5F5EF]/70 grid grid-cols-3 gap-4 text-sm">
        <div>
            <div class="text-xs text-[#858B84]">Member</div>
            <div class="font-semibold text-[#202520]">{{ $payment->member?->name }}</div>
        </div>
        <div>
            <div class="text-xs text-[#858B84]">Amount Paid</div>
            <div class="font-semibold text-[#202520] tabular-nums">{{ $curr }}{{ number_format((float) $payment->amount_paid, 2) }}</div>
        </div>
        <div>
            <div class="text-xs text-[#858B84]">Status</div>
            <div class="font-semibold text-[#202520]">{{ ucfirst(str_replace('_', ' ', $payment->status)) }}</div>
        </div>
    </div>

    <form method="POST" action="{{ route('payments.update', $payment) }}" class="gym-card p-6 sm:p-8 space-y-5">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="payment_date" class="gym-label">Payment Date *</label>
                <input id="payment_date" type="date" name="payment_date" value="{{ old('payment_date', $payment->payment_date->format('Y-m-d')) }}" required class="gym-input">
            </div>

            <div>
                <label for="payment_method" class="gym-label">Payment Method *</label>
                <select id="payment_method" name="payment_method" required class="gym-input">
                    @foreach($paymentMethods as $method)
                        <option value="{{ $method }}" {{ old('payment_method', $payment->payment_method) === $method ? 'selected' : '' }}>
                            {{ ucfirst(str_replace('_', ' ', $method)) }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div>
            <label for="transaction_id" class="gym-label">Transaction / Reference ID</label>
            <input id="transaction_id" type="text" name="transaction_id" value="{{ old('transaction_id', $payment->transaction_id) }}" class="gym-input">
        </div>

        <div>
            <label for="notes" class="gym-label">Notes</label>
            <textarea id="notes" name="notes" rows="3" class="gym-input">{{ old('notes', $payment->notes) }}</textarea>
        </div>

        <div class="flex items-center justify-end gap-3 pt-4 border-t border-[#E8EAE3]">
            <a href="{{ route('payments.show', $payment) }}" class="btn-outline">Cancel</a>
            <button type="submit" class="btn-lime">
                <x-icon name="check" class="w-4 h-4" />
                <span class="btn-label">Update Details</span>
            </button>
        </div>
    </form>
</div>
@endsection
