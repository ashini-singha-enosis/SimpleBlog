export type PostStatusName =
    | 'draft'
    | 'in_review'
    | 'changes_requested'
    | 'scheduled'
    | 'published';

export type StatusOption = {
    value: PostStatusName;
    label: string;
};

export type PostRevisionSummary = {
    id: number;
    status: PostStatusName;
    lock_version: number;
    created_at: string | null;
    author: string;
};

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
    status?: PostStatusName;
    updated_at?: string | null;
    author?: PostAuthor | null;
    image_url?: string | null;
    image_card_url?: string | null;
    image_full_url?: string | null;
    category?: TaxonomyTerm | null;
    tags?: TaxonomyTerm[];
};

export type PostDetail = {
    title: string;
    excerpt: string | null;
    body: string;
    published_at: string | null;
    image_url: string | null;
    author: PostAuthor;
    category: TaxonomyTerm | null;
    tags: TaxonomyTerm[];
};

export type PostFormData = {
    id: number;
    title: string;
    excerpt: string | null;
    body: string;
    status: PostStatusName;
    published_at: string | null;
    image_url?: string | null;
    lock_version?: number;
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
