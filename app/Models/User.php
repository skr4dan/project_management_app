<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\User\UserStatus;
use App\Observers\UserObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

/**
 * User Model
 *
 * Represents a system user with authentication, role-based access control,
 * and project/task management capabilities.
 *
 * @property int $id Unique identifier for the user
 * @property string $first_name User's first name
 * @property string $last_name User's last name
 * @property string $email Unique email address for authentication
 * @property string $password Hashed password for authentication
 * @property int|null $role_id Foreign key to the role this user belongs to
 * @property UserStatus $status Current status of the user account
 * @property string|null $avatar Path to user's avatar image file
 * @property string|null $phone User's phone number
 * @property string|null $remember_token Token for "remember me" functionality
 * @property \Carbon\Carbon $created_at Timestamp when the user was created
 * @property \Carbon\Carbon $updated_at Timestamp when the user was last updated
 * @property-read Role|null $role The role this user belongs to
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Project> $projects Projects created by this user
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Task> $createdTasks Tasks created by this user
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Task> $assignedTasks Tasks assigned to this user
 *
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 */
#[ObservedBy(UserObserver::class)]
class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'role_id',
        'status',
        'avatar',
        'phone',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'status' => UserStatus::class,
        ];
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed The user's primary key value
     */
    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array<mixed> Custom claims for the JWT token
     */
    public function getJWTCustomClaims(): array
    {
        return [];
    }

    /**
     * Get the role that belongs to the user.
     *
     * @return BelongsTo<Role, covariant User> The role relationship
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }
}
