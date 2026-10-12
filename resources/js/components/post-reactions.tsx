import { Link, router, usePage } from '@inertiajs/react';
import { ThumbsDown, ThumbsUp } from 'lucide-react';
import { useState, type ReactNode } from 'react';
import { login } from '@/routes';
import type { ReactionChoice } from '@/types/blog';
import type { Auth } from '@/types';

type Props = {
    slug: string;
    likesCount: number;
    dislikesCount: number;
    userReaction: ReactionChoice | null;
};

export default function PostReactions({
    slug,
    likesCount,
    dislikesCount,
    userReaction,
}: Props) {
    const { auth } = usePage<{ auth: Auth }>().props;
    const signedIn = auth.user !== null;
    const [names, setNames] = useState<string[] | null>(null);
    const [openList, setOpenList] = useState<ReactionChoice | null>(null);

    function react(type: ReactionChoice): void {
        if (!signedIn) {
            return;
        }

        if (userReaction === type) {
            router.delete(`/posts/${slug}/reactions`, { preserveScroll: true });

            return;
        }

        router.post(`/posts/${slug}/reactions`, { type }, { preserveScroll: true });
    }

    async function showNames(type: ReactionChoice): Promise<void> {
        if (openList === type) {
            setOpenList(null);

            return;
        }

        const response = await fetch(`/posts/${slug}/reactions?type=${type}`, {
            headers: { Accept: 'application/json' },
        });

        if (!response.ok) {
            setNames([]);
            setOpenList(type);

            return;
        }

        const data = (await response.json()) as { names?: string[] };
        setNames(data.names ?? []);
        setOpenList(type);
    }

    return (
        <div className="space-y-2">
            <div className="flex flex-wrap items-center gap-2">
                <ReactionControl
                    label="Like"
                    count={likesCount}
                    active={userReaction === 'like'}
                    signedIn={signedIn}
                    icon={<ThumbsUp className="size-4" />}
                    onReact={() => react('like')}
                    onNames={() => void showNames('like')}
                />
                <ReactionControl
                    label="Dislike"
                    count={dislikesCount}
                    active={userReaction === 'dislike'}
                    signedIn={signedIn}
                    icon={<ThumbsDown className="size-4" />}
                    onReact={() => react('dislike')}
                    onNames={() => void showNames('dislike')}
                />
            </div>
            {openList !== null && names !== null ? (
                <ul className="text-sm text-muted-foreground">
                    {names.length === 0 ? (
                        <li>
                            {openList === 'like' ? 'No likes yet' : 'No dislikes yet'}
                        </li>
                    ) : (
                        names.map((name, index) => <li key={`${name}-${index}`}>{name}</li>)
                    )}
                </ul>
            ) : null}
        </div>
    );
}

function ReactionControl({
    label,
    count,
    active,
    signedIn,
    icon,
    onReact,
    onNames,
}: {
    label: string;
    count: number;
    active: boolean;
    signedIn: boolean;
    icon: ReactNode;
    onReact: () => void;
    onNames: () => void;
}) {
    const buttonClass = `inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-sm ${
        active
            ? 'border-foreground bg-foreground text-background'
            : 'border-border text-muted-foreground hover:text-foreground'
    }`;

    return (
        <span className="inline-flex items-center gap-1">
            {signedIn ? (
                <button type="button" className={buttonClass} onClick={onReact}>
                    {icon}
                    {label}
                </button>
            ) : (
                <Link href={login()} className={buttonClass}>
                    {icon}
                    {label}
                </Link>
            )}
            <button
                type="button"
                className="text-sm text-muted-foreground underline-offset-4 hover:text-foreground hover:underline"
                onClick={onNames}
            >
                {count}
            </button>
        </span>
    );
}
