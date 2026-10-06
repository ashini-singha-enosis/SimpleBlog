<?php

use App\Models\Post;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    cache()->flush();
    $this->withoutVite();
});

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function blogPayload(array $overrides = []): array
{
    return array_merge([
        'title' => 'A public post',
        'excerpt' => 'Short excerpt',
        'body' => 'This is a long enough body for the post.',
        'status' => 'published',
    ], $overrides);
}

test('homepage loads', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('home'));
});

test('published posts appear on the homepage', function () {
    $post = Post::factory()->published()->create([
        'title' => 'Visible story',
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('home')
            ->has('posts.data', 1)
            ->where('posts.data.0.title', 'Visible story')
            ->where('posts.data.0.slug', $post->slug)
            ->missing('posts.data.0.body')
        );
});

test('draft posts do not appear publicly', function () {
    Post::factory()->draft()->create(['title' => 'Hidden draft']);
    Post::factory()->published()->create(['title' => 'Visible story']);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('posts.data', 1)
            ->where('posts.data.0.title', 'Visible story')
        );
});

test('posts with a future published_at do not appear publicly', function () {
    Post::factory()->scheduled()->create(['title' => 'Not yet']);
    Post::factory()->published()->create(['title' => 'Visible story']);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('posts.data', 1)
            ->where('posts.data.0.title', 'Visible story')
        );
});

test('post details page shows a published post', function () {
    $post = Post::factory()->published()->create([
        'title' => 'Visible story',
        'body' => 'This is the full body of the published post.',
    ]);

    $this->get(route('posts.show', $post))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('posts/show')
            ->where('post.title', 'Visible story')
            ->where('post.body', 'This is the full body of the published post.')
        );
});

test('draft future and missing posts are not found', function () {
    $draft = Post::factory()->draft()->create();
    $scheduled = Post::factory()->scheduled()->create();
    $deleted = Post::factory()->published()->create();
    $deleted->delete();

    $this->get(route('posts.show', $draft))->assertNotFound();
    $this->get(route('posts.show', $scheduled))->assertNotFound();
    $this->get(route('posts.show', $deleted))->assertNotFound();
    $this->get('/posts/missing-post')->assertNotFound();
});

test('guests cannot manage posts', function () {
    $post = Post::factory()->published()->create();

    $this->get(route('admin.posts.index'))->assertRedirect(route('login'));
    $this->get(route('admin.posts.create'))->assertRedirect(route('login'));
    $this->post(route('admin.posts.store'), blogPayload())->assertRedirect(route('login'));
    $this->put(route('admin.posts.update', $post), blogPayload())->assertRedirect(route('login'));
    $this->delete(route('admin.posts.destroy', $post))->assertRedirect(route('login'));
});

test('authenticated user can create a post', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('admin.posts.store'), blogPayload([
            'title' => 'My First Post',
        ]))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.posts.index'));

    $this->assertDatabaseHas('posts', [
        'user_id' => $user->id,
        'title' => 'My First Post',
        'slug' => 'my-first-post',
        'status' => 'published',
    ]);
});

test('validation failures are handled', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('admin.posts.create'))
        ->post(route('admin.posts.store'), [
            'title' => '',
            'excerpt' => str_repeat('a', 501),
            'body' => 'too short',
            'status' => 'archived',
            'published_at' => 'not-a-date',
        ])
        ->assertRedirect(route('admin.posts.create'))
        ->assertSessionHasErrors(['title', 'excerpt', 'body', 'status', 'published_at']);
});

test('an author can update their own post', function () {
    $user = User::factory()->create();
    $post = Post::factory()->draft()->for($user, 'author')->create([
        'title' => 'Old Title',
    ]);

    $this->actingAs($user)
        ->put(route('admin.posts.update', $post), blogPayload([
            'title' => 'New Title',
            'status' => 'draft',
        ]))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.posts.index'));

    expect($post->refresh()->slug)->toBe('new-title')
        ->and($post->title)->toBe('New Title');
});

test('another user cannot update or delete a post', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $post = Post::factory()->draft()->for($owner, 'author')->create();

    $this->actingAs($stranger)
        ->put(route('admin.posts.update', $post), blogPayload(['status' => 'draft']))
        ->assertForbidden();

    $this->actingAs($stranger)
        ->delete(route('admin.posts.destroy', $post))
        ->assertForbidden();

    $this->assertNotSoftDeleted($post);
});

test('an admin can update another authors post', function () {
    $owner = User::factory()->create();
    $admin = User::factory()->admin()->create();
    $post = Post::factory()->draft()->for($owner, 'author')->create([
        'title' => 'Owner draft',
    ]);

    $this->actingAs($admin)
        ->put(route('admin.posts.update', $post), blogPayload([
            'title' => 'Edited by admin',
            'status' => 'draft',
        ]))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.posts.index'));

    expect($post->refresh()->title)->toBe('Edited by admin');
});

test('an author can soft delete a post', function () {
    $user = User::factory()->create();
    $post = Post::factory()->published()->for($user, 'author')->create([
        'title' => 'Going away',
    ]);

    $this->actingAs($user)
        ->delete(route('admin.posts.destroy', $post))
        ->assertRedirect(route('admin.posts.index'));

    $this->assertSoftDeleted($post);
    $this->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page->has('posts.data', 0));
});

test('duplicate titles receive unique slugs', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('admin.posts.store'), blogPayload([
        'title' => 'Same Title',
        'status' => 'draft',
    ]))->assertSessionHasNoErrors();

    $this->actingAs($user)->post(route('admin.posts.store'), blogPayload([
        'title' => 'Same Title',
        'status' => 'draft',
    ]))->assertSessionHasNoErrors();

    expect(Post::query()->whereIn('slug', ['same-title', 'same-title-2'])->count())->toBe(2);
});
