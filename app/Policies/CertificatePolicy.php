<?php

namespace App\Policies;

use App\Models\Certificate;
use App\Models\User;

class CertificatePolicy
{
    /**
     * Determine whether the user can download the certificate.
     */
    public function download(User $user, Certificate $certificate): bool
    {
        if (($user->role ?? null) === 'admin') {
            return true;
        }

        return $user->id === $certificate->user_id;
    }
}
