<?php

use App\Http\Controllers\Inertia\DashboardController;
use App\Http\Controllers\Inertia\Auth\LoginController;
use App\Http\Controllers\Inertia\Administration\ApplicationSettingsController;
use App\Http\Controllers\Inertia\Administration\UserLevelController;
use App\Http\Controllers\Inertia\Libraries\HarmonizedStaffController;
use App\Http\Controllers\Inertia\RpmoManagement\HarmonizedIpcController;
use App\Http\Controllers\Inertia\Settings\SidebarMenuController;
use App\Http\Controllers\Inertia\Settings\AppearanceController;
use App\Http\Controllers\Inertia\Settings\ProfileController;
use App\Http\Controllers\Inertia\Settings\SecurityController;
use App\Http\Controllers\Inertia\Administration\UsersController;
use App\Http\Controllers\Inertia\AnnualTargetController;
use App\Http\Controllers\Inertia\SearchController;
use App\Http\Controllers\Inertia\RatingsController;
use Illuminate\Support\Facades\Route;

if (class_exists(\Inertia\Inertia::class)) {
    Route::prefix('inertia')->group(function (): void {
        Route::middleware('guest')->group(function (): void {
            Route::redirect('/login', '/inertia/auth/login');
            Route::get('/auth/login', LoginController::class)->name('inertia.login');
        });

        Route::middleware(['auth', 'verified'])->group(function (): void {
            Route::redirect('/', '/inertia/dashboard');
            Route::get('/dashboard', DashboardController::class)->name('inertia.dashboard');
            Route::get('/search', SearchController::class)->name('inertia.search');
            Route::get('/ipcrf/annualtarget', [AnnualTargetController::class, 'index'])->name('inertia.annualtarget');
            Route::redirect('/annualtarget', '/inertia/ipcrf/annualtarget');
            Route::post('/ipcrf/annualtarget', [AnnualTargetController::class, 'store'])->name('inertia.annualtarget.store');
            Route::post('/ipcrf/annualtarget/reorder', [AnnualTargetController::class, 'reorder'])->name('inertia.annualtarget.reorder');
            Route::post('/ipcrf/annualtarget/{indicatorId}/sub-target', [AnnualTargetController::class, 'storeSubTarget'])->name('inertia.annualtarget.sub-target.store');
            Route::patch('/ipcrf/annualtarget/{indicatorId}', [AnnualTargetController::class, 'update'])->name('inertia.annualtarget.update');
            Route::delete('/ipcrf/annualtarget/{indicatorId}', [AnnualTargetController::class, 'destroyIndicator'])->name('inertia.annualtarget.destroy');
            Route::delete('/ipcrf/annualtarget-item/{itemId}', [AnnualTargetController::class, 'destroyItem'])->name('inertia.annualtarget-item.destroy');
            Route::post('/ipcrf/annualtarget/lock', [AnnualTargetController::class, 'lock'])->name('inertia.annualtarget.lock');
            Route::post('/ipcrf/annualtarget/unlock', [AnnualTargetController::class, 'unlock'])->name('inertia.annualtarget.unlock');
            Route::get('/ipcrf/annualtarget/copy-data', [AnnualTargetController::class, 'getCopyData'])->name('inertia.annualtarget.copy-data');
            Route::post('/ipcrf/annualtarget/copy-staff', [AnnualTargetController::class, 'copyStaffTargetGroup'])->name('inertia.annualtarget.copy-staff');
            Route::post('/ipcrf/annualtarget/copy-harmonized', [AnnualTargetController::class, 'copyHarmonizedTargetGroup'])->name('inertia.annualtarget.copy-harmonized');
            Route::post('/ipcrf/annualtarget/copy-all-staff', [AnnualTargetController::class, 'copyAllStaffTargetGroups'])->name('inertia.annualtarget.copy-all-staff');
            Route::post('/ipcrf/annualtarget/copy-all-harmonized', [AnnualTargetController::class, 'copyAllHarmonizedTargetGroups'])->name('inertia.annualtarget.copy-all-harmonized');
            Route::redirect('/myratings', '/inertia/ipcrf/myratings');
            Route::get('/ipcrf/myratings', [RatingsController::class, 'index'])->name('inertia.myratings');
            Route::get('/ipcrf/myratings/{ratingId}/sem-target', [RatingsController::class, 'show'])->name('inertia.myratings.sem-target');
            Route::patch('/ipcrf/myratings/{ratingId}/accomplishment/{itemId}', [RatingsController::class, 'updateAccomplishment'])->name('inertia.myratings.accomplishment.update');
            Route::post('/ipcrf/myratings/{ratingId}/target', [RatingsController::class, 'storeTarget'])->name('inertia.myratings.target.store');
            Route::put('/ipcrf/myratings/{ratingId}/target/{targetId}', [RatingsController::class, 'updateTargetGroup'])->name('inertia.myratings.target.update');
            Route::post('/ipcrf/myratings/{ratingId}/target/{targetId}/subtarget', [RatingsController::class, 'storeSubTarget'])->name('inertia.myratings.subtarget.store');
            Route::delete('/ipcrf/myratings/{ratingId}/target/{targetId}', [RatingsController::class, 'destroyTarget'])->name('inertia.myratings.target.destroy');
            Route::get('/ipcrf/myratings/{ratingId}/target/{targetId}/history', [RatingsController::class, 'getEditHistory'])->name('inertia.myratings.target.history');
            Route::delete('/ipcrf/myratings/{ratingId}/target/{targetId}/history', [RatingsController::class, 'discardEditHistory'])->name('inertia.myratings.target.history.discard');
            Route::delete('/ipcrf/myratings/{ratingId}/subtarget/{itemId}', [RatingsController::class, 'destroySubTarget'])->name('inertia.myratings.subtarget.destroy');
            Route::post('/ipcrf/myratings/{ratingId}/toggle-status', [RatingsController::class, 'toggleStatus'])->name('inertia.myratings.toggle-status');
            Route::post('/ipcrf/myratings/{ratingId}/areas-improvement', [RatingsController::class, 'storeAreaOfImprovement'])->name('inertia.myratings.areas-improvement.store');
            Route::delete('/ipcrf/myratings/{ratingId}/areas-improvement/{id}', [RatingsController::class, 'destroyAreaOfImprovement'])->name('inertia.myratings.areas-improvement.destroy');
            Route::post('/ipcrf/myratings/{ratingId}/documentation', [RatingsController::class, 'storeDocumentation'])->name('inertia.myratings.documentation.store');
            Route::delete('/ipcrf/myratings/{ratingId}/documentation', [RatingsController::class, 'destroyDocumentation'])->name('inertia.myratings.documentation.destroy');
            Route::post('/ipcrf/myratings/{ratingId}/target/reorder', [RatingsController::class, 'reorderTargets'])->name('inertia.myratings.target.reorder');
            Route::post('/ipcrf/myratings/{ratingId}/copy-movs', [RatingsController::class, 'copyStaffMovs'])->name('inertia.myratings.copy-movs');
            Route::delete('/ipcrf/myratings/{ratingId}', [RatingsController::class, 'destroy'])->name('inertia.myratings.destroy');
            Route::get('/settings/profile', [ProfileController::class, 'edit'])->name('inertia.settings.profile');
            Route::patch('/settings/profile', [ProfileController::class, 'update']);
            Route::get('/settings/security', [SecurityController::class, 'edit'])->name('inertia.settings.security');
            Route::patch('/settings/security', [SecurityController::class, 'update']);
            Route::get('/settings/appearance', [AppearanceController::class, 'edit'])->name('inertia.settings.appearance');
            Route::patch('/settings/appearance', [AppearanceController::class, 'update']);
            Route::get('/settings/sidebar-menu', [SidebarMenuController::class, 'index'])->name('inertia.settings.sidebar-menu');
            Route::post('/settings/sidebar-menu', [SidebarMenuController::class, 'store'])->name('inertia.settings.sidebar-menu.store');
            Route::patch('/settings/sidebar-menu/{id}', [SidebarMenuController::class, 'update'])->name('inertia.settings.sidebar-menu.update');
            Route::delete('/settings/sidebar-menu/{id}', [SidebarMenuController::class, 'destroy'])->name('inertia.settings.sidebar-menu.destroy');
            Route::get('/administration/settings', [ApplicationSettingsController::class, 'edit'])->name('inertia.administration.settings');
            Route::patch('/administration/settings', [ApplicationSettingsController::class, 'update']);
            Route::get('/administration/user-level', [UserLevelController::class, 'index'])->name('inertia.administration.user-level');
            Route::post('/administration/user-level', [UserLevelController::class, 'save'])->name('inertia.administration.user-level.save');
            Route::delete('/administration/user-level/{levelId}', [UserLevelController::class, 'destroy'])->name('inertia.administration.user-level.destroy');
            Route::patch('/administration/user-level/menu-access', [UserLevelController::class, 'saveMenuAccess'])->name('inertia.administration.user-level.menu-access');
            Route::get('/libraries/harmonized-staff', [HarmonizedStaffController::class, 'index'])->name('inertia.libraries.harmonized-staff');
            Route::post('/libraries/harmonized-staff', [HarmonizedStaffController::class, 'store'])->name('inertia.libraries.harmonized-staff.store');
            Route::patch('/libraries/harmonized-staff/{id}', [HarmonizedStaffController::class, 'update'])->name('inertia.libraries.harmonized-staff.update');
            Route::delete('/libraries/harmonized-staff/{id}', [HarmonizedStaffController::class, 'destroy'])->name('inertia.libraries.harmonized-staff.destroy');
            Route::get('/rpmo-management/harmonized-ipc', [HarmonizedIpcController::class, 'index'])->name('inertia.rpmo-management.harmonized-ipc');
            Route::post('/rpmo-management/harmonized-ipc', [HarmonizedIpcController::class, 'store'])->name('inertia.rpmo-management.harmonized-ipc.store');
            Route::post('/rpmo-management/harmonized-ipc/reorder', [HarmonizedIpcController::class, 'reorder'])->name('inertia.rpmo-management.harmonized-ipc.reorder');
            Route::post('/rpmo-management/harmonized-ipc/{indicatorId}/sub-target', [HarmonizedIpcController::class, 'storeSubTarget'])->name('inertia.rpmo-management.harmonized-ipc.sub-target.store');
            Route::patch('/rpmo-management/harmonized-ipc/{indicatorId}/{rowId}', [HarmonizedIpcController::class, 'update'])->name('inertia.rpmo-management.harmonized-ipc.update');
            Route::delete('/rpmo-management/harmonized-ipc/{indicatorId}', [HarmonizedIpcController::class, 'destroy'])->name('inertia.rpmo-management.harmonized-ipc.destroy');
            Route::delete('/rpmo-management/harmonized-ipc-item/{itemId}', [HarmonizedIpcController::class, 'destroyItem'])->name('inertia.rpmo-management.harmonized-ipc-item.destroy');

            Route::middleware('can:access-administration')->group(function (): void {
                Route::get('/administration/users', [UsersController::class, 'index'])->name('inertia.administration.users');
                Route::patch('/administration/users/{userId}', [UsersController::class, 'update'])->name('inertia.administration.users.update');
                Route::delete('/administration/users/{userId}', [UsersController::class, 'destroy'])->name('inertia.administration.users.destroy');
                Route::patch('/administration/users/{userId}/toggle-status', [UsersController::class, 'toggleStatus'])->name('inertia.administration.users.toggle-status');
            });
        });
    });
}
