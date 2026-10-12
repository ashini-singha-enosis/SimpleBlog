<?php

namespace App\Contracts;

use App\Models\Post;
use App\Models\User;

interface PostServiceInterface
{
    /**
     * Published posts for the homepage, cached per page and filter set.
     *
     * Supported filters: author_id, q (title contains), category (slug), tag (slug).
     * An author id limits the cached page to that user's public posts.
     *
     * @param  array{author_id?: int|null, q?: string|null, category?: string|null, tag?: string|null}  $filters
     * @return array<string, mixed>
     */
    public function paginatedPublished(int $page, int $perPage = 9, array $filters = []): array;

    /**
     * Categories and public tag counts for the homepage.
     *
     * Counts include only posts that are already public.
     *
     * @return array{
     *     categories: list<array{name: string, slug: string}>,
     *     tags: list<array{name: string, slug: string, posts_count: int, posts_count_label: string}>
     * }
     */
    public function homepageTaxonomy(): array;

    /**
     * Existing category and tag names for the post form.
     *
     * @return array{categories: list<string>, tags: list<string>}
     */
    public function taxonomySuggestions(): array;

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

    /**
     * Statuses the actor may set on this post. A new post uses the create list.
     *
     * @return list<array{value: string, label: string}>
     */
    public function allowedStatuses(User $actor, ?Post $post = null): array;

    /**
     * Publish a scheduled post once its time has arrived.
     *
     * When an expected timestamp is given, a changed publish time makes this a no-op.
     */
    public function publishIfDue(int $postId, ?string $expectedPublishedAt = null): bool;

    public function restoreRevision(Post $post, User $actor, int $revisionId): Post;
}
