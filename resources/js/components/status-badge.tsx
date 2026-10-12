import { Badge } from '@/components/ui/badge';

const labels = {
    draft: 'Draft',
    in_review: 'In review',
    changes_requested: 'Changes requested',
    scheduled: 'Scheduled',
    published: 'Published',
} as const;

export default function StatusBadge({
    status,
}: {
    status: keyof typeof labels;
}) {
    return (
        <Badge variant={status === 'published' ? 'default' : 'secondary'}>
            {labels[status]}
        </Badge>
    );
}
