<?php

declare(strict_types=1);

namespace Revolution\Feedable\Drivers\Note;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Revolution\Feedable\Core\Enums\Format;

class NoteCacheDriver
{
    public function __invoke(Format $format = Format::RSS): Response
    {
        $feed = Cache::get('note-feed:'.$format->value, '');

        $type = $format === Format::RSS ? 'application/xml' : 'application/json; charset=UTF-8';

        return response($feed)
            ->header('Content-Type', $type);
    }
}
