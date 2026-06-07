<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sets the application locale per-request from the frontend, so validation and
 * other framework messages come back in the page's language.
 *
 * Priority: explicit `X-Locale` header → first language in `Accept-Language`.
 * Only whitelisted locales are honoured; anything else falls back to English.
 */
class SetLocale
{
    private const SUPPORTED = ['en', 'ar'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->header('X-Locale');

        if (! $locale) {
            // e.g. "ar,en;q=0.9" → "ar"
            $accept = $request->header('Accept-Language', '');
            $locale = strtolower(substr(trim(explode(',', $accept)[0] ?? ''), 0, 2));
        }

        $locale = strtolower((string) $locale);
        if (! in_array($locale, self::SUPPORTED, true)) {
            $locale = 'en';
        }

        App::setLocale($locale);

        return $next($request);
    }
}
