<?php

use App\Http\Controllers\Auth\GoogleAuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Redirect root to dashboard (or login if guest)
Route::get('/', function () {
    return Auth::check() ? redirect('/inertia/dashboard') : redirect('/inertia/auth/login');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::redirect('dashboard', '/inertia/dashboard')->name('dashboard');
    Route::redirect('search', '/inertia/search')->name('search');
    Route::redirect('annualtarget', '/inertia/ipcrf/annualtarget');
    Route::redirect('ipcrf/annualtarget', '/inertia/ipcrf/annualtarget')->name('annualtarget.index');
    Route::redirect('myratings', '/inertia/ipcrf/myratings');
    Route::redirect('ipcrf/myratings', '/inertia/ipcrf/myratings')->name('myratings.index');
    Route::redirect('ipcrf/myratings/semestral-target', '/inertia/ipcrf/myratings')->name('myratings.semestral-target');
    Route::get('ipcrf/myratings/semestral-target/print-ipcrf', [\App\Http\Controllers\PrintIpcrfController::class, 'show'])->name('myratings.semestral-target.print-ipcrf');
    Route::get('ipcrf/myratings/semestral-target/print-checkpoint', [\App\Http\Controllers\PrintCheckpointController::class, 'show'])->name('myratings.semestral-target.print-checkpoint');
    Route::redirect('verification', '/inertia/verification');
    Route::redirect('ipcrf/verification', '/inertia/verification');
    Route::redirect('rpmo-management/harmonized-ipc', '/inertia/rpmo-management/harmonized-ipc')->name('harmonized-ipc.index');
    Route::redirect('harmonized-ipc', '/inertia/rpmo-management/harmonized-ipc');
    Route::redirect('libraries/harmonized-staff', '/inertia/libraries/harmonized-staff')->name('libraries.harmonized-staff.index');
    Route::redirect('harmonized-staff', '/inertia/libraries/harmonized-staff');
    Route::redirect('myaccount/profile', '/inertia/settings/profile')->name('profile.edit');
    Route::redirect('settings/profile', '/inertia/settings/profile');
    Route::redirect('settings/appearance', '/inertia/settings/appearance')->name('appearance.edit');
    Route::redirect('myaccount/security', '/inertia/settings/security')->name('security.edit');
    Route::redirect('settings/security', '/inertia/settings/security');
    Route::redirect('administration/sidebar-menu', '/inertia/settings/sidebar-menu')->name('sidebar-menu.index');
    Route::redirect('settings/sidebar-menu', '/inertia/settings/sidebar-menu');
    Route::redirect('libraries/users/users-list', '/inertia/administration/users')->name('administration.users.index');
    Route::redirect('administration/users', '/inertia/administration/users');
    Route::redirect('administration/settings', '/inertia/administration/settings')->name('administration.settings.index');
    Route::redirect('libraries/users/user-level', '/inertia/administration/user-level')->name('libraries.users.user-level.index');
});

// Logout handler
Route::match(['get', 'post'], '/logout', function (Request $request) {
    Auth::guard('web')->logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect('/inertia/auth/login');
})->name('logout');

// Google OAuth
Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect'])
    ->middleware('guest')
    ->name('google.redirect');

Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])
    ->middleware('guest')
    ->name('google.callback');

require __DIR__.'/inertia.php';
