import { Head, Link, usePage } from '@inertiajs/react';
import DeletePostButton from '@/components/delete-post-button';
import Heading from '@/components/heading';
import Pagination from '@/components/pagination';
import StatusBadge from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { formatPostDate } from '@/lib/dates';
import { dashboard } from '@/routes';
import { create, edit, index } from '@/routes/admin/posts';
import { show } from '@/routes/posts';
import type { Auth } from '@/types';
import type { Paginated, PostSummary } from '@/types/blog';

export default function Index({ posts }: { posts: Paginated<PostSummary> }) {
    const { auth } = usePage<{ auth: Auth }>().props;
    const isAdmin = auth.user?.is_admin === true;

    return (
        <>
            <Head title="Posts" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <Heading
                        title="Posts"
                        description={
                            isAdmin
                                ? 'Every post on the site.'
                                : 'Posts you have written.'
                        }
                    />
                    <Button asChild>
                        <Link href={create()}>New post</Link>
                    </Button>
                </div>

                {posts.data.length === 0 ? (
                    <div className="rounded-xl border border-dashed px-6 py-16 text-center">
                        <h2 className="text-lg font-medium">No posts yet</h2>
                        <p className="mt-2 text-sm text-muted-foreground">
                            Create a draft or publish something new.
                        </p>
                    </div>
                ) : (
                    <ul className="divide-y rounded-xl border">
                        {posts.data.map((post) => (
                            <li
                                key={post.id}
                                className="flex flex-col gap-4 p-4 sm:flex-row sm:items-center sm:justify-between"
                            >
                                <div className="min-w-0 space-y-1">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <Link
                                            href={edit(post.id)}
                                            className="truncate font-medium underline-offset-4 hover:underline"
                                        >
                                            {post.title}
                                        </Link>
                                        {post.status ? (
                                            <StatusBadge status={post.status} />
                                        ) : null}
                                    </div>
                                    <p className="text-sm text-muted-foreground">
                                        {isAdmin
                                            ? `${post.author?.name ?? 'Unknown'} · `
                                            : ''}
                                        {formatPostDate(
                                            post.published_at ??
                                                post.updated_at,
                                        )}
                                    </p>
                                </div>
                                <div className="flex shrink-0 items-center gap-2">
                                    {post.status === 'published' &&
                                    post.published_at &&
                                    new Date(post.published_at).getTime() <=
                                        Date.now() ? (
                                        <Button variant="outline" size="sm" asChild>
                                            <Link href={show(post.slug)}>
                                                View
                                            </Link>
                                        </Button>
                                    ) : null}
                                    <Button variant="outline" size="sm" asChild>
                                        <Link href={edit(post.id)}>Edit</Link>
                                    </Button>
                                    <DeletePostButton
                                        postId={post.id}
                                        postTitle={post.title}
                                    />
                                </div>
                            </li>
                        ))}
                    </ul>
                )}

                <Pagination links={posts.links} lastPage={posts.last_page} />
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
            title: 'Posts',
            href: index(),
        },
    ],
};
