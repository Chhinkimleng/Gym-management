<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'member_id' => ['required', 'exists:members,id'],
            'subscription_id' => ['nullable', 'exists:subscriptions,id'],
            'total_amount' => ['nullable', 'numeric', 'min:0.01', 'max:999999.99'],
            'amount_paid' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'payment_date' => ['required', 'date'],
            'payment_method' => ['required', 'string', 'max:50'],
            'transaction_id' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'in:paid,partially_paid,pending'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
