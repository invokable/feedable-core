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
            ->filter()
            ->toArray();
    }
}
