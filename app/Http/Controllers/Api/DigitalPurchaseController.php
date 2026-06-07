<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DigitalProduct;
use App\Models\ProductPurchase;
use Illuminate\Http\Request;

/**
 * Purchase flow for digital products.
 *
 * ─── How the purchase works (current + future) ──────────────────────────────
 *  CURRENT (no gateway wired yet): this endpoint records a completed purchase
 *  immediately — an instant/free checkout that unlocks the product in the
 *  student's library. It is idempotent (re-buying returns the existing record).
 *
 *  FUTURE (real payment): replace the body with:
 *    1. Create a ProductPurchase with status = 'pending' + a payment intent.
 *    2. Redirect the student to the gateway (Stripe/PayPal/Tap/etc.).
 *    3. On the gateway webhook, look up the purchase by payment_id and flip
 *       status to 'completed'. The entitlement Policy already keys off
 *       status === 'completed', so downloads unlock automatically.
 *
 *  The rest of the system (Policy, signed downloads, My Library) needs ZERO
 *  changes when the gateway is added — only this method changes.
 */
class DigitalPurchaseController extends Controller
{
    /** POST /api/v1/digital-products/{product}/purchase */
    public function store(Request $request, DigitalProduct $product)
    {
        abort_unless($product->is_active, 404, 'Product is not available.');

        $user = $request->user();

        $expiresAt = $product->access_days
            ? now()->addDays($product->access_days)
            : null;

        // Idempotent: if there's a still-active purchase, return it.
        $existing = ProductPurchase::where('user_id', $user->id)
            ->where('digital_product_id', $product->id)
            ->where('status', 'completed')
            ->first();

        if ($existing && $existing->isActive()) {
            return response()->json([
                'message'  => 'You already own this product.',
                'purchase' => $existing,
                'owned'    => true,
            ]);
        }

        // Expired purchase → renew it; otherwise create a fresh one.
        if ($existing) {
            $existing->update(['expires_at' => $expiresAt]);
            $purchase = $existing;
        } else {
            $purchase = ProductPurchase::create([
                'user_id'            => $user->id,
                'digital_product_id' => $product->id,
                'payment_id'         => null, // set by the gateway webhook in the future
                'status'             => 'completed',
                'expires_at'         => $expiresAt,
            ]);
        }

        return response()->json([
            'message'  => 'Purchase complete. The product is now in your library.',
            'purchase' => $purchase,
            'owned'    => true,
        ], 201);
    }
}
