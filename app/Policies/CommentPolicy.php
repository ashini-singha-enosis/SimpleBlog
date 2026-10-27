<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;

class CommentPolicy
{
    /**
     * Any authenticated user may comment on a public post or video.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * The comment author or an admin may delete a comment.
     */
    public function delete(User $user, Comment $comment): bool
    {
        return $user->is_admin || $user->id === $comment->user_id;
    }
}
