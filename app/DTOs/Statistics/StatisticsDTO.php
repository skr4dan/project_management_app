<?php

namespace App\DTOs\Statistics;

readonly class StatisticsDTO
{
    public function __construct(
        public int $totalProjects,
        public int $totalTasks,
        public array $tasksByStatus,
        public int $overdueTasks,
        public array $topActiveUsers,
    ) {}

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
