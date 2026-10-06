import { Head, Link } from '@inertiajs/react';
import Heading from '@/components/heading';
import PostForm from '@/components/post-form';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';
import { create, index } from '@/routes/admin/posts';

export default function Create() {
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
                <PostForm />
            </div>
        </>
    );
}

Create.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
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
