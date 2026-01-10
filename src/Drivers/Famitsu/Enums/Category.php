<?php

declare(strict_types=1);

namespace Revolution\Feedable\Drivers\Famitsu\Enums;

enum Category: string
{
    case NewArticle = 'new-article';
    case SWITCH = 'switch';
    case PS5 = 'ps5';
    case PS4 = 'ps4';
    case PC_GAME = 'pc-game';
    case NEWS = 'news';
    case VIDEOS = 'videos';
    case SPECIAL_ARTICLE = 'special-article';
    case INTERVIEW = 'interview';
    case EVENT_REPORT = 'event-report';
    case REVIEW = 'review';
    case INDIE_GAME = 'indie-game';
}
