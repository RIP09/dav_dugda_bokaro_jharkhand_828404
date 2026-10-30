<?php

use Illuminate\Support\Facades\Route;
use Website\Http\AssetController;
use Website\Http\Authenticate;
use Website\Modules\Achievements\AchievementController;
use Website\Modules\Admissions\AdmissionController;
use Website\Modules\Cms\DashboardController;
use Website\Modules\Cms\InboxController;
use Website\Modules\Cms\ResourceController;
use Website\Modules\Contact\ContactController;
use Website\Modules\Downloads\DownloadController;
use Website\Modules\Events\EventController;
use Website\Modules\Gallery\GalleryAdminController;
use Website\Modules\Gallery\GalleryController;
use Website\Modules\Home\HomeController;
use Website\Modules\Notices\NoticeController;
use Website\Modules\Pages\PageController;
use Website\Modules\Settings\SettingsController;
use Website\Modules\Users\AuthController;
use Website\Modules\Users\UserAdminController;

// Static files of the website package + CMS uploads (work without any publish/symlink step).
Route::get('site-assets/{path}', [AssetController::class, 'asset'])->where('path', '.*')->name('website.asset');
Route::get('site-media/{path}', [AssetController::class, 'media'])->where('path', '.*')->name('website.media');
Route::get('downloads/{file}', [AssetController::class, 'release'])->where('file', '[A-Za-z0-9._-]+')->name('website.release');
Route::get('sitemap.xml', [AssetController::class, 'sitemap'])->name('website.sitemap');

Route::middleware('web')->group(function () {
    // ---------------- Public website ----------------
    Route::get('/', [HomeController::class, 'index'])->name('website.home');
    Route::get('about', [PageController::class, 'about'])->name('website.about');
    Route::get('principal-message', [PageController::class, 'principal'])->name('website.principal');
    Route::get('management', [PageController::class, 'management'])->name('website.management');
    Route::get('academics', [PageController::class, 'academics'])->name('website.academics');
    Route::get('facilities', [PageController::class, 'facilities'])->name('website.facilities');
    Route::get('facilities/{slug}', [PageController::class, 'facility'])->name('website.facility');
    Route::get('faculty', [PageController::class, 'faculty'])->name('website.faculty');
    Route::get('mandatory-disclosure', [PageController::class, 'disclosure'])->name('website.disclosure');
    Route::get('careers', [PageController::class, 'careers'])->name('website.careers');
    Route::get('admissions', [AdmissionController::class, 'show'])->name('website.admissions');
    Route::post('admissions/enquiry', [AdmissionController::class, 'submit'])->name('website.admissions.submit');
    Route::get('notices', [NoticeController::class, 'index'])->name('website.notices');
    Route::get('notices/{slug}', [NoticeController::class, 'show'])->name('website.notices.show');
    Route::get('events', [EventController::class, 'index'])->name('website.events');
    Route::get('events/{slug}', [EventController::class, 'show'])->name('website.events.show');
    Route::get('gallery', [GalleryController::class, 'index'])->name('website.gallery');
    Route::get('gallery/{slug}', [GalleryController::class, 'show'])->name('website.gallery.show');
    Route::get('achievements', [AchievementController::class, 'index'])->name('website.achievements');
    Route::get('downloads', [DownloadController::class, 'index'])->name('website.downloads');
    Route::get('contact', [ContactController::class, 'show'])->name('website.contact');
    Route::post('contact', [ContactController::class, 'submit'])->name('website.contact.submit');
    Route::get('page/{slug}', [PageController::class, 'show'])->name('website.page');

    // ---------------- Website CMS (own login) ----------------
    Route::prefix('website-admin')->name('webadmin.')->group(function () {
        Route::get('login', [AuthController::class, 'show'])->name('login');
        Route::post('login', [AuthController::class, 'login'])->name('login.submit');

        Route::middleware(Authenticate::class)->group(function () {
            Route::post('logout', [AuthController::class, 'logout'])->name('logout');
            Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
            Route::get('change-log', [DashboardController::class, 'log'])->name('log');

            Route::get('settings', [SettingsController::class, 'edit'])->name('settings');
            Route::post('settings', [SettingsController::class, 'update'])->name('settings.update');

            Route::get('inbox/{box}', [InboxController::class, 'index'])->name('inbox')->where('box', 'enquiries|messages');
            Route::get('inbox/{box}/export', [InboxController::class, 'export'])->name('inbox.export')->where('box', 'enquiries|messages');
            Route::get('inbox/{box}/{id}', [InboxController::class, 'show'])->name('inbox.show')->whereNumber('id');
            Route::put('inbox/{box}/{id}', [InboxController::class, 'update'])->name('inbox.update')->whereNumber('id');
            Route::delete('inbox/{box}/{id}', [InboxController::class, 'destroy'])->name('inbox.destroy')->whereNumber('id');

            Route::post('gallery/{album}/photos', [GalleryAdminController::class, 'upload'])->name('gallery.upload');
            Route::put('gallery/{album}/photos', [GalleryAdminController::class, 'updatePhotos'])->name('gallery.photos.update');
            Route::delete('gallery/{album}/photos/{photo}', [GalleryAdminController::class, 'destroyPhoto'])->name('gallery.photos.destroy');
            Route::post('gallery/{album}/photos/{photo}/cover', [GalleryAdminController::class, 'cover'])->name('gallery.photos.cover');

            Route::get('account', [UserAdminController::class, 'account'])->name('account');
            Route::post('account', [UserAdminController::class, 'updateAccount'])->name('account.update');
            Route::get('users', [UserAdminController::class, 'index'])->name('users.index');
            Route::get('users/create', [UserAdminController::class, 'create'])->name('users.create');
            Route::post('users', [UserAdminController::class, 'store'])->name('users.store');
            Route::get('users/{user}/edit', [UserAdminController::class, 'edit'])->name('users.edit');
            Route::put('users/{user}', [UserAdminController::class, 'update'])->name('users.update');
            Route::delete('users/{user}', [UserAdminController::class, 'destroy'])->name('users.destroy');

            Route::get('content/{key}', [ResourceController::class, 'index'])->name('r.index');
            Route::get('content/{key}/create', [ResourceController::class, 'create'])->name('r.create');
            Route::post('content/{key}', [ResourceController::class, 'store'])->name('r.store');
            Route::get('content/{key}/{id}/edit', [ResourceController::class, 'edit'])->name('r.edit')->whereNumber('id');
            Route::put('content/{key}/{id}', [ResourceController::class, 'update'])->name('r.update')->whereNumber('id');
            Route::delete('content/{key}/{id}', [ResourceController::class, 'destroy'])->name('r.destroy')->whereNumber('id');
        });
    });
});
