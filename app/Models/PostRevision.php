<?php

namespace App\Models;

use App\Enums\PostStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $post_id
 * @property int $user_id
 * @property int $lock_version
 * @property string $title
 * @property string|null $excerpt
 * @property string $body
 * @property PostStatus $status
 * @property Carbon|null $published_at
 * @property string|null $image_path
 * @property int|null $category_id
 * @property list<int> $tag_ids
 * @property-read User|null $author
 */
class PostRevision extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'post_id',
        'user_id',
        'lock_version',
        'title',
        'excerpt',
        'body',
        'status',
        'published_at',
        'image_path',
        'category_id',
        'tag_ids',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PostStatus::class,
            'published_at' => 'datetime',
            'tag_ids' => 'array',
            'lock_version' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Post, $this>
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
