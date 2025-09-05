<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class CreateTaskRequest extends FormRequest
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
            'title' => ['required', 'string', 'min:3', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'priority' => ['sometimes', 'string', 'in:low,medium,high'],
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'due_date' => ['nullable', 'date', 'after:today'],
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
            'title.required' => 'Task title is required',
            'title.string' => 'Task title must be a string',
            'title.min' => 'Task title must be at least 3 characters long',
            'title.max' => 'Task title cannot exceed 255 characters',
            'description.string' => 'Task description must be a string',
            'description.max' => 'Task description cannot exceed 1000 characters',
            'priority.in' => 'Task priority must be one of: low, medium, high',
            'project_id.required' => 'Project ID is required',
            'project_id.integer' => 'Project ID must be an integer',
            'project_id.exists' => 'Selected project does not exist',
            'assigned_to.integer' => 'Assigned user ID must be an integer',
            'assigned_to.exists' => 'Selected user does not exist',
            'due_date.date' => 'Due date must be a valid date',
            'due_date.after' => 'Due date must be after today',
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
            'title' => 'task title',
            'description' => 'task description',
            'priority' => 'task priority',
            'project_id' => 'project ID',
            'assigned_to' => 'assigned user',
            'due_date' => 'due date',
        ];
    }
}
