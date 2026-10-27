import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import CommentList from '@/components/comment-list';
import PostReactions from '@/components/post-reactions';
import PostTaxonomy from '@/components/post-taxonomy';
import { formatPostDate } from '@/lib/dates';
import { home } from '@/routes';
import type { CommentSummary, Paginated, PostDetail } from '@/types/blog';

export default function Show({
    post,
    comments,
    can_comment,
}: {
    post: PostDetail;
    comments: Paginated<CommentSummary>;
    can_comment: boolean;
}) {
    return (
        <>
            <Head title={post.title} />
            <article className="mx-auto max-w-3xl space-y-8">
                <div className="space-y-4">
                    <Link
                        href={home()}
                        className="inline-flex items-center gap-1.5 text-sm text-muted-foreground hover:text-foreground"
                    >
                        <ArrowLeft className="size-4" />
                        Blog
                    </Link>
                    {post.image_url ? (
                        <img
                            src={post.image_url}
                            alt=""
                            className="max-h-[32rem] w-full rounded-lg object-cover"
                        />
                    ) : null}
                    <h1 className="text-3xl font-semibold tracking-tight sm:text-4xl">
                        {post.title}
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        {post.author.name} ·{' '}
                        <time dateTime={post.published_at ?? undefined}>
                            {formatPostDate(post.published_at)}
                        </time>
                    </p>
                    <PostTaxonomy category={post.category} tags={post.tags} />
                    <PostReactions
                        slug={post.slug}
                        likesCount={post.likes_count}
                        dislikesCount={post.dislikes_count}
                        userReaction={post.user_reaction}
                    />
                    {post.excerpt ? (
                        <p className="text-lg text-muted-foreground">
                            {post.excerpt}
                        </p>
                    ) : null}
                </div>
                <div className="border-t pt-8 text-base leading-7 whitespace-pre-wrap break-words">
                    {post.body}
                </div>
                <CommentList
                    storeUrl={`/posts/${post.slug}/comments`}
                    comments={comments}
                    canComment={can_comment}
                />
            </article>
        </>
    );
}
