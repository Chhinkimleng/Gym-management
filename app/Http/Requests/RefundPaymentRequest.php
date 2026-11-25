<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RefundPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'refund_amount' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'refund_reason' => ['required', 'string', 'max:500'],
        ];
    }
}
