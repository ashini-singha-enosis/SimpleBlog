<?php

namespace App\Listeners;

use App\Events\PostCreated;
use App\Events\PostDeleted;
use App\Events\PostPublished;
use App\Events\PostUpdated;
use App\Services\PostService;

class InvalidatePostCache
{
    public function __construct(private PostService $posts) {}

    public function handle(PostCreated|PostUpdated|PostPublished|PostDeleted $event): void
    {
        $this->posts->invalidatePublishedCache();
    }
}
