import { Link, usePage } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import { dashboard, home, login, register } from '@/routes';
import { index as managePosts } from '@/routes/admin/posts';
import type { Auth } from '@/types';

export default function BlogLayout({
    children,
}: {
    children: React.ReactNode;
}) {
    const { auth, name } = usePage<{ auth: Auth; name: string }>().props;

    return (
        <div className="min-h-screen bg-background text-foreground">
            <header className="border-b border-border">
                <div className="mx-auto flex h-16 w-full max-w-5xl items-center justify-between gap-4 px-4">
                    <Link
                        href={home()}
                        className="flex items-center gap-2 font-semibold"
                    >
                        <span className="flex size-8 items-center justify-center rounded-md bg-primary text-primary-foreground">
                            <AppLogoIcon className="size-5 fill-current" />
                        </span>
                        <span>{name}</span>
                    </Link>

                    <nav
                        aria-label="Account"
                        className="flex items-center gap-2"
                    >
                        {auth.user ? (
                            <>
                                <Link
                                    href={managePosts()}
                                    className="rounded-md px-3 py-2 text-sm font-medium hover:bg-accent"
                                >
                                    Manage posts
                                </Link>
                                <Link
                                    href={dashboard()}
                                    className="rounded-md px-3 py-2 text-sm font-medium hover:bg-accent"
                                >
                                    Dashboard
                                </Link>
                            </>
                        ) : (
                            <>
                                <Link
                                    href={login()}
                                    className="rounded-md px-3 py-2 text-sm font-medium hover:bg-accent"
                                >
                                    Log in
                                </Link>
                                <Link
                                    href={register()}
                                    className="rounded-md bg-primary px-3 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90"
                                >
                                    Register
                                </Link>
                            </>
                        )}
                    </nav>
                </div>
            </header>
            <main className="mx-auto w-full max-w-5xl px-4 py-10">{children}</main>
        </div>
    );
}
