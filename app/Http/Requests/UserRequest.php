<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Check for appropriate granular permissions based on HTTP method
        if ($this->isMethod('POST')) {
            return $this->user()->hasPermissionTo('create users');
        } else {
            return $this->user()->hasPermissionTo('edit users');
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'roles' => 'array',
            'roles.*' => 'exists:roles,id',
        ];

        // Handle email validation differently for create vs update
        if ($this->isMethod('POST')) {
            // Creating a new user
            $rules['email'] = 'required|string|email|max:255|unique:users,email';
            $rules['password'] = 'required|string|min:8|confirmed';
        } else {
            // Updating an existing user
            $rules['email'] = [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($this->route('user'))
            ];
            $rules['password'] = 'nullable|string|min:8|confirmed';
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The user name is required.',
            'email.required' => 'The email address is required.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'A user with this email address already exists.',
            'password.required' => 'The password is required.',
            'password.min' => 'The password must be at least 8 characters.',
            'password.confirmed' => 'The password confirmation does not match.',
            'roles.*.exists' => 'One or more selected roles are invalid.',
        ];
    }
}
