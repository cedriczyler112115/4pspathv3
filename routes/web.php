<?php

use App\Http\Controllers\Auth\GoogleAuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Logout handler
Route::post('/logout', function (Request $request) {
    $user = Auth::guard('web')->user();
    if ($user instanceof \App\Models\User) {
        app(\App\Services\SidebarMenuTree::class)->forgetUser($user);
    } else {
        app(\App\Services\SidebarMenuTree::class)->forget();
    }

    Auth::guard('web')->logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect('/login');
})->name('logout');

Route::get('/logout', function (Request $request) {
    $user = Auth::guard('web')->user();
    if ($user instanceof \App\Models\User) {
        app(\App\Services\SidebarMenuTree::class)->forgetUser($user);
    } else {
        app(\App\Services\SidebarMenuTree::class)->forget();
    }

    Auth::guard('web')->logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect('/login');
});

// Google OAuth
Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect'])
    ->middleware('guest')
    ->name('google.redirect');

Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])
    ->middleware('guest')
    ->name('google.callback');

require __DIR__.'/inertia.php';
