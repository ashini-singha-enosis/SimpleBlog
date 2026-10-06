import { Link } from '@inertiajs/react';
import { formatPostDate } from '@/lib/dates';
import { show } from '@/routes/posts';
import type { PostSummary } from '@/types/blog';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

export default function PostCard({ post }: { post: PostSummary }) {
    return (
        <Card className="h-full transition-colors hover:border-primary/40">
            <CardHeader>
                <CardDescription>
                    {post.author?.name ?? 'Unknown'} ·{' '}
                    <time dateTime={post.published_at ?? undefined}>
                        {formatPostDate(post.published_at)}
                    </time>
                </CardDescription>
                <CardTitle className="text-xl leading-snug">
                    <Link
                        href={show(post.slug)}
                        className="underline-offset-4 hover:underline"
                    >
                        {post.title}
                    </Link>
                </CardTitle>
            </CardHeader>
            {post.excerpt ? (
                <CardContent>
                    <p className="line-clamp-3 text-sm text-muted-foreground">
                        {post.excerpt}
                    </p>
                </CardContent>
            ) : null}
        </Card>
    );
}
