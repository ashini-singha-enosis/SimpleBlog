<?php

namespace App\Services;

use App\Contracts\PostServiceInterface;
use App\Enums\PostStatus;
use App\Events\PostCreated;
use App\Events\PostDeleted;
use App\Events\PostPublished;
use App\Events\PostUpdated;
use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PostService implements PostServiceInterface
{
    public const string CACHE_VERSION_KEY = 'posts.published.version';

    private const int CACHE_TTL_MINUTES = 10;

    /**
     * Published posts for the homepage, cached per page and filter set.
     *
     * Supported filters: author_id, q (title contains), category (slug), tag (slug).
     * An author id limits the cached page to that user's public posts.
     *
     * @param  array{author_id?: int|null, q?: string|null, category?: string|null, tag?: string|null}  $filters
     * @return array<string, mixed>
     */
    public function paginatedPublished(int $page, int $perPage = 9, array $filters = []): array
    {
        $page = max(1, $page);
        $authorId = isset($filters['author_id']) && is_int($filters['author_id']) ? $filters['author_id'] : null;
        $q = $this->filterText($filters['q'] ?? null);
        $category = $this->filterText($filters['category'] ?? null);
        $tag = $this->filterText($filters['tag'] ?? null);
        $version = (int) Cache::get(self::CACHE_VERSION_KEY, 1);
        $filterKey = hash('xxh128', json_encode([$authorId, $q, $category, $tag], JSON_THROW_ON_ERROR));
        $key = "posts.published.v{$version}.{$filterKey}.page.{$page}.per.{$perPage}";

        return Cache::remember(
            $key,
            now()->addMinutes(self::CACHE_TTL_MINUTES),
            function () use ($perPage, $page, $authorId, $q, $category, $tag): array {
                $query = Post::query()
                    ->published()
                    ->with([
                        'author:id,name',
                        'category:id,name,slug',
                        'tags:id,name,slug',
                    ])
                    ->latest('published_at');

                if ($authorId !== null) {
                    $query->where('user_id', $authorId);
                }

                if ($q !== '') {
                    $query->where('title', 'like', '%'.$this->escapeLike($q).'%');
                }

                if ($category !== '') {
                    $query->whereHas('category', function (Builder $builder) use ($category): void {
                        $builder->where('slug', $category);
                    });
                }

                if ($tag !== '') {
                    $query->whereHas('tags', function (Builder $builder) use ($tag): void {
                        $builder->where('slug', $tag);
                    });
                }

                $paginator = $query->paginate(
                    $perPage,
                    ['id', 'user_id', 'category_id', 'title', 'slug', 'excerpt', 'published_at'],
                    'page',
                    $page,
                );

                $appends = array_filter([
                    'mine' => $authorId !== null ? 1 : null,
                    'q' => $q !== '' ? $q : null,
                    'category' => $category !== '' ? $category : null,
                    'tag' => $tag !== '' ? $tag : null,
                ], fn (mixed $value): bool => $value !== null && $value !== '');

                if ($appends !== []) {
                    $paginator->appends($appends);
                }

                return $paginator->toArray();
            },
        );
    }

    /**
     * Categories and public tag counts for the homepage.
     *
     * Counts include only posts that are already public.
     *
     * @return array{
     *     categories: list<array{name: string, slug: string}>,
     *     tags: list<array{name: string, slug: string, posts_count: int, posts_count_label: string}>
     * }
     */
    public function homepageTaxonomy(): array
    {
        $version = (int) Cache::get(self::CACHE_VERSION_KEY, 1);

        return Cache::remember(
            "posts.taxonomy.v{$version}",
            now()->addMinutes(self::CACHE_TTL_MINUTES),
            function (): array {
                return [
                    'categories' => Category::query()
                        ->orderBy('name')
                        ->get(['name', 'slug'])
                        ->map(fn (Category $category): array => [
                            'name' => $category->name,
                            'slug' => $category->slug,
                        ])
                        ->all(),
                    'tags' => Tag::query()
                        ->withCount(['posts as posts_count' => function (Builder $query): void {
                            $query->published();
                        }])
                        ->orderByDesc('posts_count')
                        ->orderBy('name')
                        ->get()
                        ->map(function (Tag $tag): array {
                            $count = (int) $tag->getAttribute('posts_count');

                            return [
                                'name' => $tag->name,
                                'slug' => $tag->slug,
                                'posts_count' => $count,
                                'posts_count_label' => $this->abbreviateCount($count),
                            ];
                        })
                        ->all(),
                ];
            },
        );
    }

    /**
     * Existing category and tag names for the post form.
     *
     * @return array{categories: list<string>, tags: list<string>}
     */
    public function taxonomySuggestions(): array
    {
        return [
            'categories' => Category::query()->orderBy('name')->pluck('name')->all(),
            'tags' => Tag::query()->orderBy('name')->pluck('name')->all(),
        ];
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
        $this->syncTaxonomy($post, $data);

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

        $this->syncTaxonomy($post, $data);

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

    /**
     * @param  array<string, mixed>  $data
     */
    private function syncTaxonomy(Post $post, array $data): void
    {
        $categoryName = $this->nullableString($data, 'category');

        if ($categoryName === null) {
            $post->category()->dissociate();
        } else {
            $post->category()->associate($this->findOrCreateCategory($categoryName));
        }

        $post->save();

        $tagIds = [];

        foreach ($this->tagNames($data) as $name) {
            $tagIds[] = $this->findOrCreateTag($name)->id;
        }

        $post->tags()->sync(array_values(array_unique($tagIds)));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<string>
     */
    private function tagNames(array $data): array
    {
        $tags = $data['tags'] ?? [];

        if (! is_array($tags)) {
            return [];
        }

        $names = [];

        foreach ($tags as $tag) {
            if (! is_string($tag)) {
                continue;
            }

            $name = trim($tag);

            if ($name === '') {
                continue;
            }

            $names[Str::lower($name)] = $name;
        }

        return array_values($names);
    }

    private function findOrCreateCategory(string $name): Category
    {
        $slug = Str::slug($name);

        return Category::query()->firstOrCreate(
            ['slug' => $slug !== '' ? $slug : 'category'],
            ['name' => $name],
        );
    }

    private function findOrCreateTag(string $name): Tag
    {
        $slug = Str::slug($name);

        return Tag::query()->firstOrCreate(
            ['slug' => $slug !== '' ? $slug : 'tag'],
            ['name' => $name],
        );
    }

    private function filterText(mixed $value): string
    {
        return is_string($value) ? trim($value) : '';
    }

    private function escapeLike(string $value): string
    {
        return addcslashes($value, '%_\\');
    }

    /**
     * Short public counts: 1100 becomes 1.1K and 1000000 becomes 1M.
     */
    private function abbreviateCount(int $count): string
    {
        $units = [
            1_000_000_000_000 => 'T',
            1_000_000_000 => 'B',
            1_000_000 => 'M',
            1_000 => 'K',
        ];

        foreach ($units as $divisor => $suffix) {
            if (abs($count) < $divisor) {
                continue;
            }

            $formatted = number_format($count / $divisor, 1, '.', '');
            $formatted = rtrim(rtrim($formatted, '0'), '.');

            return $formatted.$suffix;
        }

        return (string) $count;
    }
}
