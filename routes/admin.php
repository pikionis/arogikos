<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\ArticleController;
use App\Http\Controllers\Admin\Auth\InvitationController;
use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Admin\Auth\PasswordResetController;
use App\Http\Controllers\Admin\AuthorController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EventController;
use App\Http\Controllers\Admin\GalleryController;
use App\Http\Controllers\Admin\MessageController;
use App\Http\Controllers\Admin\PageContentController;
use App\Http\Controllers\Admin\PreviewController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin panel
|--------------------------------------------------------------------------
| Everything under /admin. Required from routes/web.php, so the whole group
| already sits inside the `web` middleware stack (session + CSRF).
|
| There is no registration route by design: accounts exist only by invitation
| (see the Users screen and InvitationController).
*/

Route::prefix('admin')->name('admin.')->group(function () {

    /* ------------------------------------------------------------- guests */

    Route::middleware('guest')->group(function () {
        Route::get('login', [LoginController::class, 'show'])->name('login');
        Route::post('login', [LoginController::class, 'store'])
            ->middleware('throttle:admin-login')
            ->name('login.store');

        Route::get('forgot-password', [PasswordResetController::class, 'showLinkRequest'])
            ->name('password.request');
        Route::post('forgot-password', [PasswordResetController::class, 'sendLink'])
            ->middleware('throttle:admin-password')
            ->name('password.email');

        Route::get('reset-password/{token}', [PasswordResetController::class, 'showReset'])
            ->name('password.reset');
        Route::post('reset-password', [PasswordResetController::class, 'reset'])
            ->middleware('throttle:admin-password')
            ->name('password.update');

        Route::get('invitation/{token}', [InvitationController::class, 'show'])
            ->middleware('throttle:admin-invitation')
            ->name('invitation.show');
        Route::post('invitation/{token}', [InvitationController::class, 'accept'])
            ->middleware('throttle:admin-invitation')
            ->name('invitation.accept');
    });

    /* ---------------------------------------------------- signed-in staff */

    Route::middleware(['auth', 'active'])->group(function () {
        Route::post('logout', [LoginController::class, 'destroy'])->name('logout');

        Route::get('/', DashboardController::class)->name('dashboard');

        /* -------------------------------------------------------- profile */

        // Reachable by every role: with mail unconfigured this is the only way
        // anyone can change the password they were given.
        Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::put('profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
        Route::put('profile/author', [ProfileController::class, 'updateAuthor'])->name('profile.author');

        /* -------------------------------------------------------- preview */

        // The public templates, rendered for staff only, so a draft can be
        // read the way a visitor will read it before anyone else can.
        Route::get('preview/arthro/{article:id}', [PreviewController::class, 'article'])->name('preview.article');
        Route::get('preview/ekdilosi/{event:id}', [PreviewController::class, 'event'])->name('preview.event');

        /* ------------------------------------------------------- articles */

        // Bound by id, not slug: Article::getRouteKeyName() is 'slug' for the
        // public site, but an admin URL must survive the slug being edited.
        Route::prefix('articles')->name('articles.')->group(function () {
            Route::get('/', [ArticleController::class, 'index'])->name('index');
            Route::get('create', [ArticleController::class, 'create'])->name('create');
            Route::post('/', [ArticleController::class, 'store'])->name('store');
            Route::get('{article:id}/edit', [ArticleController::class, 'edit'])->name('edit');
            Route::put('{article:id}', [ArticleController::class, 'update'])->name('update');
            Route::delete('{article:id}', [ArticleController::class, 'destroy'])->name('destroy');
        });

        /* ----------------------------------------------------- categories */

        // JSON, driven by the panel that opens beside the category picker on
        // the article form — a redirect would discard the half-written article.
        Route::prefix('categories')->name('categories.')->group(function () {
            Route::get('/', [CategoryController::class, 'index'])->name('index');
            Route::post('/', [CategoryController::class, 'store'])->name('store');
            Route::put('{category}', [CategoryController::class, 'update'])->name('update');
            Route::delete('{category}', [CategoryController::class, 'destroy'])->name('destroy');
            Route::post('{category}/restore', [CategoryController::class, 'restore'])->name('restore');
        });

        /* --------------------------------------------------------- events */

        Route::prefix('events')->name('events.')->group(function () {
            Route::get('/', [EventController::class, 'index'])->name('index');
            Route::get('create', [EventController::class, 'create'])->name('create');
            Route::post('/', [EventController::class, 'store'])->name('store');
            Route::get('{event:id}/edit', [EventController::class, 'edit'])->name('edit');
            Route::put('{event:id}', [EventController::class, 'update'])->name('update');
            Route::delete('{event:id}', [EventController::class, 'destroy'])->name('destroy');
        });

        /* -------------------------------------------------------- authors */

        Route::prefix('authors')->name('authors.')->group(function () {
            Route::get('/', [AuthorController::class, 'index'])->name('index');
            // Before {author}: "order" is a word, not an id.
            Route::patch('order', [AuthorController::class, 'order'])->name('order');
            Route::get('create', [AuthorController::class, 'create'])->name('create');
            Route::post('/', [AuthorController::class, 'store'])->name('store');
            Route::get('{author}/edit', [AuthorController::class, 'edit'])->name('edit');
            Route::put('{author}', [AuthorController::class, 'update'])->name('update');
            Route::delete('{author}', [AuthorController::class, 'destroy'])->name('destroy');
        });

        /* -------------------------------------------------------- gallery */

        Route::prefix('gallery')->name('gallery.')->group(function () {
            Route::get('/', [GalleryController::class, 'index'])->name('index');
            Route::get('create', [GalleryController::class, 'create'])->name('create');
            Route::post('/', [GalleryController::class, 'store'])->name('store');
            Route::get('{album:id}/edit', [GalleryController::class, 'edit'])->name('edit');
            Route::put('{album:id}', [GalleryController::class, 'update'])->name('update');
            Route::delete('{album:id}', [GalleryController::class, 'destroy'])->name('destroy');

            Route::post('{album:id}/photos', [GalleryController::class, 'storePhotos'])->name('photos.store');
            Route::patch('photos/{photo}', [GalleryController::class, 'updatePhoto'])->name('photos.update');
            Route::delete('photos/{photo}', [GalleryController::class, 'destroyPhoto'])->name('photos.destroy');
        });

        /* ---------------------------------------------------- page content */

        Route::prefix('pages')->name('pages.')->group(function () {
            Route::get('/', [PageContentController::class, 'index'])->name('index');
            Route::get('{pageKey}', [PageContentController::class, 'edit'])->name('edit');
            Route::put('{pageKey}', [PageContentController::class, 'update'])->name('update');
            Route::delete('{pageKey}/reset', [PageContentController::class, 'reset'])->name('reset');
        });

        /* ------------------------------------------------------- messages */

        Route::prefix('messages')->name('messages.')->group(function () {
            Route::get('/', [MessageController::class, 'index'])->name('index');
            Route::get('{message}', [MessageController::class, 'show'])->name('show');
            Route::patch('{message}/read', [MessageController::class, 'toggleRead'])->name('read');
            Route::delete('{message}', [MessageController::class, 'destroy'])->name('destroy');
        });

        /* ------------------------------------------------------- settings */

        Route::get('settings', [SettingController::class, 'edit'])->name('settings.edit');
        Route::put('settings', [SettingController::class, 'update'])->name('settings.update');

        /* ------------------------------------------------------- accounts */

        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/', [UserController::class, 'index'])->name('index');
            Route::post('invite', [UserController::class, 'invite'])->name('invite');
            Route::delete('invitations/{invitation}', [UserController::class, 'revokeInvitation'])
                ->name('invitations.revoke');
            Route::patch('{user}/role', [UserController::class, 'updateRole'])->name('role');
            Route::patch('{user}/active', [UserController::class, 'toggleActive'])->name('active');
            Route::delete('{user}', [UserController::class, 'destroy'])->name('destroy');
        });
    });
});
