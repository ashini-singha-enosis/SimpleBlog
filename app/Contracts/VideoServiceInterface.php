<?php

namespace App\Contracts;

use App\Models\User;
use App\Models\Video;

interface VideoServiceInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $author, array $data): Video;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Video $video, User $actor, array $data): Video;

    public function delete(Video $video, User $actor): void;
}
