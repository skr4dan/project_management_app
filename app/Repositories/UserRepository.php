<?php

namespace App\Repositories;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class UserRepository implements UserRepositoryInterface
{
    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    public function findById(int $id): ?User
    {
        return User::find($id);
    }

    public function create(array $attributes): User
    {
        return User::create($attributes);
    }

    public function update(User $user, array $attributes): bool
    {
        return $user->update($attributes);
    }

    public function delete(User $user): bool
    {
        return $user->delete() ?? false;
    }

    public function getAll(): Collection
    {
        return User::all();
    }
}
