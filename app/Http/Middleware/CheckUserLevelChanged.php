<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class CheckUserLevelChanged
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user) {
            // Fetch current database user_level_id to detect changes made by administrators
            $dbUserLevelId = (int) DB::table('users')->where('id', $user->id)->value('user_level_id');

            if ($request->session()->has('auth_user_level_id')) {
                $sessionLevelId = (int) $request->session()->get('auth_user_level_id');

                if ($sessionLevelId !== $dbUserLevelId) {
                    Auth::guard('web')->logout();

                    $request->session()->invalidate();
                    $request->session()->regenerateToken();

                    return redirect()->route('login')->with('status', __('Your user level access was modified. Please log in again.'));
                }
            } else {
                $request->session()->put('auth_user_level_id', $dbUserLevelId);
            }
        }

        return $next($request);
    }
}
