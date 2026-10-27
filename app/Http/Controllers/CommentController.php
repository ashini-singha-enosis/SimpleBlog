<?php

namespace App\Http\Controllers;

use App\Contracts\CommentServiceInterface;
use App\Http\Requests\StoreCommentRequest;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Video;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    /**
     * Store a comment on a public post.
     */
    public function storeForPost(StoreCommentRequest $request, Post $post, CommentServiceInterface $comments): RedirectResponse
    {
        abort_unless($post->isPubliclyVisible(), 404);

        $user = $request->user();

        if ($user === null) {
            abort(403);
        }

        $comments->create($user, $post, (string) $request->validated('body'));

        return back();
    }

    /**
     * Store a comment on a public video.
     */
    public function storeForVideo(StoreCommentRequest $request, Video $video, CommentServiceInterface $comments): RedirectResponse
    {
        abort_unless($video->isPubliclyVisible(), 404);

        $user = $request->user();

        if ($user === null) {
            abort(403);
        }

        $comments->create($user, $video, (string) $request->validated('body'));

        return back();
    }

    /**
     * Delete a comment.
     */
    public function destroy(Request $request, Comment $comment, CommentServiceInterface $comments): RedirectResponse
    {
        $user = $request->user();

        if ($user === null) {
            abort(403);
        }

        $comments->delete($comment, $user);

        return back();
    }
}
