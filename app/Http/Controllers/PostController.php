<?php

namespace App\Http\Controllers;

use App\Contracts\CommentServiceInterface;
use App\Contracts\PostServiceInterface;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PostController extends Controller
{
    /**
     * Show one publicly visible post.
     */
    public function show(Request $request, Post $post, PostServiceInterface $posts, CommentServiceInterface $comments): Response
    {
        abort_unless($post->isPubliclyVisible(), 404);

        $post->load([
            'author:id,name',
            'category:id,name,slug',
            'tags:id,name,slug',
        ]);

        $reactions = $posts->reactionSummary($post, $request->user()?->id);

        return Inertia::render('posts/show', [
            'post' => [
                'slug' => $post->slug,
                'title' => $post->title,
                'excerpt' => $post->excerpt,
                'body' => $post->body,
                'published_at' => $post->published_at?->toIso8601String(),
                'image_url' => $post->image_full_url ?? $post->image_url,
                'author' => [
                    'name' => $post->author->name,
                ],
                'category' => $post->category === null ? null : [
                    'name' => $post->category->name,
                    'slug' => $post->category->slug,
                ],
                'tags' => $post->tags->map(fn (Tag $tag): array => [
                    'name' => $tag->name,
                    'slug' => $tag->slug,
                ])->values()->all(),
                ...$reactions,
            ],
            'comments' => $comments->forCommentable(
                $post,
                $request->user(),
                $request->integer('comments', 1),
            ),
            'can_comment' => $request->user()?->hasVerifiedEmail() === true,
        ]);
    }
}
