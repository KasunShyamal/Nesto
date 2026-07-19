<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ActivateAccountRequest extends FormRequest
{
    /* Determine if the user is authorized to make this request*/
    public function authorize(): bool
    {
        return true;
    }

    /* Sanitize and standardize NIC before validation */
    protected function prepareForValidation(): void
    {
        if ($this->has('nic_passport')) {
            $nic = strtoupper(str_replace([' ', '-'], '', $this->nic_passport));
            $this->merge(['nic_passport' => $nic]);
        }
    }

   /* Get the validation rules that apply to the request*/
    public function rules(): array
    {
        return [
            'nic_passport' => 'required|string|exists:customers,nic_passport',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ];
    }

    /* Custom messages for activation*/
    public function messages(): array
    {
        return [
            'nic_passport.exists' => 'The provided NIC/Passport does not match any registered customer profile.',
            'email.unique' => 'An account is already registered with this email address.',
        ];
    }
}
