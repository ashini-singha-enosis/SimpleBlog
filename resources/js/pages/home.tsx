import { Head, Link, usePage } from '@inertiajs/react';
import Pagination from '@/components/pagination';
import PostCard from '@/components/post-card';
import { Button } from '@/components/ui/button';
import { home } from '@/routes';
import type { Auth } from '@/types';
import type { Paginated, PostSummary } from '@/types/blog';

export default function Home({
    posts,
    filters,
}: {
    posts: Paginated<PostSummary>;
    filters: { mine: boolean };
}) {
    const { auth } = usePage<{ auth: Auth }>().props;
    const showingMine = filters.mine;

    return (
        <>
            <Head title="Posts" />
            <div className="space-y-8">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div className="space-y-2">
                        <h1 className="text-3xl font-semibold tracking-tight sm:text-4xl">
                            {showingMine ? 'Your posts' : 'Latest posts'}
                        </h1>
                        <p className="max-w-2xl text-muted-foreground">
                            {showingMine
                                ? 'Published posts you have written.'
                                : 'Notes and articles from the people writing here.'}
                        </p>
                    </div>

                    {auth.user ? (
                        <div
                            className="flex gap-2"
                            role="group"
                            aria-label="Filter posts"
                        >
                            <Button
                                variant={showingMine ? 'outline' : 'default'}
                                size="sm"
                                asChild
                            >
                                <Link href={home()}>All posts</Link>
                            </Button>
                            <Button
                                variant={showingMine ? 'default' : 'outline'}
                                size="sm"
                                asChild
                            >
                                <Link href={home({ query: { mine: 1 } })}>
                                    My posts
                                </Link>
                            </Button>
                        </div>
                    ) : null}
                </div>

                {posts.data.length === 0 ? (
                    <div className="rounded-xl border border-dashed px-6 py-16 text-center">
                        <h2 className="text-lg font-medium">
                            {showingMine
                                ? 'You have not published any posts yet'
                                : 'No posts yet'}
                        </h2>
                        <p className="mt-2 text-sm text-muted-foreground">
                            {showingMine
                                ? 'Publish a post and it will show up here.'
                                : 'Published posts will show up here.'}
                        </p>
                    </div>
                ) : (
                    <div className="grid gap-4 md:grid-cols-2">
                        {posts.data.map((post) => (
                            <PostCard key={post.id} post={post} />
                        ))}
                    </div>
                )}

                <Pagination links={posts.links} lastPage={posts.last_page} />
            </div>
        </>
    );
}
