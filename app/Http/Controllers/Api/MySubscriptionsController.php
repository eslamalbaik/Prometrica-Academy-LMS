<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\ProductPurchase;
use Illuminate\Http\Request;

/**
 * Unified "My Subscriptions" feed for the student dashboard: course enrollments
 * and digital-product purchases, each with its access window, active/expired
 * status, and days remaining.
 */
class MySubscriptionsController extends Controller
{
    /** GET /api/v1/my-subscriptions */
    public function index(Request $request)
    {
        $user = $request->user();
        $now  = now();

        $courses = Enrollment::where('user_id', $user->id)
            ->with('course:id,title,thumbnail')
            ->get()
            ->map(function ($e) use ($now) {
                $active = is_null($e->expires_at) || $e->expires_at->greaterThan($now);
                return [
                    'type'           => 'course',
                    'id'             => $e->course?->id,
                    'title'          => $e->course?->title,
                    'thumbnail'      => $e->course?->thumbnail,
                    'progress'       => $e->progress,
                    'started_at'     => optional($e->enrolled_at ?? $e->created_at)->toIso8601String(),
                    'expires_at'     => optional($e->expires_at)->toIso8601String(),
                    'is_lifetime'    => is_null($e->expires_at),
                    'is_active'      => $active,
                    'days_remaining' => $e->expires_at
                        ? max(0, $now->diffInDays($e->expires_at, false))
                        : null,
                ];
            });

        $products = ProductPurchase::where('user_id', $user->id)
            ->where('status', 'completed')
            ->with('product:id,title,thumbnail_path')
            ->get()
            ->map(function ($p) use ($now) {
                $active = is_null($p->expires_at) || $p->expires_at->greaterThan($now);
                return [
                    'type'           => 'product',
                    'id'             => $p->product?->id,
                    'title'          => $p->product?->title,
                    'thumbnail'      => $p->product?->thumbnail_path,
                    'progress'       => null,
                    'started_at'     => optional($p->created_at)->toIso8601String(),
                    'expires_at'     => optional($p->expires_at)->toIso8601String(),
                    'is_lifetime'    => is_null($p->expires_at),
                    'is_active'      => $active,
                    'days_remaining' => $p->expires_at
                        ? max(0, $now->diffInDays($p->expires_at, false))
                        : null,
                ];
            });

        return response()->json([
            'subscriptions' => $courses->concat($products)->values(),
        ]);
    }
}
