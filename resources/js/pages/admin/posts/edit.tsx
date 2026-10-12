import { Head, Link } from '@inertiajs/react';
import Heading from '@/components/heading';
import PostForm from '@/components/post-form';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';
import { index } from '@/routes/admin/posts';
import type { PostFormData } from '@/types/blog';

export default function Edit({
    post,
    categories = [],
    tags = [],
}: {
    post: PostFormData;
    categories?: string[];
    tags?: string[];
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
                <PostForm post={post} categories={categories} tags={tags} />
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
