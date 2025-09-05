<?php

namespace App\Http\Requests\Api;

use App\Enums\Task\TaskPriority;
use App\Enums\Task\TaskStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Request class for task index endpoint with filtering and validation
 */
class TaskIndexRequest extends FormRequest
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
            'status' => ['nullable', 'string', Rule::in(array_column(TaskStatus::cases(), 'value'))],
            'priority' => ['nullable', 'string', Rule::in(array_column(TaskPriority::cases(), 'value'))],
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'sort_by' => ['nullable', 'string', Rule::in($this->getSortableFields())],
            'sort_order' => ['nullable', 'string', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    private function getSortableFields(): array
    {
        return ['due_date', 'created_at'];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.in' => 'Status must be one of: '.implode(', ', array_column(TaskStatus::cases(), 'value')),
            'priority.in' => 'Priority must be one of: '.implode(', ', array_column(TaskPriority::cases(), 'value')),
            'project_id.exists' => 'Selected project does not exist',
            'assigned_to.exists' => 'Selected user does not exist',
            'sort_by.in' => 'Sort by must be one of: '.implode(', ', $this->getSortableFields()),
            'sort_order.in' => 'Sort order must be asc or desc',
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
            'status' => 'task status',
            'priority' => 'task priority',
            'project_id' => 'project ID',
            'assigned_to' => 'assigned user',
            'sort_by' => 'sort field',
            'sort_order' => 'sort order',
        ];
    }

    /**
     * Get filters array from validated request data
     *
     * @return array<string, mixed>
     */
    public function getFilters(): array
    {
        $validated = $this->validated();
        $filters = [];

        // Only include non-null values
        foreach ($validated as $key => $value) {
            if ($value !== null && $value !== '') {
                // Convert boolean strings and ensure proper types
                if (in_array($key, ['project_id', 'assigned_to'])) {
                    $filters[$key] = (int) $value;
                } else {
                    $filters[$key] = $value;
                }
            }
        }

        return $filters;
    }

    /**
     * Get pagination data from validated request
     *
     * @return array<string, int>
     */
    public function getPaginationData(): array
    {
        $validated = $this->validated();

        return [
            'per_page' => $validated['per_page'] ?? 15,
            'page' => $validated['page'] ?? 1,
        ];
    }

    /**
     * Get filter summary for debugging/logging
     *
     * @return array<string, mixed>
     */
    public function getFilterSummary(): array
    {
        return [
            'filters_applied' => count($this->getFilters()),
            'has_search' => $this->has('search'),
            'has_sorting' => $this->has('sort_by'),
            'filter_keys' => array_keys($this->getFilters()),
        ];
    }
}
