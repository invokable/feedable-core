<?php

declare(strict_types=1);

namespace Revolution\Feedable\Drivers\Note;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class NotePostDriver
{
    public function __invoke(Request $request): Response
    {
        abort_if($request->input('token') !== config('feedable.note.token'), Response::HTTP_FORBIDDEN);

        $feed = $request->input('feed');
        $format = $request->input('format');

        info('Received note feed: '.$format, [
            'summary' => Str::limit($feed),
            'length' => Str::length($feed),
        ]);

        Cache::forever('note-feed:'.$format, $feed);

        return response()->noContent();
    }
}
