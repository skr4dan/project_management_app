<?php

namespace App\Models;

use App\Enums\Project\ProjectStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Project Model
 *
 * Represents a project within the project management system that contains
 * multiple tasks and is owned by a user.
 *
 * @property int $id Unique identifier for the project
 * @property string $name Project name/title
 * @property string|null $description Detailed description of the project
 * @property ProjectStatus $status Current status of the project
 * @property int $created_by Foreign key to the user who created this project
 * @property \Carbon\Carbon $created_at Timestamp when the project was created
 * @property \Carbon\Carbon $updated_at Timestamp when the project was last updated
 * @property-read User $createdBy The user who created this project
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Task> $tasks Tasks belonging to this project
 *
 * @method static \Database\Factories\ProjectFactory factory($count = null, $state = [])
 */
class Project extends Model
{
    /** @use HasFactory<\Database\Factories\ProjectFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'status',
        'created_by',
    ];

    protected $casts = [
        'status' => ProjectStatus::class,
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /** -----------------------------
     * Relationships
     * ----------------------------- */

    /**
     * Get the user who created the project.
     *
     * @return BelongsTo<User, covariant Project>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the tasks for the project.
     *
     * @return HasMany<Task, covariant Project>
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}
