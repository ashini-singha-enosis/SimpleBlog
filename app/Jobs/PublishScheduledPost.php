<?php

namespace App\Jobs;

use App\Contracts\PostServiceInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class PublishScheduledPost implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $postId,
        public string $publishedAt,
        public ?string $requestId = null,
    ) {}

    public function handle(PostServiceInterface $posts): void
    {
        if ($this->requestId !== null) {
            Log::shareContext(['request_id' => $this->requestId]);
        }

        $posts->publishIfDue($this->postId, $this->publishedAt);
    }
}
