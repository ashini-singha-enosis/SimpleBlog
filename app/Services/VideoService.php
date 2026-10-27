<?php

namespace App\Services;

use App\Contracts\VideoServiceInterface;
use App\Models\User;
use App\Models\Video;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class VideoService implements VideoServiceInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $author, array $data): Video
    {
        $caption = $this->caption($data);
        $attributes = [
            'user_id' => $author->id,
            'caption' => $caption,
            'slug' => $this->uniqueSlug($caption),
            'video_path' => null,
        ];

        try {
            $video = Video::query()->create($attributes);
        } catch (UniqueConstraintViolationException) {
            $attributes['slug'] = $this->uniqueSlug($caption);
            $video = Video::query()->create($attributes);
        }

        $this->syncVideo($video, $data);

        return $video->refresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Video $video, User $actor, array $data): Video
    {
        $caption = $this->caption($data);
        $video->caption = $caption;

        if ($video->isDirty('caption')) {
            $video->slug = $this->uniqueSlug($caption, $video->id);
        }

        $video->save();
        $this->syncVideo($video, $data);

        Log::info('Video updated', [
            'video_id' => $video->id,
            'user_id' => $actor->id,
        ]);

        return $video->refresh();
    }

    public function delete(Video $video, User $actor): void
    {
        $this->deleteVideoFile($video->video_path);
        $video->comments()->delete();
        $video->video_path = null;
        $video->save();
        $video->delete();

        Log::info('Video deleted', [
            'video_id' => $video->id,
            'user_id' => $actor->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function syncVideo(Video $video, array $data): void
    {
        $file = $data['video'] ?? null;

        if ($file instanceof UploadedFile) {
            $previous = $video->video_path;
            $path = $this->storeVideo($video, $file);
            $video->video_path = $path;
            $video->save();

            if (is_string($previous) && $previous !== '' && $previous !== $path) {
                $this->deleteVideoFile($previous);
            }

            return;
        }

        $remove = filter_var($data['remove_video'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if ($remove && is_string($video->video_path) && $video->video_path !== '') {
            $this->deleteVideoFile($video->video_path);
            $video->video_path = null;
            $video->save();
        }
    }

    private function storeVideo(Video $video, UploadedFile $file): string
    {
        $extension = $file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'mp4';
        $path = $file->storeAs(
            'videos/'.$video->id,
            Str::uuid()->toString().'.'.$extension,
            'public',
        );

        if (! is_string($path)) {
            throw new RuntimeException('The video could not be stored.');
        }

        return $path;
    }

    private function deleteVideoFile(?string $path): void
    {
        if (! is_string($path) || $path === '' || str_contains($path, '..')) {
            return;
        }

        Storage::disk('public')->delete($path);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function caption(array $data): string
    {
        $caption = $data['caption'] ?? '';

        return is_string($caption) ? trim($caption) : '';
    }

    private function uniqueSlug(string $caption, ?int $ignoreId = null): string
    {
        $base = Str::slug($caption);

        if ($base === '') {
            $base = 'video';
        }

        $slug = $base;
        $suffix = 2;

        while ($this->slugExists($slug, $ignoreId)) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    private function slugExists(string $slug, ?int $ignoreId): bool
    {
        $query = Video::withTrashed()->where('slug', $slug);

        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }

        return $query->exists();
    }
}
