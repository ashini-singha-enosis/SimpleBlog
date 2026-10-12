<?php

namespace App\Services;

use App\Enums\PostStatus;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;

class PostWorkflow
{
    /**
     * @return list<PostStatus>
     */
    public function targets(User $actor, PostStatus $from): array
    {
        return match ($from) {
            PostStatus::Draft => [PostStatus::Draft, PostStatus::InReview, PostStatus::Published],
            PostStatus::InReview => $actor->is_admin
                ? [PostStatus::InReview, PostStatus::Draft, PostStatus::ChangesRequested, PostStatus::Published]
                : [PostStatus::InReview, PostStatus::Draft],
            PostStatus::ChangesRequested => [PostStatus::ChangesRequested, PostStatus::InReview, PostStatus::Draft],
            PostStatus::Published => [PostStatus::Published, PostStatus::Draft],
            PostStatus::Scheduled => [PostStatus::Scheduled, PostStatus::Draft],
        };
    }

    public function assert(?User $actor, ?PostStatus $from, PostStatus $to): void
    {
        if ($from === $to) {
            return;
        }

        $allowed = $from === null
            ? [PostStatus::Draft, PostStatus::InReview, PostStatus::Published]
            : ($actor === null ? [] : $this->targets($actor, $from));

        if (! in_array($to, $allowed, true)) {
            throw ValidationException::withMessages([
                'status' => 'That status change is not allowed.',
            ]);
        }
    }

    /**
     * A future publish time is stored as scheduled and goes live through the queue.
     */
    public function storedStatus(PostStatus $requested, ?CarbonInterface $publishedAt): PostStatus
    {
        if ($requested === PostStatus::Published && $publishedAt !== null && $publishedAt->isFuture()) {
            return PostStatus::Scheduled;
        }

        return $requested;
    }
}
