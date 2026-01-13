<?php

declare(strict_types=1);

namespace Revolution\Feedable\Drivers\Nicovideo\Enums;

enum Category: string
{
    case All = 'all';
    case Shonen = 'shonen';
    case Shojo = 'shojo';
    case Seinen = 'seinen';
    case Josei = 'josei';
    case Yonkoma = 'yonkoma';
    case Other = 'other';
    case Fan = 'fan';
}
