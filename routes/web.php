<?php

use App\Livewire\Actions\Logout;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Livewire\Pages\AnnualTargetPage;
use Illuminate\Support\Facades\Route;
use App\Livewire\Pages\Administration\UsersPage;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::get('administration/users', UsersPage::class)->name('administration.users.index');
    Route::get('annualtarget', AnnualTargetPage::class)->name('annualtarget.index');
});

Route::match(['get', 'post'], '/logout', Logout::class)
    ->middleware('auth')
    ->name('logout');

Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect'])
    ->middleware('guest')
    ->name('google.redirect');

Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])
    ->middleware('guest')
    ->name('google.callback');

require __DIR__.'/settings.php';
