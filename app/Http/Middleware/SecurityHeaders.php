<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        URL::forceRootUrl($request->getSchemeAndHttpHost());
        if ($request->header('x-forwarded-proto') === 'https' || $request->isSecure()) {
            URL::forceScheme('https');
        }

        $response = $next($request);    

        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        if (app()->environment('local')) {
            $response->headers->set(
                'Content-Security-Policy',
                "default-src 'self'; " .
                "script-src 'self' 'unsafe-inline' 'unsafe-eval' http://localhost:5173; " .
                "style-src 'self' 'unsafe-inline' http://localhost:5173 https://fonts.googleapis.com; " .
                "font-src 'self' https://fonts.gstatic.com http://localhost:5173; " .
                "img-src 'self' data: blob:; " .
                "connect-src 'self' http://localhost:5173 ws://localhost:5173; " .
                "frame-ancestors 'self'; " .
                "form-action 'self';"
            );
        } else {
            $response->headers->set(
                'Content-Security-Policy',
                "default-src 'self'; " .
                "script-src 'self'; " .
                "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; " .
                "font-src 'self' https://fonts.gstatic.com; " .
                "img-src 'self' data:; " .
                "frame-ancestors 'self'; " .
                "form-action 'self';"
            );
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
