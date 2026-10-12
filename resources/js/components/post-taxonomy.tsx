import { Link } from '@inertiajs/react';
import { home } from '@/routes';
import type { TaxonomyTerm } from '@/types/blog';

export default function PostTaxonomy({
    category,
    tags = [],
}: {
    category?: TaxonomyTerm | null;
    tags?: TaxonomyTerm[];
}) {
    if (!category && tags.length === 0) {
        return null;
    }

    return (
        <div className="flex flex-wrap gap-2">
            {category ? (
                <Link
                    href={home({ query: { category: category.slug } })}
                    className="rounded-full bg-primary/10 px-2.5 py-1 text-xs font-medium text-primary"
                >
                    {category.name}
                </Link>
            ) : null}
            {tags.map((tag) => (
                <Link
                    key={tag.slug}
                    href={home({ query: { tag: tag.slug } })}
                    className="rounded-full border px-2.5 py-1 text-xs text-muted-foreground hover:text-foreground"
                >
                    {tag.name}
                </Link>
            ))}
        </div>
    );
}
