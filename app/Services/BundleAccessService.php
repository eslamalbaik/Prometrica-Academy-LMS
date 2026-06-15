<?php

namespace App\Services;

use App\Models\BundleCourse;
use App\Models\BundleDigitalProduct;
use App\Models\BundleQuizPermission;
use App\Models\Enrollment;
use App\Models\User;

/**
 * Resolves what a user can access when their enrollment was created via a bundle.
 *
 * Access priority:
 *  1. Direct enrollment (bundle_id IS NULL) → always full access, skip this service.
 *  2. Bundle enrollment → run this service to get effective permissions.
 *
 * Resolution order for each setting:
 *  bundle_course override (≠ inherit) → takes priority
 *  bundle default                     → fallback
 */
class BundleAccessService
{
    /**
     * Resolve all permissions for a user on a specific course.
     *
     * @return array{
     *   has_access: bool,
     *   source: string|null,
     *   bundle_id: int|null,
     *   quiz_access: string|int[],       'all' | 'none' | [quizId, ...]
     *   certificate_enabled: bool,
     *   products_access: string|int[],   'all' | 'none' | [digitalProductId, ...]
     *   expires_at: \Carbon\Carbon|null
     * }
     */
    public function resolve(int $userId, int $courseId): array
    {
        $enrollment = Enrollment::where('user_id', $userId)
            ->where('course_id', $courseId)
            ->first();

        if (! $enrollment) {
            return $this->noAccess();
        }

        // Direct purchase → unconditional full access
        if (is_null($enrollment->bundle_id)) {
            return $this->fullAccess($enrollment->expires_at);
        }

        // Bundle purchase → resolve permissions
        $bundle = $enrollment->bundle;

        if (! $bundle || ! $bundle->is_active) {
            return $this->noAccess();
        }

        // Check expiry on the enrollment itself
        if ($enrollment->expires_at && $enrollment->expires_at->isPast()) {
            return $this->noAccess();
        }

        $bundleCourse = BundleCourse::where('bundle_id', $bundle->id)
            ->where('course_id', $courseId)
            ->first();

        if (! $bundleCourse) {
            return $this->noAccess();
        }

        return [
            'has_access'          => true,
            'source'              => 'bundle',
            'bundle_id'           => $bundle->id,
            'quiz_access'         => $this->resolveQuizAccess($bundle, $bundleCourse),
            'certificate_enabled' => $this->resolveCertificateAccess($bundle, $bundleCourse),
            'products_access'     => $this->resolveProductsAccess($bundle),
            'expires_at'          => $enrollment->expires_at,
        ];
    }

    /**
     * Shortcut: can this user take a specific quiz?
     */
    public function canAccessQuiz(int $userId, int $courseId, int $quizId): bool
    {
        $perm = $this->resolve($userId, $courseId);

        if (! $perm['has_access']) return false;

        $quizAccess = $perm['quiz_access'];

        if ($quizAccess === 'all')  return true;
        if ($quizAccess === 'none') return false;

        return in_array($quizId, $quizAccess, true);
    }

    /**
     * Shortcut: can this user earn a certificate for this course?
     */
    public function canAccessCertificate(int $userId, int $courseId): bool
    {
        $perm = $this->resolve($userId, $courseId);
        return $perm['has_access'] && $perm['certificate_enabled'];
    }

    /**
     * Shortcut: does a bundle grant access to a specific digital product?
     */
    public function bundleGrantsDigitalProduct(int $userId, int $digitalProductId): bool
    {
        // Find any active bundle enrollment for this user that includes the product
        return Enrollment::where('user_id', $userId)
            ->whereNotNull('bundle_id')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->whereHas('bundle', function ($q) use ($digitalProductId) {
                $q->where('is_active', true)
                  ->where(function ($bq) use ($digitalProductId) {
                      // default_products_visibility = 'all' → all attached products included
                      $bq->where('default_products_visibility', 'all')
                         ->whereHas('digitalProducts', fn($dp) =>
                             $dp->where('digital_products.id', $digitalProductId)
                         );
                  })
                  ->orWhere(function ($bq) use ($digitalProductId) {
                      // 'selected' → only is_included = true rows
                      $bq->where('default_products_visibility', 'selected')
                         ->whereHas('digitalProducts', fn($dp) =>
                             $dp->where('digital_products.id', $digitalProductId)
                                ->wherePivot('is_included', true)
                         );
                  });
            })
            ->exists();
    }

    // ─── Private resolvers ────────────────────────────────────────────────────

    private function resolveQuizAccess(\App\Models\Bundle $bundle, BundleCourse $bundleCourse): string|array
    {
        // Course-level override takes priority over bundle default
        $visibility = ($bundleCourse->quiz_visibility !== 'inherit')
            ? $bundleCourse->quiz_visibility
            : $bundle->default_quiz_visibility;

        if ($visibility === 'all')  return 'all';
        if ($visibility === 'none') return 'none';

        // 'selected': look up which quizzes are explicitly allowed
        return BundleQuizPermission::where('bundle_id', $bundle->id)
            ->where('bundle_course_id', $bundleCourse->id)
            ->where('is_visible', true)
            ->pluck('quiz_id')
            ->toArray();
    }

    private function resolveCertificateAccess(\App\Models\Bundle $bundle, BundleCourse $bundleCourse): bool
    {
        if ($bundleCourse->certificate_enabled === 'yes') return true;
        if ($bundleCourse->certificate_enabled === 'no')  return false;

        // 'inherit' → use bundle default
        return $bundle->default_certificate_enabled;
    }

    private function resolveProductsAccess(\App\Models\Bundle $bundle): string|array
    {
        if ($bundle->default_products_visibility === 'none') return 'none';
        if ($bundle->default_products_visibility === 'all')  return 'all';

        // 'selected' → return IDs of included products
        return BundleDigitalProduct::where('bundle_id', $bundle->id)
            ->where('is_included', true)
            ->pluck('digital_product_id')
            ->toArray();
    }

    private function noAccess(): array
    {
        return [
            'has_access'          => false,
            'source'              => null,
            'bundle_id'           => null,
            'quiz_access'         => 'none',
            'certificate_enabled' => false,
            'products_access'     => 'none',
            'expires_at'          => null,
        ];
    }

    private function fullAccess(?\Carbon\Carbon $expiresAt): array
    {
        return [
            'has_access'          => true,
            'source'              => 'direct',
            'bundle_id'           => null,
            'quiz_access'         => 'all',
            'certificate_enabled' => true,
            'products_access'     => 'all',
            'expires_at'          => $expiresAt,
        ];
    }
}
