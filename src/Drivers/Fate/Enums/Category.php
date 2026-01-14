<?php

declare(strict_types=1);

namespace Revolution\Feedable\Drivers\Fate\Enums;

enum Category: string
{
    case News = 'news';
    case Maintenance = 'maintenance';
    case Update = 'update';
    case Trouble = 'trouble';
}
