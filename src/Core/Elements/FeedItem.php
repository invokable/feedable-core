<?php

declare(strict_types=1);

namespace Revolution\Feedable\Core\Elements;

use Carbon\Carbon;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Tappable;

/**
 * Item object common to JsonFeed and RSS.
 */
class FeedItem implements Arrayable
{
    use Conditionable;
    use Tappable;

    protected array $extra = [];

    public function __construct(
        public string|int $id,
        public ?string $url = null,
        public ?string $external_url = null,
        public ?string $title = null,
        public ?string $content_html = null,
        public ?string $content_text = null,
        public ?string $summary = null,
        public ?string $image = null,
        public ?string $banner_image = null,
        public string|Carbon|null $date_published = null,
        public string|Carbon|null $date_modified = null,
        public ?array $authors = null,
        public ?array $tags = null,
        public ?string $language = null,
        public ?array $attachments = null,
    ) {
        //
    }

    public static function fromArray(array $data): static
    {
        $known = [
            'id', 'url', 'external_url', 'title',
            'content_html', 'content_text', 'summary',
            'image', 'banner_image',
            'date_published', 'date_modified',
            'authors', 'tags', 'language', 'attachments',
        ];

        $item = new static(
            id: $data['id'] ?? '',
            url: $data['url'] ?? null,
            external_url: $data['external_url'] ?? null,
            title: $data['title'] ?? null,
            content_html: $data['content_html'] ?? null,
            content_text: $data['content_text'] ?? null,
            summary: $data['summary'] ?? null,
            image: $data['image'] ?? null,
            banner_image: $data['banner_image'] ?? null,
            date_published: isset($data['date_published']) ? Carbon::parse($data['date_published']) : null,
            date_modified: isset($data['date_modified']) ? Carbon::parse($data['date_modified']) : null,
            authors: $data['authors'] ?? null,
            tags: $data['tags'] ?? null,
            language: $data['language'] ?? null,
            attachments: $data['attachments'] ?? null,
        );

        foreach ($data as $key => $value) {
            if (! in_array($key, $known, true)) {
                $item->extra[$key] = $value;
            }
        }

        return $item;
    }

    /**
     * Get property value with default.
     *
     * ```
     * $title = $item->get('title', 'Default Title');
     * ```
     */
    public function get(string $name, string|array|null $default = null): string|array|null
    {
        return $this->$name ?? data_get($this->extra, $name, $default);
    }

    /**
     * Fluently set property value.
     *
     * ```
     * $item->set('title', 'New Title')
     *      ->set('categories', ['News', 'Updates']);
     * ```
     */
    public function set(string $name, string|array|null $value): self
    {
        if (property_exists($this, $name)) {
            $this->$name = $value;
        } else {
            $this->extra[$name] = $value;
        }

        return $this;
    }

    public function __get(string $name)
    {
        return data_get($this->extra, $name);
    }

    public function __set(string $name, string|array|null $value): void
    {
        $this->extra[$name] = $value;
    }

    public function toArray(): array
    {
        return Collection::make(get_object_vars($this))
            ->except('extra')
            ->merge($this->extra)
            ->map(fn ($value) => $value instanceof Carbon ? $value->toIso8601String() : $value)
            ->filter()
            ->toArray();
    }
}
