import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import PostTaxonomy from '@/components/post-taxonomy';
import { formatPostDate } from '@/lib/dates';
import { home } from '@/routes';
import type { PostDetail } from '@/types/blog';

export default function Show({ post }: { post: PostDetail }) {
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
                    {post.excerpt ? (
                        <p className="text-lg text-muted-foreground">
                            {post.excerpt}
                        </p>
                    ) : null}
                </div>
                <div className="border-t pt-8 text-base leading-7 whitespace-pre-wrap break-words">
                    {post.body}
                </div>
            </article>
        </>
    );
}
