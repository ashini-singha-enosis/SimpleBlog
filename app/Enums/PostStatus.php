<?php

namespace App\Enums;

enum PostStatus: string
{
    case Draft = 'draft';
    case InReview = 'in_review';
    case ChangesRequested = 'changes_requested';
    case Scheduled = 'scheduled';
    case Published = 'published';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::InReview => 'In review',
            self::ChangesRequested => 'Changes requested',
            self::Scheduled => 'Scheduled',
            self::Published => 'Published',
        };
    }
}
