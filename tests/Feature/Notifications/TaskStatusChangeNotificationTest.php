<?php

namespace Tests\Feature\Notifications;

use App\Enums\Task\TaskStatus;
use App\Jobs\SendTaskStatusChangedNotification as SendTaskStatusChangedNotificationJob;
use App\Listeners\SendTaskStatusChangedNotification;
use App\Mail\TaskStatusChangedMail;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TaskStatusChangeNotificationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function task_status_change_triggers_notification_email(): void
    {
        Mail::fake();
        Queue::fakeExcept([
            SendTaskStatusChangedNotification::class,
        ]);

        $assignedTo = User::factory()->regularUser()->create();
        $changedBy = User::factory()->admin()->create();
        $projectCreator = User::factory()->manager()->create();
        $project = Project::factory()->createQuietly(['created_by' => $projectCreator->id]);
        $task = Task::factory()->createQuietly([
            'project_id' => $project->id,
            'created_by' => $projectCreator->id,
            'assigned_to' => null,
            'status' => TaskStatus::Pending,
        ]);

        $token = $this->authenticateUser($changedBy);
        $this
            ->withHeaders($this->getAuthHeader($token))
            ->put("/api/tasks/{$task->id}", [
                'assigned_to' => $assignedTo->id,
            ])
            ->assertStatus(200);

        $response = $this
            ->withHeaders($this->getAuthHeader($token))
            ->put("/api/tasks/{$task->id}", [
                'status' => TaskStatus::InProgress->value,
            ]);

        $response
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', TaskStatus::InProgress->value)
            ->assertJsonPath('data.assigned_to.id', $assignedTo->id);

        $this->assertEquals(TaskStatus::InProgress, $task->fresh()->status);

        Queue::assertPushed(
            SendTaskStatusChangedNotificationJob::class,
            fn ($job) => $job->task->id === $task->id
                && $job->oldStatus === TaskStatus::Pending
                && $job->newStatus === TaskStatus::InProgress
                && $job->changedBy->id === $changedBy->id
        );
    }

    #[Test]
    public function task_status_change_to_same_status_does_not_trigger_notification(): void
    {
        Queue::fakeExcept([
            SendTaskStatusChangedNotification::class,
        ]);

        $projectCreator = User::factory()->manager()->create();
        $project = Project::factory()->createQuietly(['created_by' => $projectCreator->id]);
        $task = Task::factory()->createQuietly([
            'project_id' => $project->id,
            'created_by' => $projectCreator->id,
            'status' => TaskStatus::Pending,
        ]);
        $assignedTo = User::factory()->regularUser()->create();
        $changedBy = User::factory()->admin()->create();

        $token = $this->authenticateUser($changedBy);
        $this
            ->withHeaders($this->getAuthHeader($token))
            ->put("/api/tasks/{$task->id}", [
                'assigned_to' => $assignedTo->id,
            ])
            ->assertStatus(200);

        $response = $this
            ->withHeaders($this->getAuthHeader($token))
            ->put("/api/tasks/{$task->id}", [
                'status' => TaskStatus::Pending->value,
            ]);

        $response
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => TaskStatus::Pending->value,
                ],
            ]);

        $this->assertEquals(TaskStatus::Pending, $task->fresh()->status);

        Queue::assertNotPushed(SendTaskStatusChangedNotificationJob::class);
    }

    #[Test]
    public function task_status_change_notification_sent_via_queue(): void
    {
        Queue::fakeExcept([
            SendTaskStatusChangedNotification::class,
        ]);

        $projectCreator = User::factory()->manager()->create();
        $project = Project::factory()->createQuietly(['created_by' => $projectCreator->id]);
        $task = Task::factory()->createQuietly([
            'project_id' => $project->id,
            'created_by' => $projectCreator->id,
            'status' => TaskStatus::Pending,
        ]);
        $assignedTo = User::factory()->regularUser()->create();
        $changedBy = User::factory()->admin()->create();

        $token = $this->authenticateUser($changedBy);
        $this
            ->withHeaders($this->getAuthHeader($token))
            ->put("/api/tasks/{$task->id}", [
                'assigned_to' => $assignedTo->id,
            ])
            ->assertStatus(200);

        $this
            ->withHeaders($this->getAuthHeader($token))
            ->put("/api/tasks/{$task->id}", [
                'status' => TaskStatus::InProgress->value,
            ])
            ->assertStatus(200);

        Queue::assertPushedOn('notifications', SendTaskStatusChangedNotificationJob::class);
    }

    #[Test]
    public function task_status_change_notification_includes_correct_data(): void
    {
        Mail::fake();

        $projectCreator = User::factory()->manager()->create();
        $project = Project::factory()->createQuietly([
            'created_by' => $projectCreator->id,
            'name' => 'Test Project',
        ]);
        $task = Task::factory()->createQuietly([
            'project_id' => $project->id,
            'created_by' => $projectCreator->id,
            'title' => 'Test Task',
            'status' => TaskStatus::Pending,
        ]);
        $assignedTo = User::factory()->regularUser()->create();
        $changedBy = User::factory()->admin()->create();

        $token = $this->authenticateUser($changedBy);
        $this
            ->withHeaders($this->getAuthHeader($token))
            ->put("/api/tasks/{$task->id}", [
                'assigned_to' => $assignedTo->id,
            ])
            ->assertStatus(200);

        $job = new SendTaskStatusChangedNotificationJob(
            $task,
            TaskStatus::Pending,
            TaskStatus::InProgress,
            $changedBy
        );

        $job->handle();

        Mail::assertSent(
            TaskStatusChangedMail::class,
            fn ($mail) => $mail->task->id === $task->id
                && $mail->oldStatus === TaskStatus::Pending
                && $mail->newStatus === TaskStatus::InProgress
                && $mail->changedBy->id === $changedBy->id
                && $mail->task->assignedTo->id === $assignedTo->id
                && $mail->task->project->id === $project->id
        );
    }

    #[Test]
    public function task_status_change_without_assigned_user_does_not_trigger_notification(): void
    {
        Queue::fakeExcept([
            SendTaskStatusChangedNotification::class,
        ]);

        $projectCreator = User::factory()->manager()->create();
        $project = Project::factory()->createQuietly(['created_by' => $projectCreator->id]);
        $task = Task::factory()->createQuietly([
            'project_id' => $project->id,
            'created_by' => $projectCreator->id,
            'assigned_to' => null,
            'status' => TaskStatus::Pending,
        ]);
        $changedBy = User::factory()->admin()->create();

        $token = $this->authenticateUser($changedBy);
        $response = $this
            ->withHeaders($this->getAuthHeader($token))
            ->put("/api/tasks/{$task->id}", [
                'status' => TaskStatus::InProgress->value,
            ]);

        $response
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', TaskStatus::InProgress->value)
            ->assertJsonPath('data.assigned_to.id', null);

        $this->assertEquals(TaskStatus::InProgress, $task->fresh()->status);

        Queue::assertNotPushed(SendTaskStatusChangedNotificationJob::class);
    }

    #[Test]
    public function task_status_change_handles_all_transitions(): void
    {
        Queue::fakeExcept([
            SendTaskStatusChangedNotification::class,
        ]);

        $projectCreator = User::factory()->manager()->create();
        $project = Project::factory()->createQuietly(['created_by' => $projectCreator->id]);
        $task = Task::factory()->createQuietly([
            'project_id' => $project->id,
            'created_by' => $projectCreator->id,
            'status' => TaskStatus::Pending,
        ]);
        $assignedTo = User::factory()->regularUser()->create();
        $changedBy = User::factory()->admin()->create();

        $token = $this->authenticateUser($changedBy);
        $this
            ->withHeaders($this->getAuthHeader($token))
            ->put("/api/tasks/{$task->id}", [
                'assigned_to' => $assignedTo->id,
            ])
            ->assertStatus(200);

        $transitions = [
            [TaskStatus::Pending, TaskStatus::InProgress],
            [TaskStatus::InProgress, TaskStatus::Completed],
            [TaskStatus::Completed, TaskStatus::Pending],
        ];

        foreach ($transitions as [$oldStatus, $newStatus]) {
            $this
                ->withHeaders($this->getAuthHeader($token))
                ->put("/api/tasks/{$task->id}", [
                    'status' => $oldStatus->value,
                ])
                ->assertStatus(200);

            $this
                ->withHeaders($this->getAuthHeader($token))
                ->put("/api/tasks/{$task->id}", [
                    'status' => $newStatus->value,
                ])
                ->assertStatus(200);

            $this->assertEquals($newStatus, $task->fresh()->status);
        }

        Queue::assertPushed(SendTaskStatusChangedNotificationJob::class, 3);
    }
}
