export function formatPostDate(value: string | null | undefined): string {
    if (!value) {
        return 'Not published';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return 'Not published';
    }

    return new Intl.DateTimeFormat(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(date);
}

/**
 * datetime-local values are the browser's local time and have no offset.
 * Convert them to a UTC instant so the server does not treat local clock time as UTC.
 */
export function toUtcIsoFromLocalDateTime(value: string): string {
    if (value.trim() === '') {
        return '';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return date.toISOString();
}

export function toDateTimeLocal(value: string | null | undefined): string {
    if (!value) {
        return '';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return '';
    }

    const pad = (part: number) => String(part).padStart(2, '0');

    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
}
