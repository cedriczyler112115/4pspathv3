<?php

namespace App\Http\Controllers\Inertia\Settings;

use App\Concerns\PasswordValidationRules;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class SecurityController extends Controller
{
    use PasswordValidationRules;

    public function edit(Request $request): Response
    {
        return Inertia::render('Settings/Security', [
            'status' => $request->session()->get('status'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        try {
            $validated = $request->validate([
                'current_password' => $this->currentPasswordRules(),
                'password' => $this->passwordRules(),
            ]);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        $user = $request->user();
        abort_if($user === null, 403);

        $user->update([
            'password' => $validated['password'],
        ]);

        return back()->with('success', __('Password updated.'));
    }
}
