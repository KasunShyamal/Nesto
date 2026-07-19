<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterCustomerRequest extends FormRequest
{
    /* Determine if the user is authorized to make this request*/
    public function authorize(): bool
    {
        // Gated by role middleware on routes, but set to true here.
        return true;
    }

    /* Sanitize and standardize NIC and Mobile formats before validation */
    protected function prepareForValidation(): void
    {
        if ($this->has('nic_passport')) {
            $nic = strtoupper(str_replace([' ', '-'], '', $this->nic_passport));
            $this->merge(['nic_passport' => $nic]);
        }

        if ($this->has('mobile_number')) {
            $mobile = str_replace([' ', '-', '+'], '', $this->mobile_number);
            if (str_starts_with($mobile, '94')) {
                $mobile = '0' . substr($mobile, 2);
            }
            $this->merge(['mobile_number' => $mobile]);
        }
    }

    /* Get the validation rules that apply to the request*/
    public function rules(): array
    {
        return [
            'nic_passport' => [
                'required',
                'string',
                'unique:customers,nic_passport',
                'regex:/^([0-9]{9}[vVxX]|[0-9]{12}|[a-zA-Z][0-9]{7,8})$/',
            ],
            'mobile_number' => [
                'required',
                'string',
                'unique:customers,mobile_number',
                'regex:/^(?:0|94|\+94)?7(0|1|2|4|5|6|7|8)[0-9]{7}$/',
            ],
            'name' => 'required|string|min:2|max:255',
        ];
    }

    /* Custom validation error messages*/
    public function messages(): array
    {
        return [
            'nic_passport.regex' => 'The NIC/Passport number format is invalid (Use old NIC like 123456789V, new NIC 12 digits, or passport format).',
            'mobile_number.regex' => 'Please enter a valid Sri Lankan mobile number (e.g., 0771234567).',
        ];
    }
}
