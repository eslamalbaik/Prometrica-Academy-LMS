<?php

namespace App\Policies;

use App\Models\DigitalProductFile;
use App\Models\ProductPurchase;
use App\Models\User;
use App\Services\BundleAccessService;

class DigitalProductFilePolicy
{
    /**
     * Determine whether the user may view/stream a digital product file.
     *
     * Access is granted when any of the following is true:
     *  1. User is admin.
     *  2. User has a completed direct purchase of the parent product.
     *  3. User has an active bundle enrollment that includes the product.
     */
    public function download(User $user, DigitalProductFile $file): bool
    {
        if (($user->role ?? null) === 'admin') {
            return true;
        }

        // Direct purchase
        $directPurchase = ProductPurchase::where('user_id', $user->id)
            ->where('digital_product_id', $file->digital_product_id)
            ->where('status', 'completed')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->exists();

        if ($directPurchase) {
            return true;
        }

        // Bundle-granted access
        return app(BundleAccessService::class)
            ->bundleGrantsDigitalProduct($user->id, $file->digital_product_id);
    }
}
