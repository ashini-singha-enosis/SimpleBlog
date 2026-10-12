import { Link } from '@inertiajs/react';
import PostTaxonomy from '@/components/post-taxonomy';
import { formatPostDate } from '@/lib/dates';
import { show } from '@/routes/posts';
import type { PostSummary } from '@/types/blog';

export default function PostCard({ post }: { post: PostSummary }) {
    return (
        <article className="space-y-3 border-b py-8">
            {post.image_card_url || post.image_url ? (
                <img
                    src={post.image_card_url || post.image_url || ''}
                    alt=""
                    className="max-h-96 w-full rounded-lg object-cover"
                />
            ) : null}
            <p className="text-sm text-muted-foreground">
                {post.author?.name ?? 'Unknown'} ·{' '}
                <time dateTime={post.published_at ?? undefined}>
                    {formatPostDate(post.published_at)}
                </time>
            </p>
            <h2 className="text-2xl font-semibold tracking-tight">
                <Link
                    href={show(post.slug)}
                    className="underline-offset-4 hover:underline"
                >
                    {post.title}
                </Link>
            </h2>
            {post.excerpt ? (
                <p className="max-w-3xl text-muted-foreground">{post.excerpt}</p>
            ) : null}
            <PostTaxonomy category={post.category} tags={post.tags} />
        </article>
    );
}
