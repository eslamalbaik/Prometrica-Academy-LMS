<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Symfony\Component\HttpFoundation\Response;

class TenantMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenantId = $request->header('X-Tenant-ID') ?: $request->input('tenant_id') ?: 'default';
        Context::add('tenant_id', $tenantId);

        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        // Prevent tenant leakage in long-running Octane processes
        Context::forget('tenant_id');
    }
}
