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
                'subtitle' => 'Performance and Individual Performance Commitment & Review System.',
            ],
            'user' => $user ? [
                'name' => $user->name,
                'email' => $user->email,
            ] : null,
            'entryPoints' => [
                [
                    'label' => 'Annual Targets',
                    'href' => route('inertia.annualtarget'),
                    'description' => 'View and manage your annual performance target commitments.',
                ],
                [
                    'label' => 'Semestral Ratings',
                    'href' => route('inertia.myratings'),
                    'description' => 'Track your semestral performance, accomplishments, and ratings.',
                ],
                [
                    'label' => 'Verification',
                    'href' => route('inertia.verification'),
                    'description' => 'Review and verify staff semestral performance submissions.',
                ],
            ],
        ]);
    }
}
