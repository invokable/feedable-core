<?php

declare(strict_types=1);

namespace Revolution\Feedable\Core\Response;

use Illuminate\Contracts\Support\Responsable;
use Revolution\Feedable\Core\Enums\Format;

class ResponseFactory
{
    public function __construct(protected Format $format = Format::RSS) {}

    public static function format(Format $format = Format::RSS): static
    {
        return new static($format);
    }

    public function make(
        ?string $title = null,
        ?string $home_page_url = null,
        ?string $feed_url = null,
        ?string $description = null,
        ?string $next_url = null,
        ?string $icon = null,
        ?string $favicon = null,
        ?array $authors = null,
        string $language = 'ja',
        ?array $hubs = null,
        array $items = [],
    ): Responsable {
        return match ($this->format) {
            Format::JSON => new JsonFeedResponse(
                title: $title,
                home_page_url: $home_page_url,
                feed_url: $feed_url,
                description: $description,
                next_url: $next_url,
                icon: $icon,
                favicon: $favicon,
                authors: $authors,
                language: $language,
                hubs: $hubs,
                items: $items,
            ),
            default => new Rss2Response(
                title: $title,
                description: $description,
                link: $home_page_url,
                feed_url: $feed_url,
                pubDate: now()->toRssString(),
                image: $icon,
                items: $items,
                language: $language,
            ),
        };
    }
}
