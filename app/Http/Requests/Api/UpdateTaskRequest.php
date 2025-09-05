<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskRequest extends FormRequest
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
            'title' => ['sometimes', 'string', 'min:3', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'status' => ['sometimes', 'string', 'in:pending,in_progress,completed'],
            'priority' => ['sometimes', 'string', 'in:low,medium,high'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'due_date' => ['nullable', 'date'],
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
            'title.string' => 'Task title must be a string',
            'title.min' => 'Task title must be at least 3 characters long',
            'title.max' => 'Task title cannot exceed 255 characters',
            'description.string' => 'Task description must be a string',
            'description.max' => 'Task description cannot exceed 1000 characters',
            'status.in' => 'Task status must be one of: pending, in_progress, completed',
            'priority.in' => 'Task priority must be one of: low, medium, high',
            'assigned_to.integer' => 'Assigned user ID must be an integer',
            'assigned_to.exists' => 'Selected user does not exist',
            'due_date.date' => 'Due date must be a valid date',
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
            'status' => 'task status',
            'priority' => 'task priority',
            'assigned_to' => 'assigned user',
            'due_date' => 'due date',
        ];
    }
}
