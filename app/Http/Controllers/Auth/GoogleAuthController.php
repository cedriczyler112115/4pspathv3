<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Authentication\AuthenticateGoogleUser;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class GoogleAuthController extends Controller
{
    public function redirect(Request $request): RedirectResponse
    {
        $state = Str::random(40);

        $request->session()->put('google_oauth_state', $state);

        $query = http_build_query([
            'client_id' => config('services.google.client_id'),
            'redirect_uri' => config('services.google.redirect'),
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'prompt' => 'select_account',
            'access_type' => 'offline',
        ]);

        return redirect()->away('https://accounts.google.com/o/oauth2/v2/auth?'.$query);
    }

    public function callback(Request $request, AuthenticateGoogleUser $authenticateGoogleUser): RedirectResponse
    {
        if ($request->filled('error')) {
            return redirect()
                ->route('login')
                ->withErrors(['email' => __('Google sign-in was cancelled or denied.')]);
        }

        $state = $request->string('state')->toString();
        $sessionState = $request->session()->pull('google_oauth_state');

        if (! filled($state) || ! hash_equals((string) $sessionState, $state)) {
            return redirect()
                ->route('login')
                ->withErrors(['email' => __('Unable to verify the Google sign-in request.')]);
        }

        $code = $request->string('code')->toString();

        if ($code === '') {
            return redirect()
                ->route('login')
                ->withErrors(['email' => __('Google did not return an authorization code.')]);
        }

        try {
            $user = $authenticateGoogleUser->execute($code);
        } catch (RuntimeException $exception) {
            Log::warning('Google sign-in failed.', ['exception' => $exception]);

            return redirect()
                ->route('login')
                ->withErrors(['email' => __('Unable to complete Google sign-in at this time.')]);
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        app(\App\Services\SidebarMenuTree::class)->forgetUser($user);
        app(\App\Services\SidebarMenuTree::class)->active($user);

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
