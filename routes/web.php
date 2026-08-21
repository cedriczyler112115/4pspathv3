<?php

use App\Http\Controllers\Auth\GoogleAuthController;
use App\Livewire\Actions\Logout;
use App\Livewire\Pages\Administration\ApplicationSettingsPage;
use App\Livewire\Pages\Administration\UsersPage;
use App\Livewire\Pages\AnnualTargetPage;
use App\Livewire\Pages\Libraries\Users\UserLevelPage;
use App\Livewire\Pages\SearchPage;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::get('search', SearchPage::class)->name('search');
    Route::middleware('can:access-administration')->group(function (): void {
        Route::get('libraries/users/users-list', UsersPage::class)->name('administration.users.index');
        Route::redirect('administration/users', 'libraries/users/users-list');
        Route::get('administration/settings', ApplicationSettingsPage::class)->name('administration.settings.index');
        Route::get('libraries/users/user-level', UserLevelPage::class)->name('libraries.users.user-level.index');
    });
    Route::get('ipcrf/annualtarget', AnnualTargetPage::class)->name('annualtarget.index');
    Route::redirect('annualtarget', 'ipcrf/annualtarget');
    Route::get('rpmo-management/harmonized-ipc', \App\Livewire\Pages\RpmoManagement\HarmonizedIpcPage::class)->name('harmonized-ipc.index');
    Route::redirect('harmonized-ipc', 'rpmo-management/harmonized-ipc');
    Route::get('libraries/harmonized-staff', \App\Livewire\Pages\Libraries\HarmonizedStaffPage::class)->name('libraries.harmonized-staff.index');
    Route::redirect('harmonized-staff', 'libraries/harmonized-staff');
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
