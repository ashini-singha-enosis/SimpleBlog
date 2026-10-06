<?php

use App\Enums\PostStatus;
use App\Models\Post;
use App\Models\User;
use App\Services\PostService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function servicePostPayload(array $overrides = []): array
{
    return array_merge([
        'title' => 'Hello World',
        'excerpt' => 'A short excerpt',
        'body' => 'This is a long enough body for the post.',
        'status' => PostStatus::Draft->value,
        'published_at' => null,
    ], $overrides);
}

test('appends a numeric suffix when the slug is already used', function () {
    $author = User::factory()->create();
    $posts = app(PostService::class);

    $first = $posts->create($author, servicePostPayload());
    $second = $posts->create($author, servicePostPayload());

    expect($first->slug)->toBe('hello-world')
        ->and($second->slug)->toBe('hello-world-2');
});

test('does not reuse a slug that belongs to a soft deleted post', function () {
    $author = User::factory()->create();
    $posts = app(PostService::class);

    $first = $posts->create($author, servicePostPayload(['title' => 'Same Title']));
    $posts->delete($first, $author);

    $second = $posts->create($author, servicePostPayload(['title' => 'Same Title']));

    expect($second->slug)->toBe('same-title-2')
        ->and(Post::withTrashed()->where('slug', 'same-title')->exists())->toBeTrue();
});

test('keeps the slug when the title does not change', function () {
    $author = User::factory()->create();
    $posts = app(PostService::class);
    $post = $posts->create($author, servicePostPayload(['title' => 'Keep Slug']));

    $updated = $posts->update($post, $author, servicePostPayload([
        'title' => 'Keep Slug',
        'body' => 'This body has been edited and is still long enough.',
    ]));

    expect($updated->slug)->toBe('keep-slug');
});

test('changes the slug when the title changes', function () {
    $author = User::factory()->create();
    $posts = app(PostService::class);
    $post = $posts->create($author, servicePostPayload(['title' => 'Old Title']));

    $updated = $posts->update($post, $author, servicePostPayload([
        'title' => 'New Title',
    ]));

    expect($updated->slug)->toBe('new-title');
});

test('sets published_at when a post is published without a date', function () {
    $author = User::factory()->create();
    $post = app(PostService::class)->create($author, servicePostPayload([
        'status' => PostStatus::Published->value,
        'published_at' => null,
    ]));

    expect($post->status)->toBe(PostStatus::Published)
        ->and($post->published_at)->not->toBeNull();
});

test('keeps an explicit published_at value', function () {
    $author = User::factory()->create();
    $publishedAt = now()->subDays(3)->startOfMinute();

    $post = app(PostService::class)->create($author, servicePostPayload([
        'status' => PostStatus::Published->value,
        'published_at' => $publishedAt->toDateTimeString(),
    ]));

    expect($post->published_at?->equalTo($publishedAt))->toBeTrue();
});

test('does not assign published_at while a post stays a draft', function () {
    $author = User::factory()->create();
    $post = app(PostService::class)->create($author, servicePostPayload());

    expect($post->status)->toBe(PostStatus::Draft)
        ->and($post->published_at)->toBeNull();
});
