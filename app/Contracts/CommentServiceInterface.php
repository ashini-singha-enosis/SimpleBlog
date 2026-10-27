<?php

namespace App\Contracts;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

interface CommentServiceInterface
{
    /**
     * Attach fresh comment counts without storing them in the page cache.
     *
     * @param  array<string, mixed>  $page
     * @return array<string, mixed>
     */
    public function withCounts(array $page): array;

    /**
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function forCommentable(Model $commentable, ?User $viewer, int $page = 1): LengthAwarePaginator;

    public function create(User $user, Model $commentable, string $body): Comment;

    public function delete(Comment $comment, User $actor): void;
}
