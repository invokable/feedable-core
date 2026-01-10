<?php

declare(strict_types=1);

namespace Revolution\Feedable\Core;

use Illuminate\Support\Collection;

class Driver
{
    protected static array $drivers = [];

    /**
     * Register driver information.
     *
     * Registered with each driver's Service Provider.
     *
     * @param  string  $id  Unique Driver ID (e.g. 'mirror', 'famitsu')
     * @param  string  $name  User-facing Driver Name
     * @param  string|null  $url  Target site URL
     * @param  null|array  $authors  List of authors/maintainers of the Driver
     * @param  null|array  $tags  List of tags categorizing the Driver
     * @param  string|null  $description  Brief description of the Driver's functionality. Markdown supported.
     * @param  string|null  $example  Example URL demonstrating Driver usage
     * @param  null|array  $format  Supported output formats (e.g. ['rss', 'json', 'atom'])
     * @param  null|string  $language  Language code (e.g. 'ja', 'en')
     * @param  null|string  $timezone  Timezone identifier (e.g. 'Asia/Tokyo', 'UTC')
     * @param  bool  $browser  Indicates whether the driver requires a browser environment such as Playwright.
     */
    public static function about(
        string $id,
        string $name,
        ?string $url = null,
        ?array $authors = null,
        ?array $tags = null,
        ?string $description = null,
        ?string $example = null,
        ?array $format = null,
        ?string $language = null,
        ?string $timezone = null,
        bool $browser = false,
    ): void {
        static::$drivers[$id] = compact(
            'id',
            'name',
            'url',
            'authors',
            'tags',
            'description',
            'example',
            'format',
            'language',
            'timezone',
            'browser',
        );
    }

    public static function get(string $id, array $default = []): array
    {
        return data_get(static::$drivers, $id, $default);
    }

    public static function collect(): Collection
    {
        return new Collection(static::$drivers);
    }

    /**
     * Get all registered drivers as pretty JSON.
     */
    public static function toPrettyJson(int $options = JSON_UNESCAPED_UNICODE): string
    {
        return static::collect()->values()->toPrettyJson($options);
    }
}
