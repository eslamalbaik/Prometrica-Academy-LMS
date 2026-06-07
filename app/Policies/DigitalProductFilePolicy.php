<?php

namespace App\Policies;

use App\Models\DigitalProductFile;
use App\Models\ProductPurchase;
use App\Models\User;

class DigitalProductFilePolicy
{
    /**
     * Determine whether the user may download a digital product file.
     *
     * Admins always pass. Everyone else must own a completed purchase of the
     * parent product. This is the single source of entitlement truth.
     */
    public function download(User $user, DigitalProductFile $file): bool
    {
        if (($user->role ?? null) === 'admin') {
            return true;
        }

        return ProductPurchase::where('user_id', $user->id)
            ->where('digital_product_id', $file->digital_product_id)
            ->where('status', 'completed')
            ->where(function ($q) {
                // Lifetime (null) OR not yet expired.
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->exists();
    }
}
