<?php

namespace Tests\Unit\Mail;

use App\Enums\Project\ProjectStatus;
use App\Mail\ProjectStatusChangedMail;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProjectStatusChangedMailTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function project_status_changed_mail_has_correct_subject(): void
    {
        // Arrange
        $project = Project::factory()->createQuietly(['name' => 'Test Project']);
        $changedBy = User::factory()->regularUser()->create();
        $recipient = User::factory()->regularUser()->create();

        $mail = new ProjectStatusChangedMail($project, ProjectStatus::Active, ProjectStatus::Completed, $changedBy, $recipient);

        // Act
        $envelope = $mail->envelope();

        // Assert
        $this->assertEquals('Project Status Updated: Test Project', $envelope->subject);
    }

    #[Test]
    public function project_status_changed_mail_uses_correct_view(): void
    {
        // Arrange
        $project = Project::factory()->createQuietly();
        $changedBy = User::factory()->regularUser()->create();
        $recipient = User::factory()->regularUser()->create();

        $mail = new ProjectStatusChangedMail($project, ProjectStatus::Active, ProjectStatus::Completed, $changedBy, $recipient);

        // Act
        $content = $mail->content();

        // Assert
        $this->assertEquals('emails.project-status-changed', $content->view);
    }

    #[Test]
    public function project_status_changed_mail_passes_correct_data_to_view(): void
    {
        // Arrange
        $createdBy = User::factory()->regularUser()->create();
        $project = Project::factory()->createQuietly([
            'name' => 'Test Project',
            'description' => 'Test Description',
            'created_by' => $createdBy->id,
        ]);
        $changedBy = User::factory()->regularUser()->create();
        $recipient = User::factory()->regularUser()->create();

        $mail = new ProjectStatusChangedMail($project, ProjectStatus::Active, ProjectStatus::Completed, $changedBy, $recipient);

        // Act
        $content = $mail->content();

        // Assert
        $this->assertEquals($project->id, $content->with['project']->id);
        $this->assertEquals(ProjectStatus::Active, $content->with['oldStatus']);
        $this->assertEquals(ProjectStatus::Completed, $content->with['newStatus']);
        $this->assertEquals($changedBy->id, $content->with['changedBy']->id);
        $this->assertEquals($recipient->id, $content->with['recipient']->id);
        $this->assertEquals($createdBy->id, $content->with['createdBy']->id);
    }

    #[Test]
    public function project_status_changed_mail_handles_all_status_transitions(): void
    {
        // Arrange
        $project = Project::factory()->createQuietly();
        $changedBy = User::factory()->regularUser()->create();
        $recipient = User::factory()->regularUser()->create();

        $transitions = [
            [ProjectStatus::Active, ProjectStatus::Completed],
            [ProjectStatus::Completed, ProjectStatus::Archived],
            [ProjectStatus::Archived, ProjectStatus::Active],
        ];

        foreach ($transitions as [$oldStatus, $newStatus]) {
            // Act
            $mail = new ProjectStatusChangedMail($project, $oldStatus, $newStatus, $changedBy, $recipient);
            $content = $mail->content();

            // Assert
            $this->assertEquals($oldStatus, $content->with['oldStatus']);
            $this->assertEquals($newStatus, $content->with['newStatus']);
        }
    }

    #[Test]
    public function project_status_changed_mail_has_no_attachments(): void
    {
        // Arrange
        $project = Project::factory()->createQuietly();
        $changedBy = User::factory()->regularUser()->create();
        $recipient = User::factory()->regularUser()->create();

        $mail = new ProjectStatusChangedMail($project, ProjectStatus::Active, ProjectStatus::Completed, $changedBy, $recipient);

        // Act
        $attachments = $mail->attachments();

        // Assert
        $this->assertEmpty($attachments);
    }

    #[Test]
    public function project_status_changed_mail_handles_project_without_description(): void
    {
        // Arrange
        $project = Project::factory()->createQuietly(['description' => null]);
        $changedBy = User::factory()->regularUser()->create();
        $recipient = User::factory()->regularUser()->create();

        $mail = new ProjectStatusChangedMail($project, ProjectStatus::Active, ProjectStatus::Completed, $changedBy, $recipient);

        // Act
        $content = $mail->content();

        // Assert
        $this->assertNull($content->with['project']->description);
    }
}
