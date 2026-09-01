<?php

namespace App\Http\Middleware;

use App\Services\SidebarMenuTree;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that will be used for Inertia responses.
     */
    public function rootView(Request $request): string
    {
        return 'inertia.app';
    }

    /**
     * Define the shared data available to every Inertia page.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'appName' => config('app.name'),
            'auth.user' => fn () => $request->user()
                ? array_merge(
                    $request->user()->only('id', 'name', 'email', 'avatar', 'user_level_id', 'can_scorecard'),
                    ['avatar_url' => $request->user()->avatar_url]
                )
                : null,
            'flash' => fn () => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
                'danger' => $request->session()->get('danger'),
                'warning' => $request->session()->get('warning'),
                'info' => $request->session()->get('info'),
                'message' => $request->session()->get('message'),
            ],
            'flash.success' => fn () => $request->session()->get('success'),
            'flash.error' => fn () => $request->session()->get('error'),
            'navigation.sidebar' => fn () => app(SidebarMenuTree::class)->active($request->user()),
        ]);
    }
}
