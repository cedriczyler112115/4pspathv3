<?php

namespace App\Actions\Authentication;

use App\Data\GoogleIdentity;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

final class AuthenticateGoogleUser
{
    public function execute(string $code): User
    {
        try {
            $tokenResponse = Http::asForm()
                ->timeout(10)
                ->retry(2, 200, throw: false)
                ->post('https://oauth2.googleapis.com/token', [
                    'client_id' => config('services.google.client_id'),
                    'client_secret' => config('services.google.client_secret'),
                    'code' => $code,
                    'grant_type' => 'authorization_code',
                    'redirect_uri' => config('services.google.redirect'),
                ]);
        } catch (ConnectionException $exception) {
            throw new RuntimeException('Google token exchange failed.', previous: $exception);
        }

        if (! $tokenResponse->successful() || ! filled($tokenResponse->json('access_token'))) {
            throw new RuntimeException('Google token exchange failed.');
        }

        try {
            $profileResponse = Http::withToken($tokenResponse->json('access_token'))
                ->timeout(10)
                ->retry(2, 200, throw: false)
                ->get('https://www.googleapis.com/oauth2/v2/userinfo');
        } catch (ConnectionException $exception) {
            throw new RuntimeException('Google profile request failed.', previous: $exception);
        }

        if (! $profileResponse->successful()) {
            throw new RuntimeException('Google profile request failed.');
        }

        $identity = new GoogleIdentity(
            id: (string) $profileResponse->json('id'),
            email: (string) $profileResponse->json('email'),
            name: (string) ($profileResponse->json('name') ?: $profileResponse->json('given_name') ?: 'Google User'),
        );

        if (! filled($identity->id) || ! filled($identity->email)) {
            throw new RuntimeException('Google profile does not include a usable identity.');
        }

        $user = User::query()
            ->where('google_id', $identity->id)
            ->orWhere('email', $identity->email)
            ->first();

        if ($user === null) {
            return User::query()->create([
                'name' => $identity->name,
                'email' => $identity->email,
                'password' => Hash::make(Str::random(32)),
                'google_id' => $identity->id,
                'email_verified_at' => now(),
            ]);
        }

        $user->forceFill([
            'google_id' => $identity->id,
            'name' => $user->name ?: $identity->name,
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();

        return $user;
    }
}
