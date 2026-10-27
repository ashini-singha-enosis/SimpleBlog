import { Link, usePage } from '@inertiajs/react';
import {
    CircleUser,
    FileText,
    House,
    LogIn,
    UserPen,
    UserPlus,
    Video,
} from 'lucide-react';
import AppLogoIcon from '@/components/app-logo-icon';
import { dashboard, home, login, register } from '@/routes';
import { index as managePosts } from '@/routes/admin/posts';
import { cn } from '@/lib/utils';
import type { Auth } from '@/types';

function itemClass(active: boolean) {
    return cn(
        'flex items-center gap-2 rounded-md px-3 py-2 text-sm whitespace-nowrap',
        active
            ? 'bg-accent font-medium text-foreground'
            : 'text-muted-foreground hover:bg-accent hover:text-foreground',
    );
}

export default function BlogLayout({
    children,
}: {
    children: React.ReactNode;
}) {
    const page = usePage<{ auth: Auth; name: string }>();
    const { auth, name } = page.props;
    const pathname = page.url.split('?')[0];
    const mine =
        new URLSearchParams(page.url.split('?')[1] ?? '').get('mine') === '1';
    const onBlog = pathname === '/';

    const myPostsHref = (() => {
        if (!onBlog) {
            return home({ query: { mine: 1 } });
        }

        const params = new URLSearchParams(page.url.split('?')[1] ?? '');
        params.set('mine', '1');
        params.delete('page');

        return `/?${params.toString()}`;
    })();

    return (
        <div className="flex h-dvh flex-col overflow-hidden bg-background text-foreground lg:grid lg:grid-cols-[13rem_minmax(0,1fr)]">
            <aside className="shrink-0 overflow-x-auto border-b lg:min-h-0 lg:overflow-y-auto lg:border-r lg:border-b-0">
                <div className="flex items-center gap-2 px-4 py-4">
                    <Link href={home()} className="flex items-center gap-2 font-semibold">
                        <span className="flex size-8 items-center justify-center rounded-md bg-primary text-primary-foreground">
                            <AppLogoIcon className="size-5 fill-current" />
                        </span>
                        <span className="truncate">{name}</span>
                    </Link>
                </div>
                <nav
                    aria-label="Site"
                    className="flex gap-1 overflow-x-auto px-3 pb-3 lg:flex-col lg:overflow-visible lg:px-3 lg:pb-6"
                >
                    <Link href={home()} className={itemClass(onBlog && !mine)}>
                        <House className="size-4" />
                        Blog
                    </Link>
                    <Link
                        href="/videos"
                        className={itemClass(pathname.startsWith('/videos'))}
                    >
                        <Video className="size-4" />
                        Videos
                    </Link>
                    {auth.user ? (
                        <>
                            <Link
                                href={managePosts()}
                                className={itemClass(false)}
                            >
                                <FileText className="size-4" />
                                Posts
                            </Link>
                            <Link
                                href={dashboard()}
                                className={itemClass(false)}
                            >
                                <CircleUser className="size-4" />
                                Account
                            </Link>
                            <Link
                                href={myPostsHref}
                                className={itemClass(onBlog && mine)}
                            >
                                <UserPen className="size-4" />
                                My posts
                            </Link>
                        </>
                    ) : (
                        <>
                            <Link href={login()} className={itemClass(false)}>
                                <LogIn className="size-4" />
                                Log in
                            </Link>
                            <Link href={register()} className={itemClass(false)}>
                                <UserPlus className="size-4" />
                                Register
                            </Link>
                        </>
                    )}
                </nav>
            </aside>
            <main className="mx-auto flex min-h-0 w-full max-w-6xl flex-1 flex-col overflow-y-auto px-4 py-6 lg:px-8">
                {children}
            </main>
        </div>
    );
}
