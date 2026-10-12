import { Head, Link, router } from '@inertiajs/react';
import Heading from '@/components/heading';
import PostForm from '@/components/post-form';
import { Button } from '@/components/ui/button';
import { formatPostDate } from '@/lib/dates';
import { dashboard } from '@/routes';
import { index } from '@/routes/admin/posts';
import type {
    PostFormData,
    PostRevisionSummary,
    StatusOption,
} from '@/types/blog';

export default function Edit({
    post,
    categories = [],
    tags = [],
    statuses = [],
    revisions = [],
}: {
    post: PostFormData;
    categories?: string[];
    tags?: string[];
    statuses?: StatusOption[];
    revisions?: PostRevisionSummary[];
}) {
    return (
        <>
            <Head title={`Edit ${post.title}`} />
            <div className="mx-auto flex w-full max-w-3xl flex-1 flex-col gap-6 p-4">
                <div className="flex items-start justify-between gap-4">
                    <Heading
                        title="Edit post"
                        description="Changing the title updates the public URL."
                    />
                    <Button variant="outline" asChild>
                        <Link href={index()}>Back</Link>
                    </Button>
                </div>
                <PostForm
                    post={post}
                    categories={categories}
                    tags={tags}
                    statuses={statuses}
                />
                {revisions.length > 0 ? (
                    <section className="space-y-3">
                        <h2 className="text-sm font-medium">Revisions</h2>
                        <ul className="divide-y rounded-xl border">
                            {revisions.map((revision) => (
                                <li
                                    key={revision.id}
                                    className="flex items-center justify-between gap-3 px-3 py-2 text-sm"
                                >
                                    <span className="text-muted-foreground">
                                        {revision.author} ·{' '}
                                        {formatPostDate(revision.created_at)} ·{' '}
                                        {revision.status.replaceAll('_', ' ')}
                                    </span>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        onClick={() =>
                                            router.post(
                                                `/admin/posts/${post.id}/revisions/${revision.id}/restore`,
                                            )
                                        }
                                    >
                                        Restore
                                    </Button>
                                </li>
                            ))}
                        </ul>
                    </section>
                ) : null}
            </div>
        </>
    );
}

Edit.layout = {
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
