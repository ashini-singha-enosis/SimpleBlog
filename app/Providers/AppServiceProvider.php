<?php

namespace App\Providers;

use App\Contracts\CommentServiceInterface;
use App\Contracts\PostServiceInterface;
use App\Contracts\VideoServiceInterface;
use App\Events\PostCreated;
use App\Events\PostDeleted;
use App\Events\PostPublished;
use App\Events\PostUpdated;
use App\Listeners\InvalidatePostCache;
use App\Models\Post;
use App\Models\Video;
use App\Services\CommentService;
use App\Services\PostService;
use App\Services\VideoService;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PostServiceInterface::class, PostService::class);
        $this->app->bind(VideoServiceInterface::class, VideoService::class);
        $this->app->bind(CommentServiceInterface::class, CommentService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureMorphMap();
        $this->configurePostEvents();
        $this->configureRateLimiting();
    }

    /**
     * Limit how often a signed-in user can create or change posts.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('posts.create', function (Request $request): Limit {
            $key = (string) ($request->user()?->id ?: $request->ip());

            return Limit::perMinute(5)->by('posts-create:'.$key);
        });

        RateLimiter::for('posts.write', function (Request $request): Limit {
            $key = (string) ($request->user()?->id ?: $request->ip());

            return Limit::perMinute(30)->by('posts-write:'.$key);
        });

        RateLimiter::for('comments.create', function (Request $request): Limit {
            $key = (string) ($request->user()?->id ?: $request->ip());

            return Limit::perMinute(10)->by('comments-create:'.$key);
        });
    }

    /**
     * Keep morph type names stable if model class names move.
     */
    protected function configureMorphMap(): void
    {
        Relation::enforceMorphMap([
            'post' => Post::class,
            'video' => Video::class,
        ]);
    }

    /**
     * Invalidate the public post cache when a post changes.
     */
    protected function configurePostEvents(): void
    {
        Event::listen(PostCreated::class, InvalidatePostCache::class);
        Event::listen(PostUpdated::class, InvalidatePostCache::class);
        Event::listen(PostPublished::class, InvalidatePostCache::class);
        Event::listen(PostDeleted::class, InvalidatePostCache::class);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
