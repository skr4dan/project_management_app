<?php

namespace App\Models;

use App\Enums\Task\TaskPriority;
use App\Enums\Task\TaskStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Task Model
 *
 * Represents a task within a project that can be assigned to users
 * with different priorities and statuses.
 *
 * @property int $id Unique identifier for the task
 * @property string $title Task title/name
 * @property string|null $description Detailed description of the task
 * @property TaskStatus $status Current status of the task
 * @property TaskPriority $priority Priority level of the task
 * @property int $project_id Foreign key to the project this task belongs to
 * @property int|null $assigned_to Foreign key to the user this task is assigned to
 * @property int $created_by Foreign key to the user who created this task
 * @property \Carbon\Carbon|null $due_date Deadline for task completion
 * @property \Carbon\Carbon $created_at Timestamp when the task was created
 * @property \Carbon\Carbon $updated_at Timestamp when the task was last updated
 * @property-read Project $project The project this task belongs to
 * @property-read User|null $assignedTo The user this task is assigned to
 * @property-read User $createdBy The user who created this task
 *
 * @method static \Database\Factories\TaskFactory factory($count = null, $state = [])
 */
class Task extends Model
{
    /** @use HasFactory<\Database\Factories\TaskFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'status',
        'priority',
        'project_id',
        'assigned_to',
        'created_by',
        'due_date',
    ];

    protected $casts = [
        'status' => TaskStatus::class,
        'priority' => TaskPriority::class,
        'due_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /** -----------------------------
     * Relationships
     * ----------------------------- */

    /**
     * Get the project that owns the task.
     *
     * @return BelongsTo<Project, covariant Task> The project relationship
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the user that the task is assigned to.
     *
     * @return BelongsTo<User, covariant Task> The assigned user relationship
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get the user who created the task.
     *
     * @return BelongsTo<User, covariant Task> The creator user relationship
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
