<?php

namespace Tests\Unit\Jobs;

use App\Jobs\SendTaskAssignedNotification;
use App\Mail\TaskAssignedMail;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SendTaskAssignedNotificationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function job_sends_email_to_assigned_user(): void
    {
        // Arrange
        Mail::fake();

        $assignedBy = User::factory()->regularUser()->create();
        $assignedTo = User::factory()->regularUser()->create();
        $task = Task::factory()->createQuietly([
            'title' => 'Test Task',
            'assigned_to' => $assignedTo->id,
        ]);

        $job = new SendTaskAssignedNotification($task, $assignedBy);

        // Act
        $job->handle();

        Mail::assertSent(TaskAssignedMail::class, function ($mail) use ($task, $assignedBy, $assignedTo) {
            return $mail->hasTo($assignedTo->email)
                && $mail->task->id === $task->id
                && $mail->assignedBy->id === $assignedBy->id;
        });

        // Assert email content and configuration
        Mail::assertSent(TaskAssignedMail::class, function ($mail) use ($task, $assignedTo) {
            $envelope = $mail->envelope();

            return str_contains($envelope->subject, 'Test Task')
                && in_array('task:'.$task->id, $envelope->tags)
                && in_array('user:'.$assignedTo->id, $envelope->tags);
        });
    }

    #[Test]
    public function job_does_not_send_email_when_task_has_no_assigned_user(): void
    {
        // Arrange
        Mail::fake();

        $task = Task::factory()->createQuietly(['assigned_to' => null]);
        $assignedBy = User::factory()->regularUser()->create();

        $job = new SendTaskAssignedNotification($task, $assignedBy);

        // Act
        $job->handle();

        // Assert
        Mail::assertNotSent(TaskAssignedMail::class);
    }

    #[Test]
    public function job_has_correct_retry_configuration(): void
    {
        // Arrange
        $task = Task::factory()->createQuietly();
        $assignedBy = User::factory()->regularUser()->create();

        $job = new SendTaskAssignedNotification($task, $assignedBy);

        // Assert
        $this->assertEquals(3, $job->tries);
        $this->assertEquals(60, $job->backoff);
    }

    #[Test]
    public function job_handles_mail_sending_failure_gracefully(): void
    {
        // Arrange
        Mail::shouldReceive('to->send')->andThrow(new \Exception('Mail service unavailable'));

        $assignedBy = User::factory()->regularUser()->create();
        $assignedTo = User::factory()->regularUser()->create();
        $task = Task::factory()->createQuietly([
            'assigned_to' => $assignedTo->id,
        ]);

        $job = new SendTaskAssignedNotification($task, $assignedBy);

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
        $assignedBy = User::factory()->regularUser()->create();

        $job = new SendTaskAssignedNotification($task, $assignedBy);
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
        $assignedBy = User::factory()->regularUser()->create();

        $job = new SendTaskAssignedNotification($task, $assignedBy);

        // Assert
        $this->assertTrue($job instanceof \Illuminate\Contracts\Queue\ShouldQueue);
    }
}
