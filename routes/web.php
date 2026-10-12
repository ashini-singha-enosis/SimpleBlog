<?php

use App\Http\Controllers\Admin\PostController as AdminPostController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PostController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/posts/{post:slug}', [PostController::class, 'show'])->name('posts.show');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::post('posts/{post}/revisions/{revision}/restore', [AdminPostController::class, 'restoreRevision'])
            ->name('posts.revisions.restore');
        Route::resource('posts', AdminPostController::class)->except(['show']);
    });
});

require __DIR__.'/settings.php';
