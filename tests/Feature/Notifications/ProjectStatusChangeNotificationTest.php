<?php

namespace Tests\Feature\Notifications;

use App\Enums\Project\ProjectStatus;
use App\Jobs\SendProjectStatusChangedNotification as SendProjectStatusChangedNotificationJob;
use App\Listeners\SendProjectStatusChangedNotification;
use App\Mail\ProjectStatusChangedMail;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProjectStatusChangeNotificationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function project_status_change_triggers_notification_emails(): void
    {
        Mail::fake();
        Queue::fakeExcept([
            SendProjectStatusChangedNotification::class,
        ]);

        $changedBy = User::factory()->admin()->create();
        $projectCreator = User::factory()->manager()->create();
        $project = Project::factory()
            ->createQuietly([
                'created_by' => $projectCreator->id,
                'status' => ProjectStatus::Active,
            ]);

        $token = $this->authenticateUser($changedBy);
        $response = $this
            ->withHeaders($this->getAuthHeader($token))
            ->put("/api/projects/{$project->id}", [
                'status' => ProjectStatus::Completed->value,
            ]);

        $response
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => ProjectStatus::Completed->value,
                ],
            ]);

        $this->assertEquals(ProjectStatus::Completed, $project->fresh()->status);

        Queue::assertPushed(
            SendProjectStatusChangedNotificationJob::class,
            fn ($job) => $job->project->id === $project->id
                && $job->oldStatus === ProjectStatus::Active
                && $job->newStatus === ProjectStatus::Completed
                && $job->changedBy->id === $changedBy->id
                && $job->recipient->id === $projectCreator->id
        );
    }

    #[Test]
    public function project_status_change_sends_notifications_to_all_participants(): void
    {
        Queue::fakeExcept([
            SendProjectStatusChangedNotification::class,
        ]);

        $projectCreator = User::factory()->admin()->create();
        $taskAssignee1 = User::factory()->regularUser()->create();
        $taskAssignee2 = User::factory()->regularUser()->create();
        $changedBy = User::factory()->admin()->create();

        $project = Project::factory()
            ->createQuietly([
                'created_by' => $projectCreator->id,
                'status' => ProjectStatus::Active,
            ]);

        $token = $this->authenticateUser($projectCreator);

        $task1 = Task::factory()
            ->createQuietly([
                'project_id' => $project->id,
                'assigned_to' => null,
            ]);
        $this
            ->withHeaders($this->getAuthHeader($token))
            ->put("/api/tasks/{$task1->id}", [
                'assigned_to' => $taskAssignee1->id,
            ])
            ->assertStatus(200);

        $task2 = Task::factory()
            ->createQuietly([
                'project_id' => $project->id,
                'assigned_to' => null,
            ]);
        $this
            ->withHeaders($this->getAuthHeader($token))
            ->put("/api/tasks/{$task2->id}", [
                'assigned_to' => $taskAssignee2->id,
            ])
            ->assertStatus(200);

        $token = $this->authenticateUser($changedBy);
        $this
            ->withHeaders($this->getAuthHeader($token))
            ->put("/api/projects/{$project->id}", [
                'status' => ProjectStatus::Completed->value,
            ])
            ->assertStatus(200);

        Queue::assertPushed(SendProjectStatusChangedNotificationJob::class, 3);

        $recipients = collect([$projectCreator, $taskAssignee1, $taskAssignee2]);
        foreach ($recipients as $recipient) {
            Queue::assertPushed(
                SendProjectStatusChangedNotificationJob::class,
                fn ($job) => $job->recipient->id === $recipient->id
            );
        }
    }

    #[Test]
    public function project_status_change_excludes_user_who_changed_status(): void
    {
        Queue::fakeExcept([
            SendProjectStatusChangedNotification::class,
        ]);

        $projectCreator = User::factory()->manager()->create();
        $changedBy = User::factory()->admin()->create();

        $project = Project::factory()
            ->createQuietly([
                'created_by' => $projectCreator->id,
                'status' => ProjectStatus::Active,
            ]);

        $task = Task::factory()
            ->createQuietly([
                'project_id' => $project->id,
                'created_by' => $projectCreator->id,
                'assigned_to' => null,
            ]);

        $token = $this->authenticateUser($projectCreator);
        $this
            ->withHeaders($this->getAuthHeader($token))
            ->put("/api/tasks/{$task->id}", [
                'assigned_to' => $changedBy->id,
            ])
            ->assertStatus(200);

        $token = $this->authenticateUser($changedBy);
        $this
            ->withHeaders($this->getAuthHeader($token))
            ->put("/api/projects/{$project->id}", [
                'status' => ProjectStatus::Completed->value,
            ])
            ->assertStatus(200);

        Queue::assertPushed(SendProjectStatusChangedNotificationJob::class, 1);

        Queue::assertPushed(
            SendProjectStatusChangedNotificationJob::class,
            fn ($job) => $job->recipient->id === $projectCreator->id
        );

        Queue::assertNotPushed(
            SendProjectStatusChangedNotificationJob::class,
            fn ($job) => $job->recipient->id === $changedBy->id
        );
    }

    #[Test]
    public function project_status_change_to_same_status_does_not_trigger_notification(): void
    {
        Queue::fakeExcept([
            SendProjectStatusChangedNotification::class,
        ]);

        $projectCreator = User::factory()->admin()->create();
        $project = Project::factory()
            ->createQuietly([
                'created_by' => $projectCreator->id,
                'status' => ProjectStatus::Active,
            ]);
        $changedBy = User::factory()->admin()->create();

        $token = $this->authenticateUser($changedBy);
        $response = $this
            ->withHeaders($this->getAuthHeader($token))
            ->put("/api/projects/{$project->id}", [
                'status' => ProjectStatus::Active->value,
            ]);

        $response
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => ProjectStatus::Active->value,
                ],
            ]);

        $this->assertEquals(ProjectStatus::Active, $project->fresh()->status);

        Queue::assertNotPushed(SendProjectStatusChangedNotificationJob::class);
    }

    #[Test]
    public function project_status_change_notification_sent_via_queue(): void
    {
        Queue::fakeExcept([
            SendProjectStatusChangedNotification::class,
        ]);

        $projectCreator = User::factory()->admin()->create();
        $project = Project::factory()
            ->createQuietly([
                'created_by' => $projectCreator->id,
                'status' => ProjectStatus::Active,
            ]);
        $changedBy = User::factory()->admin()->create();

        $token = $this->authenticateUser($changedBy);
        $this
            ->withHeaders($this->getAuthHeader($token))
            ->put("/api/projects/{$project->id}", [
                'status' => ProjectStatus::Completed->value,
            ])
            ->assertStatus(200);

        Queue::assertPushedOn('notifications', SendProjectStatusChangedNotificationJob::class);
    }

    #[Test]
    public function project_status_change_notification_includes_correct_data(): void
    {
        Mail::fake();

        $createdBy = User::factory()->admin()->create();
        $project = Project::factory()
            ->createQuietly([
                'name' => 'Test Project',
                'description' => 'Test Description',
                'created_by' => $createdBy->id,
                'status' => ProjectStatus::Active,
            ]);
        $changedBy = User::factory()->admin()->create();
        $recipient = User::factory()->regularUser()->create();

        $token = $this->authenticateUser($changedBy);
        $this
            ->withHeaders($this->getAuthHeader($token))
            ->put("/api/projects/{$project->id}", [
                'status' => ProjectStatus::Completed->value,
            ])
            ->assertStatus(200);

        $job = new SendProjectStatusChangedNotificationJob(
            $project,
            ProjectStatus::Active,
            ProjectStatus::Completed,
            $changedBy,
            $recipient
        );

        $job->handle();

        Mail::assertSent(
            ProjectStatusChangedMail::class,
            fn ($mail) => $mail->project->id === $project->id
                && $mail->project->createdBy->id === $createdBy->id
                && $mail->oldStatus === ProjectStatus::Active
                && $mail->newStatus === ProjectStatus::Completed
                && $mail->changedBy->id === $changedBy->id
                && $mail->recipient->id === $recipient->id
        );
    }

    #[Test]
    public function project_status_change_handles_duplicate_recipients(): void
    {
        Queue::fakeExcept([
            SendProjectStatusChangedNotification::class,
        ]);

        $user = User::factory()->admin()->create();
        $changedBy = User::factory()->admin()->create();

        $project = Project::factory()
            ->createQuietly([
                'created_by' => $user->id,
                'status' => ProjectStatus::Active,
            ]);

        $token = $this->authenticateUser($user);
        $task = Task::factory()
            ->createQuietly([
                'project_id' => $project->id,
                'assigned_to' => null,
            ]);
        $this
            ->withHeaders($this->getAuthHeader($token))
            ->put("/api/tasks/{$task->id}", [
                'assigned_to' => $user->id,
            ])
            ->assertStatus(200);

        $token = $this->authenticateUser($changedBy);
        $this
            ->withHeaders($this->getAuthHeader($token))
            ->put("/api/projects/{$project->id}", [
                'status' => ProjectStatus::Completed->value,
            ])
            ->assertStatus(200);

        Queue::assertPushed(SendProjectStatusChangedNotificationJob::class, 1);

        Queue::assertPushed(
            SendProjectStatusChangedNotificationJob::class,
            fn ($job) => $job->recipient->id === $user->id
        );
    }

    #[Test]
    public function project_status_change_handles_all_transitions(): void
    {
        Queue::fakeExcept([
            SendProjectStatusChangedNotification::class,
        ]);

        $projectCreator = User::factory()->admin()->create();
        $project = Project::factory()
            ->createQuietly([
                'created_by' => $projectCreator->id,
                'status' => ProjectStatus::Active,
            ]);
        $changedBy = User::factory()->admin()->create();

        $transitions = [
            [ProjectStatus::Active, ProjectStatus::Completed],
            [ProjectStatus::Completed, ProjectStatus::Archived],
            [ProjectStatus::Archived, ProjectStatus::Active],
        ];

        foreach ($transitions as [$oldStatus, $newStatus]) {
            $token = $this->authenticateUser($changedBy);
            $this
                ->withHeaders($this->getAuthHeader($token))
                ->put("/api/projects/{$project->id}", [
                    'status' => $oldStatus->value,
                ])
                ->assertStatus(200);

            $this
                ->withHeaders($this->getAuthHeader($token))
                ->put("/api/projects/{$project->id}", [
                    'status' => $newStatus->value,
                ])
                ->assertStatus(200);

            $this->assertEquals($newStatus, $project->fresh()->status);
        }

        Queue::assertPushed(SendProjectStatusChangedNotificationJob::class, 3);
    }
}
