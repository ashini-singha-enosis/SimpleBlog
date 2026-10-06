<?php

namespace App\Http\Controllers;

use App\Enums\PostStatus;
use App\Models\Post;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Show the signed-in user's details and post counts.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        if ($user === null) {
            abort(403);
        }

        $posts = Post::query()->where('user_id', $user->id);

        return Inertia::render('dashboard', [
            'stats' => [
                'total' => (clone $posts)->count(),
                'published' => (clone $posts)->published()->count(),
                'drafts' => (clone $posts)->where('status', PostStatus::Draft)->count(),
            ],
        ]);
    }
}
