import { Head, Link } from '@inertiajs/react';
import { Search, Tag, X } from 'lucide-react';
import Pagination from '@/components/pagination';
import PostCard from '@/components/post-card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { home } from '@/routes';
import type {
    HomeFilters,
    Paginated,
    PostSummary,
    TagCount,
    TaxonomyTerm,
} from '@/types/blog';

function listingHref(
    filters: HomeFilters,
    overrides: Partial<HomeFilters> = {},
) {
    const next = { ...filters, ...overrides };
    const query: Record<string, string | number> = {};

    if (next.q) {
        query.q = next.q;
    }

    if (next.category) {
        query.category = next.category;
    }

    if (next.tag) {
        query.tag = next.tag;
    }

    if (next.mine) {
        query.mine = 1;
    }

    return home({ query });
}

export default function Home({
    posts,
    filters,
    taxonomy,
}: {
    posts: Paginated<PostSummary>;
    filters: HomeFilters;
    taxonomy: {
        categories: TaxonomyTerm[];
        tags: TagCount[];
    };
}) {
    const searching =
        filters.q !== '' || filters.category !== '' || filters.tag !== '';
    const activeCategory = taxonomy.categories.find(
        (category) => category.slug === filters.category,
    );

    return (
        <>
            <Head title="Blog" />
            <div className="grid h-full min-h-0 flex-1 grid-rows-[auto_auto_minmax(0,1fr)] gap-6 lg:grid-cols-[minmax(0,1fr)_15rem] lg:grid-rows-[auto_minmax(0,1fr)] lg:gap-x-10">
                    <form
                        action={home()}
                        method="get"
                        className="flex flex-wrap items-center gap-2 lg:col-start-1 lg:row-start-1"
                    >
                        {filters.mine ? (
                            <input type="hidden" name="mine" value="1" />
                        ) : null}
                        {filters.tag ? (
                            <input type="hidden" name="tag" value={filters.tag} />
                        ) : null}
                        <div className="relative min-w-[12rem] flex-1">
                            <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                id="q"
                                name="q"
                                defaultValue={filters.q}
                                placeholder="Search"
                                aria-label="Search titles"
                                className="pl-9"
                            />
                        </div>
                        <select
                            id="category"
                            name="category"
                            defaultValue={filters.category}
                            aria-label="Category"
                            className="border-input focus-visible:border-ring focus-visible:ring-ring/50 h-9 rounded-md border bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:ring-[3px]"
                        >
                            <option value="">All categories</option>
                            {taxonomy.categories.map((category) => (
                                <option
                                    key={category.slug}
                                    value={category.slug}
                                >
                                    {category.name}
                                </option>
                            ))}
                            {filters.category && !activeCategory ? (
                                <option value={filters.category}>
                                    {filters.category}
                                </option>
                            ) : null}
                        </select>
                        <Button type="submit" size="icon" aria-label="Search">
                            <Search className="size-4" />
                        </Button>
                        {searching ? (
                            <Button
                                variant="ghost"
                                size="icon"
                                asChild
                                aria-label="Clear search"
                            >
                                <Link
                                    href={listingHref(filters, {
                                        q: '',
                                        category: '',
                                        tag: '',
                                    })}
                                >
                                    <X className="size-4" />
                                </Link>
                            </Button>
                        ) : null}
                    </form>

                <aside className="max-h-40 space-y-3 overflow-y-auto lg:col-start-2 lg:row-span-2 lg:row-start-1 lg:max-h-none lg:min-h-0">
                    <h2 className="flex items-center gap-2 text-sm font-medium">
                        <Tag className="size-4" />
                        Tags
                    </h2>
                    {taxonomy.tags.length === 0 ? (
                        <p className="text-sm text-muted-foreground">No tags</p>
                    ) : (
                        <div className="flex flex-col gap-1">
                            {taxonomy.tags.map((tag) => (
                                <Link
                                    key={tag.slug}
                                    href={listingHref(filters, {
                                        tag:
                                            filters.tag === tag.slug
                                                ? ''
                                                : tag.slug,
                                    })}
                                    className={
                                        filters.tag === tag.slug
                                            ? 'flex items-center justify-between rounded-md bg-accent px-2 py-1.5 text-sm font-medium'
                                            : 'flex items-center justify-between rounded-md px-2 py-1.5 text-sm text-muted-foreground hover:bg-accent hover:text-foreground'
                                    }
                                >
                                    <span>{tag.name}</span>
                                    <span className="text-xs tabular-nums">
                                        {tag.posts_count_label}
                                    </span>
                                </Link>
                            ))}
                        </div>
                    )}
                </aside>

                <div className="min-h-0 overflow-y-auto lg:col-start-1 lg:row-start-2">
                    {posts.data.length === 0 ? (
                        <p className="py-16 text-center text-sm text-muted-foreground">
                            {searching ? 'Nothing matched' : 'No posts yet'}
                        </p>
                    ) : (
                        <div>
                            {posts.data.map((post) => (
                                <PostCard key={post.id} post={post} />
                            ))}
                        </div>
                    )}

                    <Pagination links={posts.links} lastPage={posts.last_page} />
                </div>
            </div>
        </>
    );
}
