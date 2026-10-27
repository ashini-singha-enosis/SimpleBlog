<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $commentable_type
 * @property int $commentable_id
 * @property string $body
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $author
 * @property-read Model|null $commentable
 */
class Comment extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'commentable_type',
        'commentable_id',
        'body',
    ];

    /**
     * @return MorphTo<Model, $this>
     */
    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
