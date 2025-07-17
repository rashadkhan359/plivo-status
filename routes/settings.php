<?php

use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\OrganizationController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

Route::middleware(['auth', 'organization.context'])->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/password', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('settings/password', [PasswordController::class, 'update'])->name('password.update');

    Route::get('settings/appearance', function () {
        return Inertia::render('settings/appearance');
    })->name('appearance');

    // Organization settings (admin only)

    Route::get('settings/organization', [OrganizationController::class, 'edit'])->name('organization.edit');
    Route::patch('settings/organization', [OrganizationController::class, 'update'])->name('organization.update');

    Route::get('settings/organization/team', [OrganizationController::class, 'team'])->name('organization.team');
    Route::patch('settings/organization/team/role', [OrganizationController::class, 'updateMemberRole'])->name('organization.team.role');
    Route::delete('settings/organization/team/member', [OrganizationController::class, 'removeMember'])->name('organization.team.remove');
});
