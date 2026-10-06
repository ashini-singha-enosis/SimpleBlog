import { Form } from '@inertiajs/react';
import PostController from '@/actions/App/Http/Controllers/Admin/PostController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { toDateTimeLocal, toUtcIsoFromLocalDateTime } from '@/lib/dates';
import { cn } from '@/lib/utils';
import type { PostFormData } from '@/types/blog';

const fieldClassName =
    'border-input focus-visible:border-ring focus-visible:ring-ring/50 flex h-9 w-full rounded-md border bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:ring-[3px]';

export default function PostForm({ post }: { post?: PostFormData }) {
    return (
        <Form
            {...(post
                ? PostController.update.form(post.id)
                : PostController.store.form())}
            transform={(data) => {
                const publishedAt = data.published_at;

                return {
                    ...data,
                    published_at:
                        typeof publishedAt === 'string'
                            ? toUtcIsoFromLocalDateTime(publishedAt)
                            : publishedAt,
                };
            }}
            className="space-y-6"
        >
            {({ processing, errors }) => (
                <>
                    <div className="grid gap-2">
                        <Label htmlFor="title">Title</Label>
                        <Input
                            id="title"
                            name="title"
                            defaultValue={post?.title ?? ''}
                            required
                            maxLength={255}
                            aria-invalid={errors.title ? true : undefined}
                            aria-describedby={
                                errors.title ? 'title-error' : undefined
                            }
                            placeholder="Post title"
                        />
                        <InputError id="title-error" message={errors.title} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="excerpt">Excerpt</Label>
                        <Textarea
                            id="excerpt"
                            name="excerpt"
                            defaultValue={post?.excerpt ?? ''}
                            maxLength={500}
                            rows={3}
                            className="min-h-24"
                            aria-invalid={errors.excerpt ? true : undefined}
                            aria-describedby={
                                errors.excerpt ? 'excerpt-error' : undefined
                            }
                            placeholder="Optional short summary"
                        />
                        <InputError
                            id="excerpt-error"
                            message={errors.excerpt}
                        />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="body">Body</Label>
                        <Textarea
                            id="body"
                            name="body"
                            defaultValue={post?.body ?? ''}
                            required
                            minLength={20}
                            aria-invalid={errors.body ? true : undefined}
                            aria-describedby={
                                errors.body ? 'body-error' : undefined
                            }
                            placeholder="Write the post. Plain text is shown as entered."
                        />
                        <InputError id="body-error" message={errors.body} />
                    </div>

                    <div className="grid gap-6 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="status">Status</Label>
                            <select
                                id="status"
                                name="status"
                                defaultValue={post?.status ?? 'draft'}
                                required
                                aria-invalid={errors.status ? true : undefined}
                                aria-describedby={
                                    errors.status ? 'status-error' : undefined
                                }
                                className={cn(
                                    fieldClassName,
                                    'aria-invalid:border-destructive',
                                )}
                            >
                                <option value="draft">Draft</option>
                                <option value="published">Published</option>
                            </select>
                            <InputError
                                id="status-error"
                                message={errors.status}
                            />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="published_at">Publish at</Label>
                            <Input
                                id="published_at"
                                name="published_at"
                                type="datetime-local"
                                defaultValue={toDateTimeLocal(
                                    post?.published_at,
                                )}
                                aria-invalid={
                                    errors.published_at ? true : undefined
                                }
                                aria-describedby={
                                    errors.published_at
                                        ? 'published-at-error'
                                        : undefined
                                }
                            />
                            <p className="text-sm text-muted-foreground">
                                Leave blank to publish immediately when the
                                status is published.
                            </p>
                            <InputError
                                id="published-at-error"
                                message={errors.published_at}
                            />
                        </div>
                    </div>

                    <Button type="submit" disabled={processing}>
                        {processing
                            ? 'Saving...'
                            : post
                              ? 'Update post'
                              : 'Create post'}
                    </Button>
                </>
            )}
        </Form>
    );
}
