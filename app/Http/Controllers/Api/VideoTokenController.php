<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use Illuminate\Http\Request;

/**
 * Issues short-lived, token-signed HLS (.m3u8) URLs for secure video delivery.
 *
 * ─── Two supported Bunny modes ───────────────────────────────────────────────
 *
 *  A) Bunny STREAM (recommended — true HLS, adaptive bitrate)
 *     The video is uploaded to a Bunny Stream *library* which transcodes it to
 *     HLS automatically. Playback URL:
 *       https://{PULL_ZONE}.b-cdn.net/{videoId}/playlist.m3u8
 *     Secured with Bunny "Token Authentication" (per-URL signed token + expiry).
 *
 *  B) Bunny CDN storage zone + "Token Authentication" enabled on the pull zone
 *     For files already on a storage zone (e.g. a raw .mp4). This protects the
 *     URL but is NOT adaptive HLS. Use only as a fallback.
 *
 *  Required .env:
 *     BUNNY_PULL_ZONE=lms-storage           # the b-cdn.net subdomain
 *     BUNNY_TOKEN_SECURITY_KEY=xxxxxxxx     # pull-zone "URL Token Auth" key
 *     BUNNY_TOKEN_TTL=300                   # seconds (default 5 min)
 *
 *  This endpoint returns the signed URL; the frontend feeds it to hls.js/Plyr.
 *  It NEVER returns a permanent URL, preventing direct-download piracy.
 */
class VideoTokenController extends Controller
{
    /** GET /api/v1/tenant/lessons/{id}/video-token */
    public function issue(Request $request, $id)
    {
        $lesson = Lesson::findOrFail($id);

        $pullZone = config('services.bunny.pull_zone', env('BUNNY_PULL_ZONE'));
        $key      = config('services.bunny.token_key', env('BUNNY_TOKEN_SECURITY_KEY'));
        $ttl      = (int) env('BUNNY_TOKEN_TTL', 300);

        if (! $pullZone || ! $key) {
            return response()->json([
                'message' => 'Bunny video delivery is not configured on the server.',
            ], 503);
        }

        // The HLS manifest path. For Bunny Stream use "{videoId}/playlist.m3u8";
        // store that path in lessons.video_url (or a dedicated column).
        $path = ltrim($lesson->video_url ?: '', '/');

        $expires = time() + $ttl;

        // Bunny Token Authentication (path-based) signature:
        //   token = base64url( md5_raw( security_key + path + expires ) )
        $hashInput = $key . '/' . $path . $expires;
        $token = $this->base64Url(md5($hashInput, true));

        $url = sprintf(
            'https://%s.b-cdn.net/%s?token=%s&expires=%d',
            $pullZone,
            $path,
            $token,
            $expires
        );

        return response()->json([
            'url'        => $url,
            'type'       => str_ends_with($path, '.m3u8') ? 'hls' : 'file',
            'expires_at' => $expires,
            'expires_in' => $ttl,
        ]);
    }

    private function base64Url(string $raw): string
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }
}
