<?php

use App\Livewire\Pages\Settings\AppearancePage;
use App\Livewire\Pages\Settings\ProfilePage;
use App\Livewire\Pages\Settings\SecurityPage;
use App\Livewire\Pages\Settings\SidebarMenuPage;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::get('myaccount/profile', ProfilePage::class)->name('profile.edit');
    Route::redirect('settings/profile', 'myaccount/profile');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('settings/appearance', AppearancePage::class)->name('appearance.edit');

    Route::get('myaccount/security', SecurityPage::class)
        ->name('security.edit');
    Route::redirect('settings/security', 'myaccount/security');

    Route::get('administration/sidebar-menu', SidebarMenuPage::class)
        ->name('sidebar-menu.index');
    Route::redirect('settings/sidebar-menu', 'administration/sidebar-menu');
});
