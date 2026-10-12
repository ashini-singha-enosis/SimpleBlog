<?php

namespace App\Jobs;

use App\Contracts\PostServiceInterface;
use App\Models\Post;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProcessPostImage implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public int $postId,
        public string $originalPath,
        public ?string $requestId = null,
    ) {}

    public function handle(PostServiceInterface $posts): void
    {
        if ($this->requestId !== null) {
            Log::shareContext(['request_id' => $this->requestId]);
        }

        if (! function_exists('imagecreatetruecolor')) {
            Log::warning('GD is missing, so the cover was left at its original size.', [
                'post_id' => $this->postId,
            ]);

            return;
        }

        $post = Post::query()->find($this->postId);

        if ($post === null || $post->image_path !== $this->originalPath) {
            return;
        }

        $disk = Storage::disk('public');

        if (! $disk->exists($this->originalPath)) {
            return;
        }

        $source = $disk->path($this->originalPath);
        $card = $this->resize($source, 'posts/'.$post->id.'/card', 800);
        $full = $this->resize($source, 'posts/'.$post->id.'/full', 1600);

        if ($card === null && $full === null) {
            return;
        }

        Post::query()
            ->whereKey($post->id)
            ->where('image_path', $this->originalPath)
            ->update([
                'image_card_path' => $card,
                'image_full_path' => $full,
            ]);

        $posts->invalidatePublishedCache();
    }

    private function resize(string $source, string $directory, int $maxWidth): ?string
    {
        $info = getimagesize($source);

        if ($info === false) {
            return null;
        }

        [$width, $height, $type] = $info;
        $image = $this->open($source, $type);

        if ($image === false) {
            return null;
        }

        $targetWidth = max(1, min($width, $maxWidth));
        $targetHeight = max(1, (int) round($height * ($targetWidth / $width)));
        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);

        if ($canvas === false) {
            imagedestroy($image);

            return null;
        }

        if (in_array($type, [IMAGETYPE_PNG, IMAGETYPE_WEBP, IMAGETYPE_GIF], true)) {
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
            if ($transparent !== false) {
                imagefilledrectangle($canvas, 0, 0, $targetWidth, $targetHeight, $transparent);
            }
        }

        imagecopyresampled($canvas, $image, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

        $extension = match ($type) {
            IMAGETYPE_PNG => 'png',
            IMAGETYPE_GIF => 'gif',
            IMAGETYPE_WEBP => 'webp',
            default => 'jpg',
        };
        $relative = $directory.'/'.Str::uuid()->toString().'.'.$extension;
        Storage::disk('public')->makeDirectory($directory);
        $absolute = Storage::disk('public')->path($relative);
        $saved = match ($extension) {
            'png' => imagepng($canvas, $absolute),
            'gif' => imagegif($canvas, $absolute),
            'webp' => function_exists('imagewebp') && imagewebp($canvas, $absolute, 85),
            default => imagejpeg($canvas, $absolute, 85),
        };

        imagedestroy($image);
        imagedestroy($canvas);

        return $saved ? $relative : null;
    }

    /**
     * @return \GdImage|false
     */
    private function open(string $source, int $type): \GdImage|false
    {
        return match ($type) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($source),
            IMAGETYPE_PNG => imagecreatefrompng($source),
            IMAGETYPE_GIF => imagecreatefromgif($source),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? imagecreatefromwebp($source) : false,
            default => false,
        };
    }
}
