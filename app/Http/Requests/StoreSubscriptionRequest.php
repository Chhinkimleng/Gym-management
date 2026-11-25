<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'member_id' => ['required', 'exists:members,id'],
            'membership_plan_id' => ['required', 'exists:membership_plans,id'],
            'start_date' => ['required', 'date'],
            'status' => ['nullable', 'in:active,pending,cancelled'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_renewal' => ['nullable', 'boolean'],
        ];
    }
}
