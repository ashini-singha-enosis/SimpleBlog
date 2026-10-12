import { Link } from '@inertiajs/react';
import { useState } from 'react';
import PostReactions from '@/components/post-reactions';
import PostTaxonomy from '@/components/post-taxonomy';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { formatPostDate } from '@/lib/dates';
import { show } from '@/routes/posts';
import type { PostSummary } from '@/types/blog';

export default function PostCard({ post }: { post: PostSummary }) {
    const [open, setOpen] = useState(false);
    const cover = post.image_card_url || post.image_url || null;
    const preview = post.image_full_url || post.image_card_url || post.image_url || null;

    return (
        <article className="space-y-3 border-b py-8">
            <button
                type="button"
                className="block w-full text-left"
                onClick={() => setOpen(true)}
            >
                {cover ? (
                    <img
                        src={cover}
                        alt=""
                        className="max-h-96 w-full rounded-lg object-cover"
                    />
                ) : (
                    <div className="h-48 w-full rounded-lg bg-muted" />
                )}
            </button>
            <p className="text-sm text-muted-foreground">
                {post.author?.name ?? 'Unknown'} ·{' '}
                <time dateTime={post.published_at ?? undefined}>
                    {formatPostDate(post.published_at)}
                </time>
            </p>
            <h2 className="text-2xl font-semibold tracking-tight">
                <button
                    type="button"
                    className="text-left underline-offset-4 hover:underline"
                    onClick={() => setOpen(true)}
                >
                    {post.title}
                </button>
            </h2>
            {post.excerpt ? (
                <p className="max-w-3xl text-muted-foreground">{post.excerpt}</p>
            ) : null}
            <PostTaxonomy category={post.category} tags={post.tags} />
            <PostReactions
                slug={post.slug}
                likesCount={post.likes_count ?? 0}
                dislikesCount={post.dislikes_count ?? 0}
                userReaction={post.user_reaction ?? null}
            />
            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent className="max-h-[85vh] overflow-y-auto sm:max-w-2xl">
                    <DialogHeader>
                        <DialogTitle>{post.title}</DialogTitle>
                    </DialogHeader>
                    {preview ? (
                        <img
                            src={preview}
                            alt=""
                            className="max-h-80 w-full rounded-lg object-cover"
                        />
                    ) : (
                        <div className="h-40 w-full rounded-lg bg-muted" />
                    )}
                    <p className="text-sm text-muted-foreground">
                        {post.author?.name ?? 'Unknown'} ·{' '}
                        <time dateTime={post.published_at ?? undefined}>
                            {formatPostDate(post.published_at)}
                        </time>
                    </p>
                    {post.excerpt ? (
                        <p className="text-muted-foreground">{post.excerpt}</p>
                    ) : null}
                    <div className="text-base leading-7 whitespace-pre-wrap break-words">
                        {post.body}
                    </div>
                    <PostReactions
                        slug={post.slug}
                        likesCount={post.likes_count ?? 0}
                        dislikesCount={post.dislikes_count ?? 0}
                        userReaction={post.user_reaction ?? null}
                    />
                    <Link
                        href={show(post.slug)}
                        className="text-sm underline-offset-4 hover:underline"
                    >
                        Open the full post
                    </Link>
                </DialogContent>
            </Dialog>
        </article>
    );
}
