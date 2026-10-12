<?php

namespace App\Services;

use App\Contracts\PostServiceInterface;
use App\Enums\PostStatus;
use App\Events\PostCreated;
use App\Events\PostDeleted;
use App\Events\PostPublished;
use App\Events\PostUpdated;
use App\Exceptions\StalePostException;
use App\Jobs\ProcessPostImage;
use App\Jobs\PublishScheduledPost;
use App\Models\Category;
use App\Models\Post;
use App\Models\PostRevision;
use App\Models\Tag;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class PostService implements PostServiceInterface
{
    public const string CACHE_VERSION_KEY = 'posts.published.version';

    private const int CACHE_TTL_MINUTES = 10;

    private ?string $imageToProcess = null;

    public function __construct(private PostWorkflow $workflow) {}

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
                    ['id', 'user_id', 'category_id', 'title', 'slug', 'excerpt', 'image_path', 'image_card_path', 'image_full_path', 'published_at'],
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
     * @return list<array{value: string, label: string}>
     */
    public function allowedStatuses(User $actor, ?Post $post = null): array
    {
        $statuses = $post === null
            ? [PostStatus::Draft, PostStatus::InReview, PostStatus::Published]
            : $this->workflow->targets($actor, $post->status);

        return array_map(fn (PostStatus $status): array => [
            'value' => $status->value,
            'label' => $status->label(),
        ], $statuses);
    }

    public function publishIfDue(int $postId, ?string $expectedPublishedAt = null): bool
    {
        $post = Post::query()->find($postId);

        if ($post === null || $post->status !== PostStatus::Scheduled || $post->published_at === null) {
            return false;
        }

        if ($post->published_at->isFuture()) {
            return false;
        }

        if ($expectedPublishedAt !== null && ! $post->published_at->utc()->startOfSecond()->equalTo(Carbon::parse($expectedPublishedAt)->utc()->startOfSecond())) {
            return false;
        }

        $author = $post->author;

        if ($author === null) {
            return false;
        }

        $published = false;

        DB::transaction(function () use ($post, $author, &$published): void {
            $updated = Post::query()
                ->whereKey($post->id)
                ->where('status', PostStatus::Scheduled->value)
                ->where('lock_version', $post->lock_version)
                ->update([
                    'status' => PostStatus::Published->value,
                    'lock_version' => $post->lock_version + 1,
                ]);

            if ($updated === 0) {
                return;
            }

            $post->refresh();
            $this->recordRevision($post, $author);
            $published = true;
        });

        if ($published) {
            $this->logPublished($post, $author);
            PostPublished::dispatch($post);
        }

        return $published;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $author, array $data): Post
    {
        $attributes = $this->attributesFrom($author, null, $data);
        $attributes['user_id'] = $author->id;
        $attributes['slug'] = $this->uniqueSlug($this->stringValue($data, 'title'));
        $attributes['lock_version'] = 0;

        $post = DB::transaction(function () use ($attributes, $data, $author): Post {
            $post = $this->saveNewPost($attributes, $this->stringValue($data, 'title'));
            $this->syncTaxonomy($post, $data);
            $this->syncImage($post, $data);
            $this->recordRevision($post, $author);

            return $post->refresh();
        });

        $this->afterSave($post, null);

        Log::info('Post created', [
            'post_id' => $post->id,
            'user_id' => $author->id,
        ]);

        PostCreated::dispatch($post);

        return $post;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Post $post, User $actor, array $data): Post
    {
        $previousStatus = $post->status;
        $attributes = $this->attributesFrom($actor, $post, $data);
        $title = $this->stringValue($data, 'title');
        $expectedLock = array_key_exists('lock_version', $data)
            ? (int) $data['lock_version']
            : $post->lock_version;

        if ($post->title !== $title) {
            $attributes['slug'] = $this->uniqueSlug($title, $post->id);
        }

        $post = DB::transaction(function () use ($post, $actor, $data, $attributes, $title, $expectedLock): Post {
            try {
                $this->saveExisting($post, $attributes, $expectedLock);
            } catch (UniqueConstraintViolationException) {
                $attributes['slug'] = $this->uniqueSlug($title, $post->id);
                $this->saveExisting($post, $attributes, $expectedLock);
            }

            $this->syncTaxonomy($post, $data);
            $this->syncImage($post, $data);
            $this->restoreImagePath($post, $data);
            $this->recordRevision($post, $actor);

            return $post->refresh();
        });

        $this->afterSave($post, $previousStatus);

        Log::info('Post updated', [
            'post_id' => $post->id,
            'user_id' => $actor->id,
        ]);

        PostUpdated::dispatch($post);

        return $post;
    }

    public function restoreRevision(Post $post, User $actor, int $revisionId): Post
    {
        $revision = PostRevision::query()
            ->where('post_id', $post->id)
            ->findOrFail($revisionId);

        $tagIds = $revision->tag_ids ?? [];
        $tagNames = $tagIds === []
            ? []
            : Tag::query()->whereIn('id', $tagIds)->pluck('name')->all();

        $categoryName = $revision->category_id === null
            ? null
            : Category::query()->whereKey($revision->category_id)->value('name');

        return $this->update($post, $actor, [
            'title' => $revision->title,
            'excerpt' => $revision->excerpt,
            'body' => $revision->body,
            'status' => $revision->status->value,
            'published_at' => $revision->published_at?->toIso8601String(),
            'category' => $categoryName,
            'tags' => $tagNames,
            'lock_version' => $post->lock_version,
            'restored_image_path' => $revision->image_path,
        ]);
    }

    public function delete(Post $post, User $actor): void
    {
        $this->deleteStoredImages($post);
        $post->image_path = null;
        $post->image_card_path = null;
        $post->image_full_path = null;
        $post->save();
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
    private function attributesFrom(User $actor, ?Post $post, array $data): array
    {
        $requested = $this->statusValue($data);
        $this->workflow->assert($actor, $post?->status, $requested);
        $publishedAt = $this->publishedAtValue($data, $requested);
        $status = $this->workflow->storedStatus($requested, $publishedAt);

        return [
            'title' => $this->stringValue($data, 'title'),
            'excerpt' => $this->nullableString($data, 'excerpt'),
            'body' => $this->stringValue($data, 'body'),
            'status' => $status,
            'published_at' => $publishedAt,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function publishedAtValue(array $data, PostStatus $requested): ?CarbonInterface
    {
        $publishedAt = $data['published_at'] ?? null;

        if ($publishedAt === '' || $publishedAt === null) {
            $publishedAt = null;
        } elseif (is_string($publishedAt)) {
            $publishedAt = Carbon::parse($publishedAt);
        } elseif (! $publishedAt instanceof CarbonInterface) {
            $publishedAt = null;
        }

        if ($requested === PostStatus::Published && $publishedAt === null) {
            return now();
        }

        return $publishedAt;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function saveExisting(Post $post, array $attributes, int $expectedLock): void
    {
        $attributes['lock_version'] = $expectedLock + 1;
        $status = $attributes['status'] ?? null;

        if ($status instanceof PostStatus) {
            $attributes['status'] = $status->value;
        }

        if (($attributes['published_at'] ?? null) instanceof CarbonInterface) {
            $attributes['published_at'] = Carbon::parse($attributes['published_at'])->utc()->toDateTimeString();
        }

        $updated = Post::query()
            ->whereKey($post->id)
            ->where('lock_version', $expectedLock)
            ->update($attributes);

        if ($updated === 0) {
            throw new StalePostException;
        }

        $post->refresh();
    }

    private function afterSave(Post $post, ?PostStatus $previousStatus): void
    {
        $publishedByQueue = false;

        if ($post->status === PostStatus::Scheduled && $post->published_at !== null) {
            if ($post->published_at->isFuture()) {
                PublishScheduledPost::dispatch($post->id, $post->published_at->toIso8601String())
                    ->delay($post->published_at);
            } else {
                $publishedByQueue = $this->publishIfDue($post->id);
                $post->refresh();
            }
        }

        if ($this->imageToProcess !== null) {
            ProcessPostImage::dispatch($post->id, $this->imageToProcess);
            $this->imageToProcess = null;
        }

        if (! $publishedByQueue && $previousStatus !== PostStatus::Published && $post->status === PostStatus::Published) {
            $author = $post->author;

            if ($author !== null) {
                $this->logPublished($post, $author);
            }

            PostPublished::dispatch($post);
        }
    }

    private function recordRevision(Post $post, User $actor): void
    {
        $post->load('tags:id');

        PostRevision::query()->create([
            'post_id' => $post->id,
            'user_id' => $actor->id,
            'lock_version' => $post->lock_version,
            'title' => $post->title,
            'excerpt' => $post->excerpt,
            'body' => $post->body,
            'status' => $post->status,
            'published_at' => $post->published_at,
            'image_path' => $post->image_path,
            'category_id' => $post->category_id,
            'tag_ids' => $post->tags->pluck('id')->map(fn (mixed $id): int => (int) $id)->all(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function restoreImagePath(Post $post, array $data): void
    {
        if (! array_key_exists('restored_image_path', $data)) {
            return;
        }

        $path = $data['restored_image_path'];
        $post->image_path = is_string($path) && $path !== '' ? $path : null;
        $post->image_card_path = null;
        $post->image_full_path = null;
        $post->save();

        if (is_string($post->image_path) && $post->image_path !== '') {
            $this->imageToProcess = $post->image_path;
        }
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

    /**
     * @param  array<string, mixed>  $data
     */
    private function syncImage(Post $post, array $data): void
    {
        $this->imageToProcess = null;
        $file = $data['image'] ?? null;

        if ($file instanceof UploadedFile) {
            $previous = $post->image_path;
            $previousCard = $post->image_card_path;
            $previousFull = $post->image_full_path;
            $post->image_path = $this->storeImage($post, $file);
            $post->image_card_path = null;
            $post->image_full_path = null;
            $post->save();
            $this->imageToProcess = $post->image_path;

            if (is_string($previous) && $previous !== '' && $previous !== $post->image_path) {
                $this->deleteImageFile($previous);
            }

            $this->deleteImageFile($previousCard);
            $this->deleteImageFile($previousFull);

            return;
        }

        $remove = filter_var($data['remove_image'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if ($remove && is_string($post->image_path) && $post->image_path !== '') {
            $this->deleteStoredImages($post);
            $post->image_path = null;
            $post->image_card_path = null;
            $post->image_full_path = null;
            $post->save();
        }
    }

    private function storeImage(Post $post, UploadedFile $file): string
    {
        $extension = $file->guessExtension() ?: 'jpg';
        $path = $file->storeAs(
            'posts/'.$post->id,
            Str::uuid()->toString().'.'.$extension,
            'public',
        );

        if (! is_string($path)) {
            throw new RuntimeException('The cover image could not be stored.');
        }

        return $path;
    }

    private function deleteImageFile(?string $path): void
    {
        if (! is_string($path) || $path === '' || str_contains($path, '..')) {
            return;
        }

        Storage::disk('public')->delete($path);
    }

    private function deleteStoredImages(Post $post): void
    {
        $this->deleteImageFile($post->image_path);
        $this->deleteImageFile($post->image_card_path);
        $this->deleteImageFile($post->image_full_path);
    }
}
