<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProfileIsComplete
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user) {
            // Allow access to profile settings, logout, and auth routes
            if (
                $request->is('settings/profile*') ||
                $request->is('logout') ||
                $request->is('auth/*') ||
                $request->routeIs('settings.profile') ||
                $request->routeIs('logout') ||
                $request->routeIs('login')
            ) {
                return $next($request);
            }

            if (! $user->hasCompleteProfile()) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => __('Please completely fill out your profile details to access the application.'),
                        'missing_fields' => $user->missingProfileFields(),
                        'redirect' => route('settings.profile'),
                    ], 403);
                }

                $missing = implode(', ', $user->missingProfileFields());

                return redirect()->route('settings.profile')
                    ->with('warning', __("Please complete your profile before proceeding. Missing fields: {$missing}."));
            }
        }

        return $next($request);
    }
}
