import { Head, Link, usePage } from '@inertiajs/react';
import DeleteVideoButton from '@/components/delete-video-button';
import Heading from '@/components/heading';
import Pagination from '@/components/pagination';
import { Button } from '@/components/ui/button';
import { formatPostDate } from '@/lib/dates';
import { dashboard } from '@/routes';
import type { Auth } from '@/types';
import type { Paginated, VideoSummary } from '@/types/blog';

export default function Index({ videos }: { videos: Paginated<VideoSummary> }) {
    const { auth } = usePage<{ auth: Auth }>().props;
    const isAdmin = auth.user?.is_admin === true;

    return (
        <>
            <Head title="Videos" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <Heading
                        title="Videos"
                        description={
                            isAdmin
                                ? 'Every video on the site.'
                                : 'Videos you have published.'
                        }
                    />
                    <Button asChild>
                        <Link href="/admin/videos/create">New video</Link>
                    </Button>
                </div>

                {videos.data.length === 0 ? (
                    <div className="rounded-xl border border-dashed px-6 py-16 text-center">
                        <h2 className="text-lg font-medium">No videos yet</h2>
                        <p className="mt-2 text-sm text-muted-foreground">
                            Publish a caption and a file.
                        </p>
                    </div>
                ) : (
                    <ul className="divide-y rounded-xl border">
                        {videos.data.map((video) => (
                            <li
                                key={video.id}
                                className="flex flex-col gap-4 p-4 sm:flex-row sm:items-center sm:justify-between"
                            >
                                <div className="min-w-0 space-y-1">
                                    <Link
                                        href={`/admin/videos/${video.id}/edit`}
                                        className="truncate font-medium underline-offset-4 hover:underline"
                                    >
                                        {video.caption}
                                    </Link>
                                    <p className="text-sm text-muted-foreground">
                                        {isAdmin
                                            ? `${video.author?.name ?? 'Unknown'} · `
                                            : ''}
                                        {formatPostDate(
                                            video.updated_at ?? video.created_at,
                                        )}
                                    </p>
                                </div>
                                <div className="flex shrink-0 items-center gap-2">
                                    <Button variant="outline" size="sm" asChild>
                                        <Link href={`/videos/${video.slug}`}>
                                            View
                                        </Link>
                                    </Button>
                                    <Button variant="outline" size="sm" asChild>
                                        <Link
                                            href={`/admin/videos/${video.id}/edit`}
                                        >
                                            Edit
                                        </Link>
                                    </Button>
                                    <DeleteVideoButton
                                        videoId={video.id}
                                        caption={video.caption}
                                    />
                                </div>
                            </li>
                        ))}
                    </ul>
                )}

                <Pagination links={videos.links} lastPage={videos.last_page} />
            </div>
        </>
    );
}

Index.layout = {
    breadcrumbs: [
        {
            title: 'Account',
            href: dashboard(),
        },
        {
            title: 'Videos',
            href: '/admin/videos',
        },
    ],
};
