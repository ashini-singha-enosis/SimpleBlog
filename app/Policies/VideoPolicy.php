<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Video;

class VideoPolicy
{
    /**
     * Any authenticated user may create a video.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Authors may update their own videos. Admins may update any video.
     */
    public function update(User $user, Video $video): bool
    {
        return $user->is_admin || $user->id === $video->user_id;
    }

    /**
     * Authors may delete their own videos. Admins may delete any video.
     */
    public function delete(User $user, Video $video): bool
    {
        return $this->update($user, $video);
    }
}
