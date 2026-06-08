<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Certificate extends Model
{
    protected $fillable = ['user_id', 'course_id', 'ulid', 'signature', 'issued_at'];

    protected $casts = [
        'issued_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();
        
        static::creating(function ($certificate) {
            if (empty($certificate->ulid)) {
                $certificate->ulid = (string) Str::lower(Str::ulid());
            }
            if (empty($certificate->issued_at)) {
                $certificate->issued_at = now();
            }
            
            // Calculate cryptographic signature
            if (empty($certificate->signature)) {
                $version = config('certificates.active_version', 'v1');
                $certificate->signature = static::generateSignature(
                    $version,
                    $certificate->ulid,
                    $certificate->user_id,
                    $certificate->course_id
                );
            }
        });
    }

    /**
     * Generate versioned HMAC signature for the certificate data.
     */
    public static function generateSignature(string $version, string $ulid, int $userId, int $courseId): string
    {
        $key = config("certificates.keys.{$version}");
        if (empty($key)) {
            throw new \RuntimeException("Certificate signing key version '{$version}' not configured.");
        }

        // HMAC-SHA256 of: version:ulid_userId_courseId
        $data = $version . ':' . $ulid . $userId . $courseId;
        $hmac = hash_hmac('sha256', $data, $key);

        return "{$version}:{$hmac}";
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }
}
