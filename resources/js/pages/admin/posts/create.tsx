import { Head, Link } from '@inertiajs/react';
import Heading from '@/components/heading';
import PostForm from '@/components/post-form';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';
import { create, index } from '@/routes/admin/posts';

export default function Create({
    categories = [],
    tags = [],
}: {
    categories?: string[];
    tags?: string[];
}) {
    return (
        <>
            <Head title="New post" />
            <div className="mx-auto flex w-full max-w-3xl flex-1 flex-col gap-6 p-4">
                <div className="flex items-start justify-between gap-4">
                    <Heading
                        title="New post"
                        description="Drafts stay private until you publish them."
                    />
                    <Button variant="outline" asChild>
                        <Link href={index()}>Cancel</Link>
                    </Button>
                </div>
                <PostForm categories={categories} tags={tags} />
            </div>
        </>
    );
}

Create.layout = {
    breadcrumbs: [
        {
            title: 'Account',
            href: dashboard(),
        },
        {
            title: 'Posts',
            href: index(),
        },
        {
            title: 'New post',
            href: create(),
        },
    ],
};
