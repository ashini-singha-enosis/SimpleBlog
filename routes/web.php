<?php

use App\Http\Controllers\Admin\PostController as AdminPostController;
use App\Http\Controllers\Admin\VideoController as AdminVideoController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ReactionController;
use App\Http\Controllers\VideoController;
use App\Models\Post;
use App\Models\Video;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/posts/{post:slug}/reactions', [ReactionController::class, 'index'])->name('posts.reactions.index');
Route::get('/posts/{post:slug}', [PostController::class, 'show'])->name('posts.show');
Route::get('/videos', [VideoController::class, 'index'])->name('videos.index');
Route::get('/videos/{video:slug}', [VideoController::class, 'show'])->name('videos.show');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/posts/{post:slug}/reactions', [ReactionController::class, 'store'])->name('posts.reactions.store');
    Route::delete('/posts/{post:slug}/reactions', [ReactionController::class, 'destroy'])->name('posts.reactions.destroy');
    Route::post('/posts/{post:slug}/comments', [CommentController::class, 'storeForPost'])
        ->middleware('throttle:comments.create')
        ->name('posts.comments.store');
    Route::post('/videos/{video:slug}/comments', [CommentController::class, 'storeForVideo'])
        ->middleware('throttle:comments.create')
        ->name('videos.comments.store');
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])
        ->middleware('can:delete,comment')
        ->name('comments.destroy');

    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::post('posts/{post}/revisions/{revision}/restore', [AdminPostController::class, 'restoreRevision'])
            ->middleware(['can:update,post', 'throttle:posts.write'])
            ->name('posts.revisions.restore');
        Route::resource('posts', AdminPostController::class)
            ->except(['show'])
            ->middlewareFor('create', 'can:create,'.Post::class)
            ->middlewareFor('store', ['can:create,'.Post::class, 'throttle:posts.create'])
            ->middlewareFor('edit', 'can:update,post')
            ->middlewareFor('update', ['can:update,post', 'throttle:posts.write'])
            ->middlewareFor('destroy', 'can:delete,post');
        Route::resource('videos', AdminVideoController::class)
            ->except(['show'])
            ->middlewareFor('create', 'can:create,'.Video::class)
            ->middlewareFor('store', ['can:create,'.Video::class, 'throttle:posts.create'])
            ->middlewareFor('edit', 'can:update,video')
            ->middlewareFor('update', ['can:update,video', 'throttle:posts.write'])
            ->middlewareFor('destroy', 'can:delete,video');
    });
});

require __DIR__.'/settings.php';
