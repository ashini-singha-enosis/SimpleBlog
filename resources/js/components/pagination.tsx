import { Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import type { PaginatorLink } from '@/types/blog';

function linkLabel(label: string): string {
    return label
        .replaceAll('&laquo;', '«')
        .replaceAll('&raquo;', '»')
        .replaceAll('&amp;', '&');
}

export default function Pagination({
    links,
    lastPage,
}: {
    links: PaginatorLink[];
    lastPage: number;
}) {
    if (lastPage <= 1) {
        return null;
    }

    return (
        <nav
            aria-label="Pagination"
            className="flex flex-wrap items-center justify-center gap-2"
        >
            {links.map((link, index) => {
                const label = linkLabel(link.label);

                if (!link.url) {
                    return (
                        <Button
                            key={`${label}-${index}`}
                            type="button"
                            variant="outline"
                            size="sm"
                            disabled
                        >
                            {label}
                        </Button>
                    );
                }

                return (
                    <Button
                        key={`${label}-${index}`}
                        variant={link.active ? 'default' : 'outline'}
                        size="sm"
                        asChild
                    >
                        <Link href={link.url} preserveScroll>
                            {label}
                        </Link>
                    </Button>
                );
            })}
        </nav>
    );
}
