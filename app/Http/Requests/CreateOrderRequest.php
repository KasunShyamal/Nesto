<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateOrderRequest extends FormRequest
{
    /* Determine if user is authorized to perform order capture */
    public function authorize(): bool
    {
        return true;
    }

    /* Sanitize and standardize NIC/Passport before validation */
    protected function prepareForValidation(): void
    {
        if ($this->has('nic_passport')) {
            $nic = strtoupper(str_replace([' ', '-'], '', $this->nic_passport));
            $this->merge(['nic_passport' => $nic]);
        }
    }

    /* Input validation rules */
    public function rules(): array
    {
        return [
            'nic_passport' => 'required|string|exists:customers,nic_passport',
            'invoice_number' => 'nullable|string|max:50',
            'branch_code' => 'required|string|exists:branches,code',
            'transaction_date' => 'required|date_format:Y-m-d|before_or_equal:today',
            'amount' => 'required|numeric|min:0',
        ];
    }
}
