<?php

namespace App\Contracts;

use App\Models\Post;
use App\Models\User;

interface PostServiceInterface
{
    /**
     * Published posts for the homepage, cached per page.
     *
     * An author id limits the cached page to that user's public posts.
     *
     * @return array<string, mixed>
     */
    public function paginatedPublished(int $page, int $perPage = 9, ?int $authorId = null): array;

    /**
     * Drop every cached homepage page by moving the cache version forward.
     */
    public function invalidatePublishedCache(): void;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $author, array $data): Post;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Post $post, User $actor, array $data): Post;

    public function delete(Post $post, User $actor): void;
}
