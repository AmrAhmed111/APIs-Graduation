<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AuthRequest extends FormRequest
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
            'firstName' => 'required|string|min:3|max:50|regex:/^[a-zA-Z\s]+$/', // Contains letters only
            'lastName' => 'required|string|min:3|max:50|regex:/^[a-zA-Z\s]+$/', // Contains letters only
            'username' => 'required|string|min:5|max:30|unique:doctors|regex:/^[a-zA-Z0-9_]+$/', // Contains letters and numbers only, no spaces
            'email' => 'required|email|max:100|unique:doctors',
            'password' => [
                'required',
                'string',
                'min:8',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]+$/',
            ],
            'gender' => 'required|in:male,female',
            'phoneNumber' => 'required|string|regex:/^\+?[0-9]{7,11}$/',
            'rating' => 'required|numeric|min:0|max:5',
            'image' => [
                'required',
                'file',
                'mimes:png,jpg,jpeg,gif',
                'max:2048', // Maximum 2MB (2048KB)
            ],
            'spec_id' => 'required|exists:specializations,id',
            'user_id' => 'required|exists:users,id',
            'city_id' => 'required|exists:cities,id',
            'experience' => 'nullable|integer|min:0',
            'number_of_patients' => 'nullable|integer|min:0',
            'about' => 'required|string|max:500',
            'schedule' => 'required|json',
            'salary' => 'required|numeric|min:0',
        ];
    }

    public function messages()
    {
        return [
            'password.regex' => 'The password must contain an uppercase letter, a lowercase letter, a number, and a special character.',
            'phoneNumber.regex' => 'Invalid phone number. Please enter a valid phone number (7 to 11 digits).',
            'rating.required' => 'The rating field is required.',
            'rating.numeric' => 'The rating must be a number.',
            'rating.min' => 'The rating must be at least 0.',
            'rating.max' => 'The rating cannot be more than 5.',
            'image.required' => 'The profile image is required.',
            'image.file' => 'The profile image must be a file.',
            'image.mimes' => 'The profile image must be a file of type: png, jpg, jpeg, gif.',
            'image.max' => 'The profile image must not exceed 2MB.',
            'experience.integer' => 'Experience must be a valid number.',
            'schedule.json' => 'Schedule must be a valid JSON string.',
        ];
    }
}
