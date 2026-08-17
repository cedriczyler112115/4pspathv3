<?php

use App\Livewire\Pages\Settings\AppearancePage;
use App\Livewire\Pages\Settings\ProfilePage;
use App\Livewire\Pages\Settings\SecurityPage;
use App\Livewire\Pages\Settings\SidebarMenuPage;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::get('settings/profile', ProfilePage::class)->name('profile.edit');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('settings/appearance', AppearancePage::class)->name('appearance.edit');

    Route::get('settings/security', SecurityPage::class)
        ->name('security.edit');

    Route::get('settings/sidebar-menu', SidebarMenuPage::class)
        ->name('sidebar-menu.index');
});
