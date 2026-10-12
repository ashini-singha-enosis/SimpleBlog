<?php

namespace App\Enums;

enum ReactionType: string
{
    case Like = 'like';
    case Dislike = 'dislike';
}
