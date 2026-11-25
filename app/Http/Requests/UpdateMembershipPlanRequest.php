<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMembershipPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'billing_cycle' => ['required', 'in:monthly,quarterly,semi_annual,annual,custom'],
            'price' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'duration_months' => ['nullable', 'integer', 'min:1', 'max:120', 'required_unless:billing_cycle,custom'],
            'duration_days' => ['nullable', 'integer', 'min:1', 'max:3650', 'required_if:billing_cycle,custom'],
            'benefits_text' => ['nullable', 'string', 'max:3000'],
            'is_active' => ['nullable'],
        ];
    }
}
