<?php

namespace App\Http\Controllers;

use App\Contracts\PostServiceInterface;
use App\Enums\ReactionType;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReactionController extends Controller
{
    /**
     * Names of people who liked or disliked a public post.
     */
    public function index(Request $request, Post $post, PostServiceInterface $posts): JsonResponse
    {
        abort_unless($post->isPubliclyVisible(), 404);

        $type = $request->string('type')->toString();
        abort_unless(in_array($type, [ReactionType::Like->value, ReactionType::Dislike->value], true), 404);

        return response()->json([
            'names' => $posts->reactionNames($post, $type),
        ]);
    }

    /**
     * Set or switch the signed-in user's reaction. The same choice again removes it.
     */
    public function store(Request $request, Post $post, PostServiceInterface $posts): RedirectResponse
    {
        abort_unless($post->isPubliclyVisible(), 404);

        $user = $request->user();

        if ($user === null) {
            abort(403);
        }

        $type = $request->validate([
            'type' => ['required', Rule::in([ReactionType::Like->value, ReactionType::Dislike->value])],
        ])['type'];

        $posts->toggleReaction($post, $user, (string) $type);

        return back();
    }

    /**
     * Remove the signed-in user's reaction.
     */
    public function destroy(Request $request, Post $post, PostServiceInterface $posts): RedirectResponse
    {
        abort_unless($post->isPubliclyVisible(), 404);

        $user = $request->user();

        if ($user === null) {
            abort(403);
        }

        $posts->clearReaction($post, $user);

        return back();
    }
}
