<?php

namespace App\Http\Controllers;

use App\Contracts\CommentServiceInterface;
use App\Contracts\PostServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    /**
     * Show the paginated list of published posts.
     */
    public function index(Request $request, PostServiceInterface $posts, CommentServiceInterface $comments): Response
    {
        $user = $request->user();
        $authorId = null;

        if ($user !== null && $request->boolean('mine')) {
            $authorId = $user->id;
        }

        $q = Str::limit($request->string('q')->trim()->toString(), 100, '');
        $category = Str::limit($request->string('category')->trim()->toString(), 80, '');
        $tag = Str::limit($request->string('tag')->trim()->toString(), 80, '');

        $page = $posts->paginatedPublished($request->integer('page', 1), filters: [
            'author_id' => $authorId,
            'q' => $q,
            'category' => $category,
            'tag' => $tag,
        ]);

        return Inertia::render('home', [
            'posts' => $comments->withCounts($posts->withReactionSummaries($page, $user?->id)),
            'taxonomy' => $posts->homepageTaxonomy(),
            'filters' => [
                'q' => $q,
                'category' => $category,
                'tag' => $tag,
                'mine' => $authorId !== null,
            ],
        ]);
    }
}
