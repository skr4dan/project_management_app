<?php

namespace App\Http\Requests\Api;

use App\Enums\Project\ProjectStatus;
use GuzzleHttp\Handler\Proxy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProjectRequest extends FormRequest
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
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'min:3', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'status' => ['sometimes', 'string', Rule::in(array_column(ProjectStatus::cases(), 'value'))],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.string' => 'Project name must be a string',
            'name.min' => 'Project name must be at least 3 characters long',
            'name.max' => 'Project name cannot exceed 255 characters',
            'description.string' => 'Project description must be a string',
            'description.max' => 'Project description cannot exceed 1000 characters',
            'status.in' => 'Project status must be one of: ' . implode(', ', array_column(ProjectStatus::cases(), 'value')),
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'project name',
            'description' => 'project description',
            'status' => 'project status',
        ];
    }
}
