<?php

namespace App\Http\Controllers\Inertia;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Dashboard', [
            'appName' => config('app.name'),
            'hero' => [
                'title' => config('app.name'),
                'subtitle' => 'A separate Inertia surface that can live beside the current Livewire app.',
            ],
            'user' => $user ? [
                'name' => $user->name,
                'email' => $user->email,
            ] : null,
            'entryPoints' => [
                [
                    'label' => 'Livewire dashboard',
                    'href' => route('dashboard'),
                    'description' => 'Keep the current Livewire experience available while you port pages gradually.',
                ],
                [
                    'label' => 'Inertia login',
                    'href' => route('inertia.login'),
                    'description' => 'Use the new React auth surface without touching the existing Livewire auth views.',
                ],
                [
                    'label' => 'Livewire semestral targets',
                    'href' => route('myratings.semestral-target'),
                    'description' => 'A good candidate to leave in Livewire until the Inertia side is stable.',
                ],
            ],
        ]);
    }
}
