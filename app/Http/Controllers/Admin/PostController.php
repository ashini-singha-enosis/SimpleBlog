<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\PostServiceInterface;
use App\Exceptions\StalePostException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Models\Post;
use App\Models\PostRevision;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PostController extends Controller
{
    /**
     * List posts the current user is allowed to manage.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        if ($user === null) {
            abort(403);
        }

        $posts = Post::query()
            ->with('author:id,name')
            ->latest('updated_at');

        if (! $user->is_admin) {
            $posts->where('user_id', $user->id);
        }

        return Inertia::render('admin/posts/index', [
            'posts' => $posts
                ->paginate(10, ['id', 'user_id', 'title', 'slug', 'status', 'published_at', 'updated_at'])
                ->withQueryString(),
        ]);
    }

    /**
     * Show the create post form.
     */
    public function create(Request $request, PostServiceInterface $posts): Response
    {
        $user = $request->user();

        if ($user === null) {
            abort(403);
        }

        Gate::authorize('create', Post::class);

        return Inertia::render('admin/posts/create', [
            ...$posts->taxonomySuggestions(),
            'statuses' => $posts->allowedStatuses($user),
        ]);
    }

    /**
     * Store a new post.
     */
    public function store(StorePostRequest $request, PostServiceInterface $posts): RedirectResponse
    {
        $user = $request->user();

        if ($user === null) {
            abort(403);
        }

        $posts->create($user, $request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Post created.',
        ]);

        return to_route('admin.posts.index');
    }

    /**
     * Show the edit post form.
     */
    public function edit(Request $request, Post $post, PostServiceInterface $posts): Response
    {
        Gate::authorize('update', $post);

        $user = $request->user();

        if ($user === null) {
            abort(403);
        }

        $post->load(['category:id,name', 'tags:id,name']);

        return Inertia::render('admin/posts/edit', [
            'post' => [
                'id' => $post->id,
                'title' => $post->title,
                'excerpt' => $post->excerpt,
                'body' => $post->body,
                'status' => $post->status->value,
                'published_at' => $post->published_at?->toIso8601String(),
                'image_url' => $post->image_url,
                'lock_version' => $post->lock_version,
                'category' => $post->category?->name,
                'tags' => $post->tags->pluck('name')->values()->all(),
            ],
            ...$posts->taxonomySuggestions(),
            'statuses' => $posts->allowedStatuses($user, $post),
            'revisions' => $post->revisions()
                ->with('author:id,name')
                ->latest()
                ->limit(10)
                ->get()
                ->map(fn (PostRevision $revision): array => [
                    'id' => $revision->id,
                    'status' => $revision->status->value,
                    'lock_version' => $revision->lock_version,
                    'created_at' => $revision->created_at?->toIso8601String(),
                    'author' => $revision->author?->name ?? 'Unknown',
                ])
                ->all(),
        ]);
    }

    /**
     * Update an existing post.
     */
    public function update(UpdatePostRequest $request, Post $post, PostServiceInterface $posts): RedirectResponse
    {
        $user = $request->user();

        if ($user === null) {
            abort(403);
        }

        try {
            $posts->update($post, $user, $request->validated());
        } catch (StalePostException $exception) {
            abort(409, $exception->getMessage());
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Post updated.',
        ]);

        return to_route('admin.posts.index');
    }

    /**
     * Copy a saved revision onto the post. The copy becomes a new revision.
     */
    public function restoreRevision(Request $request, Post $post, PostRevision $revision, PostServiceInterface $posts): RedirectResponse
    {
        Gate::authorize('update', $post);
        abort_unless($revision->post_id === $post->id, 404);

        $user = $request->user();

        if ($user === null) {
            abort(403);
        }

        try {
            $posts->restoreRevision($post, $user, $revision->id);
        } catch (StalePostException $exception) {
            abort(409, $exception->getMessage());
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Revision restored.',
        ]);

        return to_route('admin.posts.edit', $post);
    }

    /**
     * Soft delete a post.
     */
    public function destroy(Request $request, Post $post, PostServiceInterface $posts): RedirectResponse
    {
        Gate::authorize('delete', $post);

        $user = $request->user();

        if ($user === null) {
            abort(403);
        }

        $posts->delete($post, $user);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Post deleted.',
        ]);

        return to_route('admin.posts.index');
    }
}
