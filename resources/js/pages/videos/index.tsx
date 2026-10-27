import { Head, Link } from '@inertiajs/react';
import Pagination from '@/components/pagination';
import { formatPostDate } from '@/lib/dates';
import type { Paginated, VideoSummary } from '@/types/blog';

export default function Index({ videos }: { videos: Paginated<VideoSummary> }) {
    return (
        <>
            <Head title="Videos" />
            <div className="mx-auto w-full max-w-3xl space-y-6">
                <h1 className="text-2xl font-semibold tracking-tight">Videos</h1>
                {videos.data.length === 0 ? (
                    <p className="text-muted-foreground">No videos yet.</p>
                ) : (
                    <ul className="divide-y">
                        {videos.data.map((video) => (
                            <li key={video.id} className="py-6">
                                <Link
                                    href={`/videos/${video.slug}`}
                                    className="text-xl font-semibold underline-offset-4 hover:underline"
                                >
                                    {video.caption}
                                </Link>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    {video.author?.name ?? 'Unknown'} ·{' '}
                                    <time dateTime={video.created_at ?? undefined}>
                                        {formatPostDate(video.created_at)}
                                    </time>
                                </p>
                            </li>
                        ))}
                    </ul>
                )}
                <Pagination links={videos.links} lastPage={videos.last_page} />
            </div>
        </>
    );
}
