<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Models\Post;
use App\Services\PostService;
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
    public function create(): Response
    {
        Gate::authorize('create', Post::class);

        return Inertia::render('admin/posts/create');
    }

    /**
     * Store a new post.
     */
    public function store(StorePostRequest $request, PostService $posts): RedirectResponse
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
    public function edit(Post $post): Response
    {
        Gate::authorize('update', $post);

        return Inertia::render('admin/posts/edit', [
            'post' => [
                'id' => $post->id,
                'title' => $post->title,
                'excerpt' => $post->excerpt,
                'body' => $post->body,
                'status' => $post->status->value,
                'published_at' => $post->published_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * Update an existing post.
     */
    public function update(UpdatePostRequest $request, Post $post, PostService $posts): RedirectResponse
    {
        $user = $request->user();

        if ($user === null) {
            abort(403);
        }

        $posts->update($post, $user, $request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Post updated.',
        ]);

        return to_route('admin.posts.index');
    }

    /**
     * Soft delete a post.
     */
    public function destroy(Request $request, Post $post, PostService $posts): RedirectResponse
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
