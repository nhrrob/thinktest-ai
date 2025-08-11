<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PermissionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Check for appropriate granular permissions based on HTTP method
        if ($this->isMethod('POST')) {
            return $this->user()->hasPermissionTo('create permissions');
        } else {
            return $this->user()->hasPermissionTo('edit permissions');
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
            'group_name' => 'nullable|string|max:255',
        ];

        // Handle name validation differently for create vs update
        if ($this->isMethod('POST')) {
            // Creating a new permission
            $rules['name'] = 'required|string|max:255|unique:permissions,name';
        } else {
            // Updating an existing permission
            $rules['name'] = [
                'required',
                'string',
                'max:255',
                Rule::unique('permissions')->ignore($this->route('permission'))
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
            'name.required' => 'The permission name is required.',
            'name.unique' => 'A permission with this name already exists.',
            'group_name.max' => 'The group name may not be greater than 255 characters.',
        ];
    }
}
