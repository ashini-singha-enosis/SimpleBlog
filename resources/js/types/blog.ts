export type PostAuthor = {
    id?: number;
    name: string;
};

export type TaxonomyTerm = {
    name: string;
    slug: string;
};

export type TagCount = TaxonomyTerm & {
    posts_count: number;
    posts_count_label: string;
};

export type PostSummary = {
    id: number;
    title: string;
    slug: string;
    excerpt: string | null;
    published_at: string | null;
    status?: 'draft' | 'published';
    updated_at?: string | null;
    author?: PostAuthor | null;
    category?: TaxonomyTerm | null;
    tags?: TaxonomyTerm[];
};

export type PostDetail = {
    title: string;
    excerpt: string | null;
    body: string;
    published_at: string | null;
    author: PostAuthor;
    category: TaxonomyTerm | null;
    tags: TaxonomyTerm[];
};

export type PostFormData = {
    id: number;
    title: string;
    excerpt: string | null;
    body: string;
    status: 'draft' | 'published';
    published_at: string | null;
    category?: string | null;
    tags?: string[];
};

export type HomeFilters = {
    q: string;
    category: string;
    tag: string;
    mine: boolean;
};

export type PaginatorLink = {
    url: string | null;
    label: string;
    active: boolean;
};

export type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
    links: PaginatorLink[];
};
