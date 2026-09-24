<?php

declare(strict_types=1);

use App\Http\Controllers\AboutController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\EventsController;
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ServicesController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/sxetika-me-emas', AboutController::class)->name('about');
Route::get('/ypiresies', ServicesController::class)->name('services');

Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('blog.show');

Route::get('/ekdiloseis', EventsController::class)->name('events');
Route::get('/gallery', GalleryController::class)->name('gallery');

Route::get('/epikoinonia', [ContactController::class, 'show'])->name('contact');
Route::post('/epikoinonia', [ContactController::class, 'store'])->name('contact.store');

Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');

require __DIR__.'/admin.php';
