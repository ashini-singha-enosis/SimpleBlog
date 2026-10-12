import { Form } from '@inertiajs/react';
import { useState } from 'react';
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

export default function PostForm({
    post,
    categories = [],
    tags = [],
}: {
    post?: PostFormData;
    categories?: string[];
    tags?: string[];
}) {
    const [selectedTags, setSelectedTags] = useState<string[]>(
        post?.tags ?? [],
    );
    const [tagQuery, setTagQuery] = useState('');
    const [suggestionsOpen, setSuggestionsOpen] = useState(false);
    const normalizedQuery = tagQuery.trim().toLowerCase();
    const suggestions = tags.filter(
        (name) =>
            normalizedQuery !== '' &&
            name.toLowerCase().includes(normalizedQuery) &&
            !selectedTags.some(
                (selected) => selected.toLowerCase() === name.toLowerCase(),
            ),
    );

    function addTag(name: string) {
        const trimmed = name.trim();

        if (
            trimmed === '' ||
            trimmed.length > 40 ||
            selectedTags.length >= 8 ||
            selectedTags.some(
                (selected) => selected.toLowerCase() === trimmed.toLowerCase(),
            )
        ) {
            return;
        }

        setSelectedTags((current) => [...current, trimmed]);
        setTagQuery('');
        setSuggestionsOpen(false);
    }

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
                    tags: selectedTags,
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

                    <div className="grid gap-2">
                        <Label htmlFor="category">Category</Label>
                        <Input
                            id="category"
                            name="category"
                            list="category-suggestions"
                            maxLength={40}
                            defaultValue={post?.category ?? ''}
                            placeholder="Optional, one category"
                            aria-invalid={errors.category ? true : undefined}
                            aria-describedby={
                                errors.category ? 'category-error' : undefined
                            }
                        />
                        <datalist id="category-suggestions">
                            {categories.map((name) => (
                                <option key={name} value={name} />
                            ))}
                        </datalist>
                        <InputError
                            id="category-error"
                            message={errors.category}
                        />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="tag-input">Tags</Label>
                        <div className="flex flex-wrap gap-2">
                            {selectedTags.map((name) => (
                                <button
                                    key={name}
                                    type="button"
                                    className="rounded-full border px-3 py-1 text-sm"
                                    onClick={() =>
                                        setSelectedTags((current) =>
                                            current.filter(
                                                (tag) => tag !== name,
                                            ),
                                        )
                                    }
                                >
                                    {name}
                                    <span className="sr-only">Remove tag</span>
                                </button>
                            ))}
                        </div>
                        <div className="relative">
                            <Input
                                id="tag-input"
                                value={tagQuery}
                                maxLength={40}
                                placeholder="Type a tag and press Enter"
                                aria-invalid={errors.tags ? true : undefined}
                                aria-describedby={
                                    errors.tags ? 'tags-error' : undefined
                                }
                                onChange={(event) => {
                                    setTagQuery(event.target.value);
                                    setSuggestionsOpen(true);
                                }}
                                onFocus={() => setSuggestionsOpen(true)}
                                onBlur={() => setSuggestionsOpen(false)}
                                onKeyDown={(event) => {
                                    if (event.key !== 'Enter') {
                                        return;
                                    }

                                    event.preventDefault();
                                    const exact = suggestions.find(
                                        (name) =>
                                            name.toLowerCase() ===
                                            normalizedQuery,
                                    );
                                    addTag(exact ?? tagQuery);
                                }}
                            />
                            {suggestionsOpen && suggestions.length > 0 ? (
                                <ul className="absolute z-10 mt-1 max-h-40 w-full overflow-auto rounded-md border bg-popover p-1 text-sm shadow-md">
                                    {suggestions.map((name) => (
                                        <li key={name}>
                                            <button
                                                type="button"
                                                className="w-full rounded-sm px-2 py-1.5 text-left hover:bg-accent"
                                                onMouseDown={(event) => {
                                                    event.preventDefault();
                                                    addTag(name);
                                                }}
                                            >
                                                {name}
                                            </button>
                                        </li>
                                    ))}
                                </ul>
                            ) : null}
                        </div>
                        <p className="text-sm text-muted-foreground">
                            Up to 8 tags. Press Enter to add a new one, or
                            click a tag to remove it.
                        </p>
                        <InputError id="tags-error" message={errors.tags} />
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
