import { Head, Link } from '@inertiajs/react';
import Heading from '@/components/heading';
import VideoForm from '@/components/video-form';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';

export default function Create() {
    return (
        <>
            <Head title="New video" />
            <div className="mx-auto flex w-full max-w-3xl flex-1 flex-col gap-6 p-4">
                <div className="flex items-start justify-between gap-4">
                    <Heading
                        title="New video"
                        description="A caption and a file. The video is public as soon as you save it."
                    />
                    <Button variant="outline" asChild>
                        <Link href="/admin/videos">Cancel</Link>
                    </Button>
                </div>
                <VideoForm />
            </div>
        </>
    );
}

Create.layout = {
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
            title: 'New video',
            href: '/admin/videos/create',
        },
    ],
};
