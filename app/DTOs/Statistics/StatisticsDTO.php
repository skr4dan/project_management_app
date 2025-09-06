<?php

namespace App\DTOs\Statistics;

readonly class StatisticsDTO
{
    public function __construct(
        public int $totalProjects,
        public int $totalTasks,
        /** @var array<string, int> */
        public array $tasksByStatus,
        public int $overdueTasks,
        /** @var array<array{id: int, name: string, email: string, task_count: int}> */
        public array $topActiveUsers,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            totalProjects: $data['total_projects'],
            totalTasks: $data['total_tasks'],
            tasksByStatus: $data['tasks_by_status'],
            overdueTasks: $data['overdue_tasks'],
            topActiveUsers: $data['top_active_users'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'total_projects' => $this->totalProjects,
            'total_tasks' => $this->totalTasks,
            'tasks_by_status' => $this->tasksByStatus,
            'overdue_tasks' => $this->overdueTasks,
            'top_active_users' => $this->topActiveUsers,
        ];
    }
}
