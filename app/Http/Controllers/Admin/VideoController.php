<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\VideoServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVideoRequest;
use App\Http\Requests\UpdateVideoRequest;
use App\Models\Video;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VideoController extends Controller
{
    /**
     * List videos the current user is allowed to manage.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        if ($user === null) {
            abort(403);
        }

        $videos = Video::query()
            ->with('author:id,name')
            ->latest('updated_at');

        if (! $user->is_admin) {
            $videos->where('user_id', $user->id);
        }

        return Inertia::render('admin/videos/index', [
            'videos' => $videos
                ->paginate(10, ['id', 'user_id', 'caption', 'slug', 'video_path', 'updated_at', 'created_at'])
                ->withQueryString(),
        ]);
    }

    /**
     * Show the create video form.
     */
    public function create(): Response
    {
        return Inertia::render('admin/videos/create');
    }

    /**
     * Store a new video.
     */
    public function store(StoreVideoRequest $request, VideoServiceInterface $videos): RedirectResponse
    {
        $user = $request->user();

        if ($user === null) {
            abort(403);
        }

        $videos->create($user, $request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Video created.',
        ]);

        return to_route('admin.videos.index');
    }

    /**
     * Show the edit video form.
     */
    public function edit(Video $video): Response
    {
        return Inertia::render('admin/videos/edit', [
            'video' => [
                'id' => $video->id,
                'caption' => $video->caption,
                'slug' => $video->slug,
                'video_url' => $video->video_url,
            ],
        ]);
    }

    /**
     * Update an existing video.
     */
    public function update(UpdateVideoRequest $request, Video $video, VideoServiceInterface $videos): RedirectResponse
    {
        $user = $request->user();

        if ($user === null) {
            abort(403);
        }

        $videos->update($video, $user, $request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Video updated.',
        ]);

        return to_route('admin.videos.index');
    }

    /**
     * Soft delete a video and remove its file.
     */
    public function destroy(Request $request, Video $video, VideoServiceInterface $videos): RedirectResponse
    {
        $user = $request->user();

        if ($user === null) {
            abort(403);
        }

        $videos->delete($video, $user);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Video deleted.',
        ]);

        return to_route('admin.videos.index');
    }
}
