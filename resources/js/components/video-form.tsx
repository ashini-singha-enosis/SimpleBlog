import { Form } from '@inertiajs/react';
import { useRef, useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { VideoFormData } from '@/types/blog';

export default function VideoForm({ video }: { video?: VideoFormData }) {
    const [preview, setPreview] = useState<string | null>(
        video?.video_url ?? null,
    );
    const [removeVideo, setRemoveVideo] = useState(false);
    const [fileInputKey, setFileInputKey] = useState(0);
    const previewRef = useRef<string | null>(video?.video_url ?? null);

    function revokePreview(): void {
        if (previewRef.current && previewRef.current.startsWith('blob:')) {
            URL.revokeObjectURL(previewRef.current);
        }
    }

    return (
        <Form
            action={video ? `/admin/videos/${video.id}` : '/admin/videos'}
            method={video ? 'put' : 'post'}
            encType="multipart/form-data"
            transform={(data) => ({
                ...data,
                remove_video: removeVideo ? 1 : 0,
            })}
            className="space-y-6"
        >
            {({ errors, processing }) => (
                <>
                    <div className="grid gap-2">
                        <Label htmlFor="caption">Caption</Label>
                        <Input
                            id="caption"
                            name="caption"
                            defaultValue={video?.caption ?? ''}
                            required
                            maxLength={200}
                            aria-invalid={errors.caption ? true : undefined}
                        />
                        <InputError message={errors.caption} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="video">Video</Label>
                        {preview ? (
                            <video
                                src={preview}
                                controls
                                className="max-h-80 w-full rounded-lg bg-muted"
                            />
                        ) : (
                            <div className="h-40 w-full rounded-lg bg-muted" />
                        )}
                        <Input
                            key={fileInputKey}
                            id="video"
                            name="video"
                            type="file"
                            accept="video/mp4,video/webm"
                            required={video === undefined}
                            onChange={(event) => {
                                const file = event.target.files?.[0];
                                revokePreview();

                                if (!file) {
                                    previewRef.current = video?.video_url ?? null;
                                    setPreview(video?.video_url ?? null);
                                    setRemoveVideo(false);

                                    return;
                                }

                                const url = URL.createObjectURL(file);
                                previewRef.current = url;
                                setPreview(url);
                                setRemoveVideo(false);
                            }}
                            aria-invalid={errors.video ? true : undefined}
                        />
                        <p className="text-sm text-muted-foreground">
                            MP4 or WEBM up to 20 MB. Raise PHP
                            upload_max_filesize if a valid file is rejected.
                        </p>
                        <InputError message={errors.video} />
                        {preview ? (
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => {
                                    revokePreview();
                                    previewRef.current = null;
                                    setPreview(null);
                                    setRemoveVideo(true);
                                    setFileInputKey((key) => key + 1);
                                }}
                            >
                                Remove video
                            </Button>
                        ) : null}
                    </div>

                    <Button type="submit" disabled={processing}>
                        {processing
                            ? 'Saving...'
                            : video
                              ? 'Save video'
                              : 'Publish video'}
                    </Button>
                </>
            )}
        </Form>
    );
}
