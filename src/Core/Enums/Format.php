<?php

declare(strict_types=1);

namespace Revolution\Feedable\Core\Enums;

/**
 * Output format enum.
 */
enum Format: string
{
    case RSS = 'rss';
    case JSON = 'json';
}
