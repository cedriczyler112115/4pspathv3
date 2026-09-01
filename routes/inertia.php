<?php

use App\Http\Controllers\Inertia\DashboardController;
use App\Http\Controllers\Inertia\Auth\LoginController;
use App\Http\Controllers\Inertia\Administration\ApplicationSettingsController;
use App\Http\Controllers\Inertia\Administration\UserLevelController;
use App\Http\Controllers\Inertia\Libraries\HarmonizedStaffController;
use App\Http\Controllers\Inertia\RpmoManagement\HarmonizedIpcController;
use App\Http\Controllers\Inertia\RpmoManagement\PlsScorecardController;
use App\Http\Controllers\Inertia\Settings\SidebarMenuController;
use App\Http\Controllers\Inertia\Settings\AppearanceController;
use App\Http\Controllers\Inertia\Settings\ProfileController;
use App\Http\Controllers\Inertia\Settings\MyStaffController;
use App\Http\Controllers\Inertia\Settings\SecurityController;
use App\Http\Controllers\Inertia\Administration\UsersController;
use App\Http\Controllers\Inertia\AnnualTargetController;
use App\Http\Controllers\Inertia\SearchController;
use App\Http\Controllers\Inertia\RatingsController;
use App\Http\Controllers\Inertia\VerificationController;
use App\Http\Controllers\Inertia\Verification\SemestralVerificationController;
use Illuminate\Support\Facades\Route;

if (class_exists(\Inertia\Inertia::class)) {
    Route::middleware('guest')->group(function (): void {
        Route::get('/login', LoginController::class)->name('login');
        Route::get('/auth/login', LoginController::class);
    });

    Route::middleware(['auth', 'verified'])->group(function (): void {
        Route::get('/', function () {
            return redirect('/dashboard');
        })->name('home');

        Route::get('/dashboard', DashboardController::class)->name('dashboard');
        Route::get('/search', SearchController::class)->name('search');

        // Annual Targets
        Route::get('/ipcrf/annualtarget', [AnnualTargetController::class, 'index'])->name('annualtarget.index');
        Route::redirect('/annualtarget', '/ipcrf/annualtarget');
        Route::post('/ipcrf/annualtarget', [AnnualTargetController::class, 'store'])->name('annualtarget.store');
        Route::post('/ipcrf/annualtarget/reorder', [AnnualTargetController::class, 'reorder'])->name('annualtarget.reorder');
        Route::post('/ipcrf/annualtarget/{indicatorId}/sub-target', [AnnualTargetController::class, 'storeSubTarget'])->name('annualtarget.sub-target.store');
        Route::patch('/ipcrf/annualtarget/{indicatorId}', [AnnualTargetController::class, 'update'])->name('annualtarget.update');
        Route::delete('/ipcrf/annualtarget/{indicatorId}', [AnnualTargetController::class, 'destroyIndicator'])->name('annualtarget.destroy');
        Route::delete('/ipcrf/annualtarget-item/{itemId}', [AnnualTargetController::class, 'destroyItem'])->name('annualtarget-item.destroy');
        Route::post('/ipcrf/annualtarget/lock', [AnnualTargetController::class, 'lock'])->name('annualtarget.lock');
        Route::post('/ipcrf/annualtarget/unlock', [AnnualTargetController::class, 'unlock'])->name('annualtarget.unlock');
        Route::get('/ipcrf/annualtarget/copy-data', [AnnualTargetController::class, 'getCopyData'])->name('annualtarget.copy-data');
        Route::post('/ipcrf/annualtarget/copy-staff', [AnnualTargetController::class, 'copyStaffTargetGroup'])->name('annualtarget.copy-staff');
        Route::post('/ipcrf/annualtarget/copy-harmonized', [AnnualTargetController::class, 'copyHarmonizedTargetGroup'])->name('annualtarget.copy-harmonized');
        Route::post('/ipcrf/annualtarget/copy-all-staff', [AnnualTargetController::class, 'copyAllStaffTargetGroups'])->name('annualtarget.copy-all-staff');
        Route::post('/ipcrf/annualtarget/copy-all-harmonized', [AnnualTargetController::class, 'copyAllHarmonizedTargetGroups'])->name('annualtarget.copy-all-harmonized');

        // Verification
        Route::get('/verification', [VerificationController::class, 'index'])->name('verification');
        Route::get('/verification/semestral-verification', [SemestralVerificationController::class, 'index'])->name('verification.semestral-verification');
        Route::get('/verification/{ratingId}/semestral-verification', [SemestralVerificationController::class, 'show'])->name('verification.semestral-verification.show');

        // Semestral Ratings
        Route::redirect('/myratings', '/ipcrf/myratings');
        Route::get('/ipcrf/myratings', [RatingsController::class, 'index'])->name('myratings.index');
        Route::get('/ipcrf/myratings/semestral-target/print-ipcrf', [\App\Http\Controllers\PrintIpcrfController::class, 'show'])->name('myratings.semestral-target.print-ipcrf');
        Route::get('/ipcrf/myratings/semestral-target/print-checkpoint', [\App\Http\Controllers\PrintCheckpointController::class, 'show'])->name('myratings.semestral-target.print-checkpoint');
        Route::get('/ipcrf/myratings/{ratingId}/sem-target', [RatingsController::class, 'show'])->name('myratings.sem-target');
        Route::patch('/ipcrf/myratings/{ratingId}/accomplishment/{itemId}', [RatingsController::class, 'updateAccomplishment'])->name('myratings.accomplishment.update');
        Route::post('/ipcrf/myratings/{ratingId}/target', [RatingsController::class, 'storeTarget'])->name('myratings.target.store');
        Route::put('/ipcrf/myratings/{ratingId}/target/{targetId}', [RatingsController::class, 'updateTargetGroup'])->name('myratings.target.update');
        Route::post('/ipcrf/myratings/{ratingId}/target/{targetId}/subtarget', [RatingsController::class, 'storeSubTarget'])->name('myratings.subtarget.store');
        Route::delete('/ipcrf/myratings/{ratingId}/target/{targetId}', [RatingsController::class, 'destroyTarget'])->name('myratings.target.destroy');
        Route::get('/ipcrf/myratings/{ratingId}/target/{targetId}/history', [RatingsController::class, 'getEditHistory'])->name('myratings.target.history');
        Route::delete('/ipcrf/myratings/{ratingId}/target/{targetId}/history', [RatingsController::class, 'discardEditHistory'])->name('myratings.target.history.discard');
        Route::post('/ipcrf/myratings/{ratingId}/target/{targetId}/restore', [RatingsController::class, 'restoreDeletedTarget'])->name('myratings.target.restore');
        Route::delete('/ipcrf/myratings/{ratingId}/subtarget/{itemId}', [RatingsController::class, 'destroySubTarget'])->name('myratings.subtarget.destroy');
        Route::post('/ipcrf/myratings/{ratingId}/toggle-status', [RatingsController::class, 'toggleStatus'])->name('myratings.toggle-status');
        Route::post('/ipcrf/myratings/{ratingId}/areas-improvement', [RatingsController::class, 'storeAreaOfImprovement'])->name('myratings.areas-improvement.store');
        Route::put('/ipcrf/myratings/{ratingId}/areas-improvement/{id}', [RatingsController::class, 'updateAreaOfImprovement'])->name('myratings.areas-improvement.update');
        Route::delete('/ipcrf/myratings/{ratingId}/areas-improvement/{id}', [RatingsController::class, 'destroyAreaOfImprovement'])->name('myratings.areas-improvement.destroy');
        Route::post('/ipcrf/myratings/{ratingId}/feedback', [RatingsController::class, 'updateFeedback'])->name('myratings.feedback.update');
        Route::post('/ipcrf/myratings/{ratingId}/documentation', [RatingsController::class, 'storeDocumentation'])->name('myratings.documentation.store');
        Route::delete('/ipcrf/myratings/{ratingId}/documentation', [RatingsController::class, 'destroyDocumentation'])->name('myratings.documentation.destroy');
        Route::post('/ipcrf/myratings/{ratingId}/target/reorder', [RatingsController::class, 'reorderTargets'])->name('myratings.target.reorder');
        Route::get('/ipcrf/myratings/{ratingId}/attachments/{itemId}', [RatingsController::class, 'getItemAttachments'])->name('myratings.attachments.get');
        Route::post('/ipcrf/myratings/{ratingId}/attachments/{itemId}', [RatingsController::class, 'uploadItemAttachments'])->name('myratings.attachments.upload');
        Route::post('/ipcrf/myratings/{ratingId}/attachments/{itemId}/delete', [RatingsController::class, 'deleteItemAttachment'])->name('myratings.attachments.delete');
        Route::get('/ipcrf/myratings/{ratingId}/staff-movs/{itemId}', [RatingsController::class, 'getStaffMovSources'])->name('myratings.staff-movs.get');
        Route::post('/ipcrf/myratings/{ratingId}/copy-staff-movs/{destItemId}', [RatingsController::class, 'copyStaffMovsToItem'])->name('myratings.copy-staff-movs');
        Route::post('/ipcrf/myratings/{ratingId}/copy-movs', [RatingsController::class, 'copyStaffMovs'])->name('myratings.copy-movs');
        Route::delete('/ipcrf/myratings/{ratingId}', [RatingsController::class, 'destroy'])->name('myratings.destroy');

        // Settings
        Route::get('/settings/profile', [ProfileController::class, 'edit'])->name('settings.profile');
        Route::match(['patch', 'post'], '/settings/profile', [ProfileController::class, 'update']);
        Route::redirect('/myaccount/profile', '/settings/profile')->name('profile.edit');
        Route::get('/settings/mystaff', [MyStaffController::class, 'index'])->name('settings.mystaff');
        Route::get('/settings/security', [SecurityController::class, 'edit'])->name('settings.security');
        Route::patch('/settings/security', [SecurityController::class, 'update']);
        Route::redirect('/myaccount/security', '/settings/security')->name('security.edit');
        Route::get('/settings/appearance', [AppearanceController::class, 'edit'])->name('settings.appearance');
        Route::patch('/settings/appearance', [AppearanceController::class, 'update']);
        Route::get('/settings/sidebar-menu', [SidebarMenuController::class, 'index'])->name('settings.sidebar-menu');
        Route::post('/settings/sidebar-menu', [SidebarMenuController::class, 'store'])->name('settings.sidebar-menu.store');
        Route::patch('/settings/sidebar-menu/{id}', [SidebarMenuController::class, 'update'])->name('settings.sidebar-menu.update');
        Route::delete('/settings/sidebar-menu/{id}', [SidebarMenuController::class, 'destroy'])->name('settings.sidebar-menu.destroy');
        Route::redirect('/administration/sidebar-menu', '/settings/sidebar-menu')->name('sidebar-menu.index');

        // Administration & Libraries
        Route::get('/administration/settings', [ApplicationSettingsController::class, 'edit'])->name('administration.settings');
        Route::patch('/administration/settings', [ApplicationSettingsController::class, 'update']);
        Route::get('/administration/user-level', [UserLevelController::class, 'index'])->name('administration.user-level');
        Route::post('/administration/user-level', [UserLevelController::class, 'save'])->name('administration.user-level.save');
        Route::delete('/administration/user-level/{levelId}', [UserLevelController::class, 'destroy'])->name('administration.user-level.destroy');
        Route::patch('/administration/user-level/menu-access', [UserLevelController::class, 'saveMenuAccess'])->name('administration.user-level.menu-access');
        Route::redirect('/libraries/users/user-level', '/administration/user-level')->name('libraries.users.user-level.index');

        Route::get('/libraries/harmonized-staff', [HarmonizedStaffController::class, 'index'])->name('libraries.harmonized-staff');
        Route::post('/libraries/harmonized-staff', [HarmonizedStaffController::class, 'store'])->name('libraries.harmonized-staff.store');
        Route::patch('/libraries/harmonized-staff/{id}', [HarmonizedStaffController::class, 'update'])->name('libraries.harmonized-staff.update');
        Route::delete('/libraries/harmonized-staff/{id}', [HarmonizedStaffController::class, 'destroy'])->name('libraries.harmonized-staff.destroy');
        Route::redirect('/harmonized-staff', '/libraries/harmonized-staff');

        Route::get('/rpmo-management/harmonized-ipc', [HarmonizedIpcController::class, 'index'])->name('rpmo-management.harmonized-ipc');
        Route::post('/rpmo-management/harmonized-ipc', [HarmonizedIpcController::class, 'store'])->name('rpmo-management.harmonized-ipc.store');
        Route::post('/rpmo-management/harmonized-ipc/reorder', [HarmonizedIpcController::class, 'reorder'])->name('rpmo-management.harmonized-ipc.reorder');
        Route::post('/rpmo-management/harmonized-ipc/{indicatorId}/sub-target', [HarmonizedIpcController::class, 'storeSubTarget'])->name('rpmo-management.harmonized-ipc.sub-target.store');
        Route::patch('/rpmo-management/harmonized-ipc/{indicatorId}/{rowId}', [HarmonizedIpcController::class, 'update'])->name('rpmo-management.harmonized-ipc.update');
        Route::delete('/rpmo-management/harmonized-ipc/{indicatorId}', [HarmonizedIpcController::class, 'destroy'])->name('rpmo-management.harmonized-ipc.destroy');
        Route::delete('/rpmo-management/harmonized-ipc-item/{itemId}', [HarmonizedIpcController::class, 'destroyItem'])->name('rpmo-management.harmonized-ipc-item.destroy');
        Route::redirect('/harmonized-ipc', '/rpmo-management/harmonized-ipc');

        Route::get('/rpmo-management/pls-scorecard', [PlsScorecardController::class, 'index'])->name('rpmo-management.pls-scorecard');
        Route::get('/rpmo-management/pls-scorecard/{ratingId}/pl-rating', [PlsScorecardController::class, 'showPlRating'])->name('rpmo-management.pls-scorecard.pl-rating');
        Route::patch('/rpmo-management/pls-scorecard/{ratingId}/accomplishment/{itemId}', [PlsScorecardController::class, 'updateAccomplishment'])->name('rpmo-management.pls-scorecard.accomplishment.update');
        Route::redirect('/rpmo-management/pls-scorecard/pl-rating', '/rpmo-management/pls-scorecard');
        Route::redirect('/pls-scorecard', '/rpmo-management/pls-scorecard');

        Route::middleware('can:access-administration')->group(function (): void {
            Route::get('/administration/users', [UsersController::class, 'index'])->name('administration.users');
            Route::patch('/administration/users/{userId}', [UsersController::class, 'update'])->name('administration.users.update');
            Route::delete('/administration/users/{userId}', [UsersController::class, 'destroy'])->name('administration.users.destroy');
            Route::patch('/administration/users/{userId}/toggle-status', [UsersController::class, 'toggleStatus'])->name('administration.users.toggle-status');
            Route::redirect('/libraries/users/users-list', '/administration/users')->name('administration.users.index');
        });
    });

    // Fallback: Redirect any legacy /inertia/* URLs to /*
    Route::any('/inertia/{any}', function (string $any) {
        return redirect('/'.$any);
    })->where('any', '.*');
}
