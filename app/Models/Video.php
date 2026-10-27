<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int $user_id
 * @property string $caption
 * @property string $slug
 * @property string|null $video_path
 * @property-read string|null $video_url
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read User|null $author
 */
class Video extends Model
{
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'caption',
        'slug',
        'video_path',
    ];

    /**
     * @var list<string>
     */
    protected $appends = [
        'video_url',
    ];

    /**
     * Public URL for the stored video file, when one exists.
     */
    protected function videoUrl(): Attribute
    {
        return Attribute::get(function (): ?string {
            if (! is_string($this->video_path) || $this->video_path === '') {
                return null;
            }

            return Storage::disk('public')->url($this->video_path);
        });
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return MorphMany<Comment, $this>
     */
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function isPubliclyVisible(): bool
    {
        return $this->deleted_at === null;
    }
}
