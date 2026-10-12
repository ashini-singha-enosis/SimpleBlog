<?php

namespace App\Models;

use App\Enums\PostStatus;
use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int $lock_version
 * @property int $user_id
 * @property int|null $category_id
 * @property string $title
 * @property string $slug
 * @property string|null $excerpt
 * @property string $body
 * @property string|null $image_path
 * @property string|null $image_card_path
 * @property string|null $image_full_path
 * @property-read string|null $image_url
 * @property-read string|null $image_card_url
 * @property-read string|null $image_full_url
 * @property PostStatus $status
 * @property Carbon|null $published_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read User|null $author
 * @property-read Category|null $category
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Tag> $tags
 */
#[Fillable(['user_id', 'category_id', 'title', 'slug', 'excerpt', 'body', 'image_path', 'image_card_path', 'image_full_path', 'status', 'published_at', 'lock_version'])]
class Post extends Model
{
    /** @use HasFactory<PostFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $appends = [
        'image_url',
        'image_card_url',
        'image_full_url',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PostStatus::class,
            'published_at' => 'datetime',
            'lock_version' => 'integer',
        ];
    }

    /**
     * Public URL for the original cover file, when one is stored.
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->publicImageUrl($this->image_path));
    }

    /**
     * Public URL for the card-sized cover, when the background job has written it.
     */
    protected function imageCardUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->publicImageUrl($this->image_card_path));
    }

    /**
     * Public URL for the full-sized cover, when the background job has written it.
     */
    protected function imageFullUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->publicImageUrl($this->image_full_path));
    }

    private function publicImageUrl(mixed $path): ?string
    {
        if (! is_string($path) || $path === '') {
            return null;
        }

        return Storage::disk('public')->url($path);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return BelongsToMany<Tag, $this>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<PostRevision, $this>
     */
    public function revisions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PostRevision::class);
    }

    /**
     * Posts that are visible on the public site.
     *
     * @param  Builder<Post>  $query
     * @return Builder<Post>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('status', PostStatus::Published)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function isPubliclyVisible(): bool
    {
        return $this->status === PostStatus::Published
            && $this->published_at !== null
            && $this->published_at->lessThanOrEqualTo(now());
    }
}
