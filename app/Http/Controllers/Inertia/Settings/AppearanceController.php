<?php

namespace App\Http\Controllers\Inertia\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AppearanceController extends Controller
{
    public function edit(Request $request): Response
    {
        return Inertia::render('Settings/Appearance', [
            'theme' => $request->cookie('lgu_theme', 'neutral'),
            'appearance' => $request->cookie('lgu_appearance', 'system'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'theme' => ['required', 'string'],
            'appearance' => ['required', 'in:light,dark,system'],
        ]);

        return back()
            ->withCookie(cookie('lgu_theme', $data['theme'], 60 * 24 * 365))
            ->withCookie(cookie('lgu_appearance', $data['appearance'], 60 * 24 * 365))
            ->with('success', __('Appearance updated.'));
    }
}
