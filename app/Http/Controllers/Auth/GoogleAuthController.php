<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

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

    public function callback(Request $request): RedirectResponse
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

        $tokenResponse = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => config('services.google.redirect'),
        ]);

        if (! $tokenResponse->successful()) {
            return redirect()
                ->route('login')
                ->withErrors(['email' => __('Unable to complete Google sign-in at this time.')]);
        }

        $accessToken = $tokenResponse->json('access_token');

        if (! filled($accessToken)) {
            return redirect()
                ->route('login')
                ->withErrors(['email' => __('Google did not return an access token.')]);
        }

        $profileResponse = Http::withToken($accessToken)->get('https://www.googleapis.com/oauth2/v2/userinfo');

        if (! $profileResponse->successful()) {
            return redirect()
                ->route('login')
                ->withErrors(['email' => __('Unable to read your Google profile.')]);
        }

        $googleId = $profileResponse->json('id');
        $email = $profileResponse->json('email');
        $name = $profileResponse->json('name') ?: $profileResponse->json('given_name') ?: 'Google User';

        if (! filled($email)) {
            return redirect()
                ->route('login')
                ->withErrors(['email' => __('Your Google account does not have an email address.')]);
        }

        $user = User::query()
            ->where('google_id', $googleId)
            ->orWhere('email', $email)
            ->first();

        if ($user === null) {
            $user = User::query()->create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make(Str::random(32)),
                'google_id' => $googleId,
                'email_verified_at' => now(),
            ]);
        } else {
            $user->forceFill([
                'google_id' => $googleId,
                'name' => $user->name ?: $name,
                'email_verified_at' => $user->email_verified_at ?? now(),
            ])->save();
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
