<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\UserRepositoryInterface;
use App\Models\User;

class EloquentUserRepository implements UserRepositoryInterface
{
    // Get user by ID
    public function findById(int $id): ?User
    {
        return User::find($id);
    }

    // Get user by email
    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    // create new user
    public function create(array $data): User
    {
        return User::create($data);
    }
}
