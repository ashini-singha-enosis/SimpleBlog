import { Form } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';

export default function DeleteVideoButton({
    videoId,
    caption,
}: {
    videoId: number;
    caption: string;
}) {
    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button type="button" variant="destructive" size="sm">
                    Delete
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogTitle>Delete this video?</DialogTitle>
                <DialogDescription>
                    “{caption}” will be removed from the site. The file is
                    deleted from storage.
                </DialogDescription>
                <DialogFooter>
                    <DialogClose asChild>
                        <Button type="button" variant="outline">
                            Cancel
                        </Button>
                    </DialogClose>
                    <Form
                        action={`/admin/videos/${videoId}`}
                        method="delete"
                    >
                        {({ processing }) => (
                            <Button
                                type="submit"
                                variant="destructive"
                                disabled={processing}
                            >
                                {processing ? 'Deleting...' : 'Delete video'}
                            </Button>
                        )}
                    </Form>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
