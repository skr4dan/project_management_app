<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Role Model
 *
 * Represents a user role with associated permissions for access control
 * within the project management system.
 *
 * @property int $id Unique identifier for the role
 * @property string $slug Unique slug identifier for the role (admin, manager, user)
 * @property string $name Display name of the role
 * @property array<int, string>|null $permissions Array of permission strings granted to this role
 * @property bool $is_active Whether the role is active and can be assigned to users
 * @property \Carbon\Carbon $created_at Timestamp when the role was created
 * @property \Carbon\Carbon $updated_at Timestamp when the role was last updated
 * @property-read \Illuminate\Database\Eloquent\Collection<int, User> $users Users assigned to this role
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Role active() Get only active roles
 * @method static \Illuminate\Database\Eloquent\Builder|Role bySlug(string $slug) Find role by slug
 * @method static \Database\Factories\RoleFactory factory($count = null, $state = [])
 */
class Role extends Model
{
    /** @use HasFactory<\Database\Factories\RoleFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'slug',
        'name',
        'permissions',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the users for the role.
     *
     * @return HasMany<User, Role> The users relationship
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Scope to get active roles only.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Role>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Role>
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to find role by slug.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Role>  $query
     * @param  string  $slug  The role slug to search for
     * @return \Illuminate\Database\Eloquent\Builder<Role>
     */
    public function scopeBySlug($query, string $slug)
    {
        return $query->where('slug', $slug);
    }

    /**
     * Check if role has a specific permission.
     *
     * @param  string  $permission  The permission to check
     * @return bool True if the role has the permission
     */
    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions ?? []);
    }

    /**
     * Check if role has any of the given permissions.
     *
     * @param  array<int, string>  $permissions  Array of permissions to check
     * @return bool True if the role has any of the permissions
     */
    public function hasAnyPermission(array $permissions): bool
    {
        return ! empty(array_intersect($permissions, $this->permissions ?? []));
    }

    /**
     * Check if role has all of the given permissions.
     *
     * @param  array<int, string>  $permissions  Array of permissions to check
     * @return bool True if the role has all the permissions
     */
    public function hasAllPermissions(array $permissions): bool
    {
        return empty(array_diff($permissions, $this->permissions ?? []));
    }
}
