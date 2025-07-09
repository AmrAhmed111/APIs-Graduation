<?php

namespace App\Http\Requests;

use App\Models\Patient;
use Illuminate\Foundation\Http\FormRequest;

class AuthPatientRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'fullName' => 'required|string|min:3|max:50|regex:/^[a-zA-Z\s]+$/',
            'email' => 'required|email||max:100|unique:patients',
            'password' => [
                'required',
                'string',
                'min:8',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]+$/',
            ],
            'DateofBirth' => 'required|date',
            'gender' => 'nullable|in:'.implode(',', Patient::$genders),
            'phoneNumber' => 'nullable|string||regex:/^\+?[0-9]{7,11}$/',
            'address' => 'nullable|string',
        ];
    }

    public function messages()
    {
        return [
            'password.regex' => 'The password must contain an uppercase letter, a lowercase letter, a number, and a special character.',
            'phoneNumber.regex' => 'Invalid phone number. Please enter a valid phone number (7 to 11 digits).',
        ];
    }
}
