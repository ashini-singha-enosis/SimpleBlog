<?php

namespace App\Services;

use App\Contracts\CommentServiceInterface;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Models\Video;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CommentService implements CommentServiceInterface
{
    /**
     * @param  array<string, mixed>  $page
     * @return array<string, mixed>
     */
    public function withCounts(array $page): array
    {
        $rows = $page['data'] ?? null;

        if (! is_array($rows) || $rows === []) {
            return $page;
        }

        $ids = [];

        foreach ($rows as $row) {
            if (is_array($row) && isset($row['id'])) {
                $ids[] = (int) $row['id'];
            }
        }

        $counts = [];

        foreach ($ids as $id) {
            $counts[$id] = 0;
        }

        if ($ids !== []) {
            $totals = Comment::query()
                ->where('commentable_type', (new Post)->getMorphClass())
                ->whereIn('commentable_id', $ids)
                ->selectRaw('commentable_id, count(*) as total')
                ->groupBy('commentable_id')
                ->get();

            foreach ($totals as $total) {
                $counts[(int) $total->getAttribute('commentable_id')] = (int) $total->getAttribute('total');
            }
        }

        foreach ($rows as $index => $row) {
            if (! is_array($row) || ! isset($row['id'])) {
                continue;
            }

            $page['data'][$index]['comments_count'] = $counts[(int) $row['id']] ?? 0;
        }

        return $page;
    }

    /**
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function forCommentable(Model $commentable, ?User $viewer, int $page = 1): LengthAwarePaginator
    {
        $this->assertVisible($commentable);

        return Comment::query()
            ->whereMorphedTo('commentable', $commentable)
            ->with('author:id,name')
            ->oldest()
            ->paginate(20, ['id', 'user_id', 'body', 'created_at'], 'comments', max(1, $page))
            ->through(fn (Comment $comment): array => [
                'id' => $comment->id,
                'body' => $comment->body,
                'created_at' => $comment->created_at?->toIso8601String(),
                'author' => [
                    'name' => $comment->author?->name ?? 'Unknown',
                ],
                'can_delete' => $viewer !== null && $viewer->can('delete', $comment),
            ]);
    }

    public function create(User $user, Model $commentable, string $body): Comment
    {
        $this->assertVisible($commentable);

        $body = trim($body);

        if ($body === '') {
            throw new InvalidArgumentException('The comment cannot be empty.');
        }

        $comment = Comment::query()->create([
            'user_id' => $user->id,
            'commentable_type' => $commentable->getMorphClass(),
            'commentable_id' => $commentable->getKey(),
            'body' => $body,
        ]);

        Log::info('Comment created', [
            'comment_id' => $comment->id,
            'user_id' => $user->id,
            'commentable_type' => $commentable->getMorphClass(),
            'commentable_id' => $commentable->getKey(),
        ]);

        return $comment;
    }

    public function delete(Comment $comment, User $actor): void
    {
        $comment->delete();

        Log::info('Comment deleted', [
            'comment_id' => $comment->id,
            'user_id' => $actor->id,
        ]);
    }

    private function assertVisible(Model $commentable): void
    {
        if ($commentable instanceof Post && $commentable->isPubliclyVisible()) {
            return;
        }

        if ($commentable instanceof Video && $commentable->isPubliclyVisible()) {
            return;
        }

        throw new NotFoundHttpException;
    }
}
