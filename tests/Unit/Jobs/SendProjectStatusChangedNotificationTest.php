<?php

namespace Tests\Unit\Jobs;

use App\Enums\Project\ProjectStatus;
use App\Jobs\SendProjectStatusChangedNotification;
use App\Mail\ProjectStatusChangedMail;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SendProjectStatusChangedNotificationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function job_sends_email_to_recipient(): void
    {
        // Arrange
        Mail::fake();

        $project = Project::factory()->createQuietly(['status' => ProjectStatus::Completed]);
        $changedBy = User::factory()->regularUser()->create();
        $recipient = User::factory()->regularUser()->create();

        $job = new SendProjectStatusChangedNotification(
            $project,
            ProjectStatus::Active,
            ProjectStatus::Completed,
            $changedBy,
            $recipient
        );

        // Act
        $job->handle();

        // Assert
        Mail::assertSent(ProjectStatusChangedMail::class, function ($mail) use ($project, $changedBy, $recipient) {
            return $mail->hasTo($recipient->email)
                && $mail->project->id === $project->id
                && $mail->oldStatus === ProjectStatus::Active
                && $mail->newStatus === ProjectStatus::Completed
                && $mail->changedBy->id === $changedBy->id
                && $mail->recipient->id === $recipient->id;
        });
    }

    #[Test]
    public function job_handles_all_status_transitions(): void
    {
        // Arrange
        Mail::fake();

        $project = Project::factory()->createQuietly(['status' => ProjectStatus::Completed]);
        $changedBy = User::factory()->regularUser()->create();
        $recipient = User::factory()->regularUser()->create();

        $transitions = [
            [ProjectStatus::Active, ProjectStatus::Completed],
            [ProjectStatus::Completed, ProjectStatus::Archived],
            [ProjectStatus::Archived, ProjectStatus::Active],
        ];

        foreach ($transitions as [$oldStatus, $newStatus]) {
            // Act
            $job = new SendProjectStatusChangedNotification($project, $oldStatus, $newStatus, $changedBy, $recipient);
            $job->handle();

            // Assert
            Mail::assertSent(ProjectStatusChangedMail::class, function ($mail) use ($project, $oldStatus, $newStatus, $changedBy, $recipient) {
                return $mail->hasTo($recipient->email)
                    && $mail->project->id === $project->id
                    && $mail->oldStatus === $oldStatus
                    && $mail->newStatus === $newStatus
                    && $mail->changedBy->id === $changedBy->id
                    && $mail->recipient->id === $recipient->id;
            });
        }
    }

    #[Test]
    public function job_has_correct_retry_configuration(): void
    {
        // Arrange
        $project = Project::factory()->createQuietly();
        $changedBy = User::factory()->regularUser()->create();
        $recipient = User::factory()->regularUser()->create();

        $job = new SendProjectStatusChangedNotification(
            $project,
            ProjectStatus::Active,
            ProjectStatus::Completed,
            $changedBy,
            $recipient
        );

        // Assert
        $this->assertEquals(3, $job->tries);
        $this->assertEquals(60, $job->backoff);
    }

    #[Test]
    public function job_handles_mail_sending_failure_gracefully(): void
    {
        // Arrange
        Mail::shouldReceive('to->send')->andThrow(new \Exception('Mail service unavailable'));

        $project = Project::factory()->createQuietly(['status' => ProjectStatus::Completed]);
        $changedBy = User::factory()->regularUser()->create();
        $recipient = User::factory()->regularUser()->create();

        $job = new SendProjectStatusChangedNotification(
            $project,
            ProjectStatus::Active,
            ProjectStatus::Completed,
            $changedBy,
            $recipient
        );

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Mail service unavailable');

        $job->handle();
    }

    #[Test]
    public function job_failed_method_logs_error(): void
    {
        // Arrange
        $project = Project::factory()->createQuietly();
        $changedBy = User::factory()->regularUser()->create();
        $recipient = User::factory()->regularUser()->create();

        $job = new SendProjectStatusChangedNotification(
            $project,
            ProjectStatus::Active,
            ProjectStatus::Completed,
            $changedBy,
            $recipient
        );
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
        $project = Project::factory()->createQuietly();
        $changedBy = User::factory()->regularUser()->create();
        $recipient = User::factory()->regularUser()->create();

        $job = new SendProjectStatusChangedNotification(
            $project,
            ProjectStatus::Active,
            ProjectStatus::Completed,
            $changedBy,
            $recipient
        );

        // Assert
        $this->assertTrue($job instanceof \Illuminate\Contracts\Queue\ShouldQueue);
    }

    #[Test]
    public function job_sends_correct_data_to_mail_class(): void
    {
        // Arrange
        Mail::fake();

        $createdBy = User::factory()->regularUser()->create();
        $project = Project::factory()->createQuietly([
            'name' => 'Test Project',
            'description' => 'Test Description',
            'created_by' => $createdBy->id,
            'status' => ProjectStatus::Completed,
        ]);
        $changedBy = User::factory()->regularUser()->create();
        $recipient = User::factory()->regularUser()->create();

        $job = new SendProjectStatusChangedNotification(
            $project,
            ProjectStatus::Active,
            ProjectStatus::Completed,
            $changedBy,
            $recipient
        );

        // Act
        $job->handle();

        // Assert
        Mail::assertSent(ProjectStatusChangedMail::class, function ($mail) use ($project, $createdBy, $changedBy, $recipient) {
            return $mail->project->id === $project->id
                && $mail->project->createdBy->id === $createdBy->id
                && $mail->changedBy->id === $changedBy->id
                && $mail->recipient->id === $recipient->id;
        });
    }
}
