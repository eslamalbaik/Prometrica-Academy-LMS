<?php

namespace App\Policies;

use App\Models\Enrollment;
use App\Models\User;

class EnrollmentPolicy
{
    public function create(User $user): bool
    {
        return $user->role === 'admin';
    }

    public function view(User $user, Enrollment $enrollment): bool
    {
        return $user->role === 'admin' || $user->id === $enrollment->user_id;
    }

    public function update(User $user, Enrollment $enrollment): bool
    {
        return $user->role === 'admin' || $user->id === $enrollment->user_id;
    }

    public function delete(User $user, Enrollment $enrollment): bool
    {
        return $user->role === 'admin';
    }
}
