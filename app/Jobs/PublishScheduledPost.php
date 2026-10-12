<?php

namespace App\Jobs;

use App\Contracts\PostServiceInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class PublishScheduledPost implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $postId, public string $publishedAt) {}

    public function handle(PostServiceInterface $posts): void
    {
        $posts->publishIfDue($this->postId, $this->publishedAt);
    }
}
