<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Inertia\Inertia;
use Inertia\Response;

class PostController extends Controller
{
    /**
     * Show one publicly visible post.
     */
    public function show(Post $post): Response
    {
        abort_unless($post->isPubliclyVisible(), 404);

        $post->load('author:id,name');

        return Inertia::render('posts/show', [
            'post' => [
                'title' => $post->title,
                'excerpt' => $post->excerpt,
                'body' => $post->body,
                'published_at' => $post->published_at?->toIso8601String(),
                'author' => [
                    'name' => $post->author->name,
                ],
            ],
        ]);
    }
}
