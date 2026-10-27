import { Head, Link, usePage } from '@inertiajs/react';
import { FileText, House, Video } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { dashboard, home } from '@/routes';
import { index as managePosts } from '@/routes/admin/posts';
import type { Auth } from '@/types';

type Stats = {
    total: number;
    published: number;
    drafts: number;
};

const statCards = [
    {
        key: 'total' as const,
        label: 'Posts',
        description: 'Everything you have written',
    },
    {
        key: 'published' as const,
        label: 'Published',
        description: 'Live on the site',
    },
    {
        key: 'drafts' as const,
        label: 'Drafts',
        description: 'Still private',
    },
];

export default function Dashboard({ stats }: { stats: Stats }) {
    const { auth } = usePage<{ auth: Auth }>().props;

    return (
        <>
            <Head title="Account" />
            <div className="flex flex-1 flex-col gap-6 p-4">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div className="space-y-1">
                        <h1 className="text-2xl font-semibold tracking-tight">
                            {auth.user.name}
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            {auth.user.email}
                        </p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Button variant="outline" asChild>
                            <Link href={home()}>
                                <House className="size-4" />
                                Blog
                            </Link>
                        </Button>
                        <Button asChild>
                            <Link href={managePosts()}>
                                <FileText className="size-4" />
                                Posts
                            </Link>
                        </Button>
                        <Button variant="outline" asChild>
                            <Link href="/admin/videos">
                                <Video className="size-4" />
                                Videos
                            </Link>
                        </Button>
                    </div>
                </div>

                <div className="grid gap-4 sm:grid-cols-3">
                    {statCards.map((stat) => (
                        <Card key={stat.key}>
                            <CardHeader>
                                <CardDescription>{stat.label}</CardDescription>
                                <CardTitle className="text-3xl tabular-nums">
                                    {stats[stat.key]}
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <p className="text-sm text-muted-foreground">
                                    {stat.description}
                                </p>
                            </CardContent>
                        </Card>
                    ))}
                </div>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Account',
            href: dashboard(),
        },
    ],
};
