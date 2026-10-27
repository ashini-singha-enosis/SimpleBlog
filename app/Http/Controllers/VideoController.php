<?php

namespace App\Http\Controllers;

use App\Contracts\CommentServiceInterface;
use App\Models\Video;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VideoController extends Controller
{
    /**
     * Show the public list of videos.
     */
    public function index(): Response
    {
        $videos = Video::query()
            ->with('author:id,name')
            ->latest()
            ->paginate(12, ['id', 'user_id', 'caption', 'slug', 'video_path', 'created_at']);

        return Inertia::render('videos/index', [
            'videos' => $videos,
        ]);
    }

    /**
     * Show one public video and its comments.
     */
    public function show(Request $request, Video $video, CommentServiceInterface $comments): Response
    {
        abort_unless($video->isPubliclyVisible(), 404);

        $video->load('author:id,name');

        return Inertia::render('videos/show', [
            'video' => [
                'slug' => $video->slug,
                'caption' => $video->caption,
                'video_url' => $video->video_url,
                'created_at' => $video->created_at?->toIso8601String(),
                'author' => [
                    'name' => $video->author?->name ?? 'Unknown',
                ],
            ],
            'comments' => $comments->forCommentable(
                $video,
                $request->user(),
                $request->integer('comments', 1),
            ),
            'can_comment' => $request->user()?->hasVerifiedEmail() === true,
        ]);
    }
}
