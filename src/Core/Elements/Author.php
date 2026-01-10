<?php

declare(strict_types=1);

namespace Revolution\Feedable\Core\Elements;

use Illuminate\Contracts\Support\Arrayable;

class Author implements Arrayable
{
    public function __construct(
        public ?string $name = null,
        public ?string $url = null,
        public ?string $avatar = null,
    ) {
        //
    }

    public static function make(
        ?string $name = null,
        ?string $url = null,
        ?string $avatar = null,
    ): static {
        return new static($name, $url, $avatar);
    }

    /**
     * Convert the object to an array.
     */
    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'url' => $this->url,
            'avatar' => $this->avatar,
        ]);
    }
}
