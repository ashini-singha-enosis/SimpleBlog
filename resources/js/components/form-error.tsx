export default function FormError({
    id,
    message,
}: {
    id?: string;
    message?: string;
}) {
    if (!message) {
        return null;
    }

    return (
        <p id={id} role="alert" className="text-sm text-red-600 dark:text-red-400">
            {message}
        </p>
    );
}
