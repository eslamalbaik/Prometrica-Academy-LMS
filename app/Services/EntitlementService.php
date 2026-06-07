<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CoursePackage;
use App\Models\Enrollment;
use App\Models\User;

/**
 * Central entitlement (feature-gating) resolver — the single source of truth the
 * controllers consult before serving a gated resource (quizzes, files,
 * certificates).
 *
 * Backward-compatibility rule: if the student is enrolled but has NO package,
 * OR the course defines no packages at all, they receive FULL access. This keeps
 * every pre-existing course/enrollment working unchanged. Tiering only restricts
 * access once an admin attaches packages AND the student buys a specific tier.
 */
class EntitlementService
{
    public const FEATURES = ['has_quizzes', 'has_files', 'has_certificate'];

    /**
     * Resolve the effective entitlements for a user on a course.
     *
     * @return array{has_quizzes:bool,has_files:bool,has_certificate:bool,tier:?string,is_full:bool,enrolled:bool}
     */
    public function resolve(User $user, int $courseId): array
    {
        $full = [
            'has_quizzes'     => true,
            'has_files'       => true,
            'has_certificate' => true,
            'tier'            => null,
            'is_full'         => true,
            'enrolled'        => true,
        ];

        // Admins and the course instructor always get everything.
        if (($user->role ?? null) === 'admin'
            || Course::where('id', $courseId)->where('instructor_id', $user->id)->exists()) {
            return ['tier' => 'Admin'] + $full;
        }

        $enrollment = Enrollment::where('user_id', $user->id)
            ->where('course_id', $courseId)
            ->first();

        if (! $enrollment) {
            return [
                'has_quizzes'     => false,
                'has_files'       => false,
                'has_certificate' => false,
                'tier'            => null,
                'is_full'         => false,
                'enrolled'        => false,
            ];
        }

        // Enrolled without a package, or course has no packages → full (legacy).
        $courseHasPackages = CoursePackage::where('course_id', $courseId)->exists();
        if (! $enrollment->course_package_id || ! $courseHasPackages) {
            return $full;
        }

        $package = CoursePackage::find($enrollment->course_package_id);
        if (! $package) {
            return $full;
        }

        $flags = $package->entitlementFlags();

        return [
            'has_quizzes'     => (bool) $flags['has_quizzes'],
            'has_files'       => (bool) $flags['has_files'],
            'has_certificate' => (bool) $flags['has_certificate'],
            'tier'            => $package->name,
            'is_full'         => false,
            'enrolled'        => true,
        ];
    }

    public function allows(User $user, int $courseId, string $feature): bool
    {
        return (bool) ($this->resolve($user, $courseId)[$feature] ?? false);
    }

    /**
     * Name of the cheapest package that unlocks the given feature — used to tell
     * the frontend which tier the student should upgrade to.
     */
    public function requiredTier(int $courseId, string $feature): ?string
    {
        $package = CoursePackage::where('course_id', $courseId)
            ->orderBy('price')
            ->get()
            ->first(fn (CoursePackage $p) => ($p->entitlementFlags()[$feature] ?? false) === true);

        return $package?->name;
    }
}
