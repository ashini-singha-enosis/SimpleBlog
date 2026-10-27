import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import CommentList from '@/components/comment-list';
import { formatPostDate } from '@/lib/dates';
import type { CommentSummary, Paginated, VideoDetail } from '@/types/blog';

export default function Show({
    video,
    comments,
    can_comment,
}: {
    video: VideoDetail;
    comments: Paginated<CommentSummary>;
    can_comment: boolean;
}) {
    return (
        <>
            <Head title={video.caption} />
            <article className="mx-auto max-w-3xl space-y-8">
                <div className="space-y-4">
                    <Link
                        href="/videos"
                        className="inline-flex items-center gap-1.5 text-sm text-muted-foreground hover:text-foreground"
                    >
                        <ArrowLeft className="size-4" />
                        Videos
                    </Link>
                    <h1 className="text-3xl font-semibold tracking-tight sm:text-4xl">
                        {video.caption}
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        {video.author.name} ·{' '}
                        <time dateTime={video.created_at ?? undefined}>
                            {formatPostDate(video.created_at)}
                        </time>
                    </p>
                    {video.video_url ? (
                        <video
                            src={video.video_url}
                            controls
                            className="w-full rounded-lg bg-muted"
                        />
                    ) : (
                        <div className="flex h-48 items-center justify-center rounded-lg bg-muted text-sm text-muted-foreground">
                            No video file
                        </div>
                    )}
                </div>
                <CommentList
                    storeUrl={`/videos/${video.slug}/comments`}
                    comments={comments}
                    canComment={can_comment}
                />
            </article>
        </>
    );
}
