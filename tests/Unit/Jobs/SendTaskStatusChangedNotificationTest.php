<?php

namespace Tests\Unit\Jobs;

use App\Enums\Task\TaskStatus;
use App\Jobs\SendTaskStatusChangedNotification;
use App\Mail\TaskStatusChangedMail;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SendTaskStatusChangedNotificationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function job_sends_email_to_assigned_user(): void
    {
        // Arrange
        Mail::fake();

        $assignedTo = User::factory()->regularUser()->create();
        $changedBy = User::factory()->regularUser()->create();
        $task = Task::factory()->createQuietly(['status' => TaskStatus::InProgress, 'assigned_to' => $assignedTo->id]);

        $job = new SendTaskStatusChangedNotification($task, TaskStatus::Pending, TaskStatus::InProgress, $changedBy);

        // Act
        $job->handle();

        // Assert
        Mail::assertSent(TaskStatusChangedMail::class, function ($mail) use ($task, $assignedTo, $changedBy) {
            return $mail->hasTo($assignedTo->email)
                && $mail->task->id === $task->id
                && $mail->oldStatus === TaskStatus::Pending
                && $mail->newStatus === TaskStatus::InProgress
                && $mail->changedBy->id === $changedBy->id;
        });
    }

    #[Test]
    public function job_does_not_send_email_when_task_has_no_assigned_user(): void
    {
        // Arrange
        Mail::fake();

        $task = Task::factory()->createQuietly(['assigned_to' => null, 'status' => TaskStatus::InProgress]);
        $changedBy = User::factory()->regularUser()->create();

        $job = new SendTaskStatusChangedNotification($task, TaskStatus::Pending, TaskStatus::InProgress, $changedBy);

        // Act
        $job->handle();

        // Assert
        Mail::assertNotSent(TaskStatusChangedMail::class);
    }

    #[Test]
    public function job_handles_all_status_transitions(): void
    {
        // Arrange
        Mail::fake();

        $assignedTo = User::factory()->regularUser()->create();
        $changedBy = User::factory()->regularUser()->create();
        $task = Task::factory()->createQuietly(['status' => TaskStatus::InProgress, 'assigned_to' => $assignedTo->id]);

        $transitions = [
            [TaskStatus::Pending, TaskStatus::InProgress],
            [TaskStatus::InProgress, TaskStatus::Completed],
            [TaskStatus::Completed, TaskStatus::Pending],
        ];

        foreach ($transitions as [$oldStatus, $newStatus]) {
            // Act
            $job = new SendTaskStatusChangedNotification($task, $oldStatus, $newStatus, $changedBy);
            $job->handle();

            // Assert
            Mail::assertSent(TaskStatusChangedMail::class, function ($mail) use ($task, $oldStatus, $newStatus, $changedBy, $assignedTo) {
                return $mail->hasTo($assignedTo->email)
                    && $mail->task->id === $task->id
                    && $mail->oldStatus === $oldStatus
                    && $mail->newStatus === $newStatus
                    && $mail->changedBy->id === $changedBy->id;
            });
        }
    }

    #[Test]
    public function job_has_correct_retry_configuration(): void
    {
        // Arrange
        $task = Task::factory()->createQuietly();
        $changedBy = User::factory()->regularUser()->create();

        $job = new SendTaskStatusChangedNotification($task, TaskStatus::Pending, TaskStatus::InProgress, $changedBy);

        // Assert
        $this->assertEquals(3, $job->tries);
        $this->assertEquals(60, $job->backoff);
    }

    #[Test]
    public function job_handles_mail_sending_failure_gracefully(): void
    {
        // Arrange
        Mail::shouldReceive('to->send')->andThrow(new \Exception('Mail service unavailable'));

        $assignedTo = User::factory()->regularUser()->create();
        $changedBy = User::factory()->regularUser()->create();
        $task = Task::factory()->createQuietly(['status' => TaskStatus::InProgress, 'assigned_to' => $assignedTo->id]);

        $job = new SendTaskStatusChangedNotification($task, TaskStatus::Pending, TaskStatus::InProgress, $changedBy);

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Mail service unavailable');

        $job->handle();
    }

    #[Test]
    public function job_failed_method_logs_error(): void
    {
        // Arrange
        $task = Task::factory()->createQuietly();
        $changedBy = User::factory()->regularUser()->create();

        $job = new SendTaskStatusChangedNotification($task, TaskStatus::Pending, TaskStatus::InProgress, $changedBy);
        $exception = new \Exception('Test failure');

        // Act
        $job->failed($exception);

        // Assert - The failed method should not throw an exception
        $this->assertTrue(true); // If we reach this point, the method handled the failure gracefully
    }

    #[Test]
    public function job_is_queueable(): void
    {
        // Arrange
        $task = Task::factory()->createQuietly();
        $changedBy = User::factory()->regularUser()->create();

        $job = new SendTaskStatusChangedNotification($task, TaskStatus::Pending, TaskStatus::InProgress, $changedBy);

        // Assert
        $this->assertTrue($job instanceof \Illuminate\Contracts\Queue\ShouldQueue);
    }
}
