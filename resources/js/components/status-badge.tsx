import { Badge } from '@/components/ui/badge';

export default function StatusBadge({
    status,
}: {
    status: 'draft' | 'published';
}) {
    const published = status === 'published';

    return (
        <Badge variant={published ? 'default' : 'secondary'}>
            {published ? 'Published' : 'Draft'}
        </Badge>
    );
}
