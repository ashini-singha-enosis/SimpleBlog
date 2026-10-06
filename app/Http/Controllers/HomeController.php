<?php

namespace App\Http\Controllers;

use App\Services\PostService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    /**
     * Show the paginated list of published posts.
     */
    public function index(Request $request, PostService $posts): Response
    {
        $user = $request->user();
        $authorId = null;

        if ($user !== null && $request->boolean('mine')) {
            $authorId = $user->id;
        }

        return Inertia::render('home', [
            'posts' => $posts->paginatedPublished(
                $request->integer('page', 1),
                authorId: $authorId,
            ),
            'filters' => [
                'mine' => $authorId !== null,
            ],
        ]);
    }
}
