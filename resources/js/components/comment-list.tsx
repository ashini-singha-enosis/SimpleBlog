import { Form, Link, router, usePage } from '@inertiajs/react';
import InputError from '@/components/input-error';
import Pagination from '@/components/pagination';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { formatPostDate } from '@/lib/dates';
import { login } from '@/routes';
import type { Auth } from '@/types';
import type { CommentSummary, Paginated } from '@/types/blog';

export default function CommentList({
    storeUrl,
    comments,
    canComment,
}: {
    storeUrl: string;
    comments: Paginated<CommentSummary>;
    canComment: boolean;
}) {
    const { auth } = usePage<{ auth: Auth }>().props;

    return (
        <section className="space-y-6 border-t pt-8">
            <h2 className="text-lg font-semibold tracking-tight">
                Comments
                {comments.total > 0 ? ` (${comments.total})` : ''}
            </h2>

            {canComment ? (
                <Form
                    action={storeUrl}
                    method="post"
                    options={{ preserveScroll: true }}
                    resetOnSuccess={['body']}
                    className="space-y-3"
                >
                    {({ errors, processing }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="body">Write a comment</Label>
                                <Textarea
                                    id="body"
                                    name="body"
                                    rows={4}
                                    maxLength={2000}
                                    required
                                    aria-invalid={errors.body ? true : undefined}
                                />
                                <InputError message={errors.body} />
                            </div>
                            <Button type="submit" disabled={processing}>
                                {processing ? 'Posting...' : 'Post comment'}
                            </Button>
                        </>
                    )}
                </Form>
            ) : (
                <p className="text-sm text-muted-foreground">
                    {auth.user ? (
                        'Verify your email to comment.'
                    ) : (
                        <>
                            <Link
                                href={login()}
                                className="underline-offset-4 hover:underline"
                            >
                                Log in
                            </Link>{' '}
                            to comment.
                        </>
                    )}
                </p>
            )}

            {comments.data.length === 0 ? (
                <p className="text-sm text-muted-foreground">No comments yet.</p>
            ) : (
                <ul className="space-y-6">
                    {comments.data.map((comment) => (
                        <li key={comment.id} className="space-y-2">
                            <p className="text-sm text-muted-foreground">
                                {comment.author.name} ·{' '}
                                <time dateTime={comment.created_at ?? undefined}>
                                    {formatPostDate(comment.created_at)}
                                </time>
                            </p>
                            <p className="whitespace-pre-wrap break-words">
                                {comment.body}
                            </p>
                            {comment.can_delete ? (
                                <button
                                    type="button"
                                    className="text-sm text-muted-foreground underline-offset-4 hover:text-foreground hover:underline"
                                    onClick={() =>
                                        router.delete(`/comments/${comment.id}`, {
                                            preserveScroll: true,
                                        })
                                    }
                                >
                                    Delete
                                </button>
                            ) : null}
                        </li>
                    ))}
                </ul>
            )}

            <Pagination links={comments.links} lastPage={comments.last_page} />
        </section>
    );
}
