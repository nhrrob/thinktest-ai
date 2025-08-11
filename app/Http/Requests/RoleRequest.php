<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Check for appropriate granular permissions based on HTTP method
        if ($this->isMethod('POST')) {
            return $this->user()->hasPermissionTo('create roles');
        } else {
            return $this->user()->hasPermissionTo('edit roles');
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
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id',
        ];

        // Handle name validation differently for create vs update
        if ($this->isMethod('POST')) {
            // Creating a new role
            $rules['name'] = 'required|string|max:255|unique:roles,name';
        } else {
            // Updating an existing role
            $rules['name'] = [
                'required',
                'string',
                'max:255',
                Rule::unique('roles')->ignore($this->route('role'))
            ];
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The role name is required.',
            'name.unique' => 'A role with this name already exists.',
            'permissions.*.exists' => 'One or more selected permissions are invalid.',
        ];
    }
}
