<?php

namespace App\Services;

use Illuminate\Http\Request;

class DeviceVerificationService
{
    public function generateFingerprint(Request $request): string
    {
        $userAgent = $request->header('User-Agent') ?? '';
        $acceptLanguage = $request->header('Accept-Language') ?? '';
        $acceptEncoding = $request->header('Accept-Encoding') ?? '';
        $deviceId = $request->header('X-Device-ID') ?? '';

        $fingerprint = hash('sha256', implode('|', [
            $userAgent,
            $acceptLanguage,
            $acceptEncoding,
            $deviceId,
        ]));

        return $fingerprint;
    }

    public function getClientIp(Request $request): string
    {
        if (!empty($request->header('X-Forwarded-For'))) {
            $ips = explode(',', $request->header('X-Forwarded-For'));
            return trim($ips[0]);
        }

        return $request->ip() ?? '0.0.0.0';
    }

    public function verifyDevice($enrollment, Request $request): array
    {
        $clientIp = $this->getClientIp($request);
        $clientFingerprint = $this->generateFingerprint($request);

        if (empty($enrollment->device_id)) {
            return [
                'verified' => true,
                'message' => null,
                'should_update' => true,
                'ip' => $clientIp,
                'fingerprint' => $clientFingerprint,
            ];
        }

        if ($enrollment->device_id === $clientFingerprint) {
            return [
                'verified' => true,
                'message' => null,
                'should_update' => false,
            ];
        }

        if (!empty($enrollment->device_ip)) {
            $ipChanged = $enrollment->device_ip !== $clientIp;

            if ($ipChanged) {
                return [
                    'verified' => false,
                    'message' => 'تم اكتشاف محاولة وصول من جهاز وموقع مختلف. يرجى التواصل مع فريق الدعم.',
                    'message_en' => 'Access detected from a different device and location. Please contact support.',
                    'code' => 'DEVICE_LOCATION_MISMATCH',
                ];
            }
        }

        return [
            'verified' => false,
            'message' => 'تم اكتشاف محاولة وصول من جهاز مختلف. يرجى التواصل مع الدعم عبر الواتساب أو البريد الإلكتروني.',
            'message_en' => 'Access detected from a different device. Please contact support via WhatsApp or email.',
            'code' => 'DEVICE_MISMATCH',
        ];
    }

    public function formatErrorResponse(array $verification): array
    {
        return [
            'locked' => true,
            'device_locked' => true,
            'message' => $verification['message'],
            'message_en' => $verification['message_en'] ?? $verification['message'],
            'code' => $verification['code'] ?? 'DEVICE_LOCKED',
            'support_channels' => [
                'whatsapp' => 'https://wa.me/966501234567',
                'email' => 'support@pharmacademy.com',
            ],
        ];
    }
}
