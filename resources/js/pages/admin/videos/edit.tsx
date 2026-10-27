import { Head, Link } from '@inertiajs/react';
import Heading from '@/components/heading';
import VideoForm from '@/components/video-form';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';
import type { VideoFormData } from '@/types/blog';

export default function Edit({ video }: { video: VideoFormData }) {
    return (
        <>
            <Head title={`Edit ${video.caption}`} />
            <div className="mx-auto flex w-full max-w-3xl flex-1 flex-col gap-6 p-4">
                <div className="flex items-start justify-between gap-4">
                    <Heading
                        title="Edit video"
                        description="Changing the caption updates the public URL."
                    />
                    <Button variant="outline" asChild>
                        <Link href="/admin/videos">Back</Link>
                    </Button>
                </div>
                <VideoForm video={video} />
            </div>
        </>
    );
}

Edit.layout = {
    breadcrumbs: [
        {
            title: 'Account',
            href: dashboard(),
        },
        {
            title: 'Videos',
            href: '/admin/videos',
        },
        {
            title: 'Edit',
            href: '/admin/videos',
        },
    ],
};
