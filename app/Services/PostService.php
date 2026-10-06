<?php

namespace App\Services;

use App\Enums\PostStatus;
use App\Events\PostCreated;
use App\Events\PostDeleted;
use App\Events\PostPublished;
use App\Events\PostUpdated;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PostService
{
    public const string CACHE_VERSION_KEY = 'posts.published.version';

    private const int CACHE_TTL_MINUTES = 10;

    /**
     * Published posts for the homepage, cached per page.
     *
     * An author id limits the cached page to that user's public posts.
     *
     * @return array<string, mixed>
     */
    public function paginatedPublished(int $page, int $perPage = 9, ?int $authorId = null): array
    {
        $page = max(1, $page);
        $version = (int) Cache::get(self::CACHE_VERSION_KEY, 1);
        $authorKey = $authorId === null ? 'all' : "author.{$authorId}";
        $key = "posts.published.v{$version}.{$authorKey}.page.{$page}.per.{$perPage}";

        return Cache::remember(
            $key,
            now()->addMinutes(self::CACHE_TTL_MINUTES),
            function () use ($perPage, $page, $authorId): array {
                $query = Post::query()
                    ->published()
                    ->with('author:id,name')
                    ->latest('published_at');

                if ($authorId !== null) {
                    $query->where('user_id', $authorId);
                }

                $paginator = $query->paginate(
                    $perPage,
                    ['id', 'user_id', 'title', 'slug', 'excerpt', 'published_at'],
                    'page',
                    $page,
                );

                if ($authorId !== null) {
                    $paginator->appends(['mine' => 1]);
                }

                return $paginator->toArray();
            },
        );
    }

    /**
     * Drop every cached homepage page by moving the cache version forward.
     */
    public function invalidatePublishedCache(): void
    {
        $version = (int) Cache::get(self::CACHE_VERSION_KEY, 1);

        Cache::forever(self::CACHE_VERSION_KEY, $version + 1);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $author, array $data): Post
    {
        $attributes = $this->attributesFrom($data);
        $attributes['user_id'] = $author->id;
        $attributes['slug'] = $this->uniqueSlug($this->stringValue($data, 'title'));

        $post = $this->saveNewPost($attributes, $this->stringValue($data, 'title'));

        Log::info('Post created', [
            'post_id' => $post->id,
            'user_id' => $author->id,
        ]);

        PostCreated::dispatch($post);

        if ($post->status === PostStatus::Published) {
            $this->logPublished($post, $author);
            PostPublished::dispatch($post);
        }

        return $post;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Post $post, User $actor, array $data): Post
    {
        $previousStatus = $post->status;
        $attributes = $this->attributesFrom($data);
        $title = $this->stringValue($data, 'title');

        if ($post->title !== $title) {
            $attributes['slug'] = $this->uniqueSlug($title, $post->id);
        }

        try {
            $post->update($attributes);
        } catch (UniqueConstraintViolationException) {
            $attributes['slug'] = $this->uniqueSlug($title, $post->id);
            $post->update($attributes);
        }

        Log::info('Post updated', [
            'post_id' => $post->id,
            'user_id' => $actor->id,
        ]);

        PostUpdated::dispatch($post);

        if ($previousStatus !== PostStatus::Published && $post->status === PostStatus::Published) {
            $this->logPublished($post, $actor);
            PostPublished::dispatch($post);
        }

        return $post;
    }

    public function delete(Post $post, User $actor): void
    {
        $post->delete();

        Log::info('Post deleted', [
            'post_id' => $post->id,
            'user_id' => $actor->id,
        ]);

        PostDeleted::dispatch($post);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function saveNewPost(array $attributes, string $title): Post
    {
        try {
            return Post::query()->create($attributes);
        } catch (UniqueConstraintViolationException) {
            $attributes['slug'] = $this->uniqueSlug($title);

            return Post::query()->create($attributes);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function attributesFrom(array $data): array
    {
        $status = $this->statusValue($data);
        $publishedAt = $data['published_at'] ?? null;

        if ($publishedAt === '') {
            $publishedAt = null;
        }

        if ($status === PostStatus::Published && $publishedAt === null) {
            $publishedAt = now();
        }

        return [
            'title' => $this->stringValue($data, 'title'),
            'excerpt' => $this->nullableString($data, 'excerpt'),
            'body' => $this->stringValue($data, 'body'),
            'status' => $status,
            'published_at' => $publishedAt,
        ];
    }

    private function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title);

        if ($base === '') {
            $base = 'post';
        }

        $slug = $base;
        $suffix = 2;

        while ($this->slugExists($slug, $ignoreId)) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    private function slugExists(string $slug, ?int $ignoreId): bool
    {
        $query = Post::withTrashed()->where('slug', $slug);

        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }

        return $query->exists();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function statusValue(array $data): PostStatus
    {
        $status = $data['status'] ?? PostStatus::Draft->value;

        if ($status instanceof PostStatus) {
            return $status;
        }

        if (is_string($status)) {
            return PostStatus::from($status);
        }

        return PostStatus::Draft;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function stringValue(array $data, string $key): string
    {
        $value = $data[$key] ?? '';

        return is_string($value) ? $value : '';
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function nullableString(array $data, string $key): ?string
    {
        $value = $data[$key] ?? null;

        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function logPublished(Post $post, User $actor): void
    {
        Log::info('Post published', [
            'post_id' => $post->id,
            'user_id' => $actor->id,
        ]);
    }
}
