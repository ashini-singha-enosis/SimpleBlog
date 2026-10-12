<?php

namespace App\Console\Commands;

use App\Contracts\PostServiceInterface;
use App\Enums\PostStatus;
use App\Models\Post;
use Illuminate\Console\Command;

class PublishDuePosts extends Command
{
    protected $signature = 'posts:publish-due';

    protected $description = 'Publish scheduled posts whose publish time has arrived';

    public function handle(PostServiceInterface $posts): int
    {
        Post::query()
            ->where('status', PostStatus::Scheduled)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->lazyById()
            ->each(function (Post $post) use ($posts): void {
                $posts->publishIfDue($post->id);
            });

        return self::SUCCESS;
    }
}
