<?php

namespace App\Listeners;

use App\Events\PostCreated;
use App\Events\PostDeleted;
use App\Events\PostPublished;
use App\Events\PostUpdated;
use App\Contracts\PostServiceInterface;

class InvalidatePostCache
{
    public function __construct(private PostServiceInterface $posts) {}

    public function handle(PostCreated|PostUpdated|PostPublished|PostDeleted $event): void
    {
        $this->posts->invalidatePublishedCache();
    }
}
