<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\User;

class PostPolicy
{
    /**
     * Any authenticated user may create a post.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Authors may update their own posts. Admins may update any post.
     */
    public function update(User $user, Post $post): bool
    {
        return $user->is_admin || $user->id === $post->user_id;
    }

    /**
     * Authors may delete their own posts. Admins may delete any post.
     */
    public function delete(User $user, Post $post): bool
    {
        return $this->update($user, $post);
    }
}
