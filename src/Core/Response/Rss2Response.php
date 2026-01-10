<?php

declare(strict_types=1);

namespace Revolution\Feedable\Core\Response;

use Carbon\Carbon;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\Response;

readonly class Rss2Response implements Responsable
{
    public function __construct(
        protected ?string $title = null,
        protected ?string $description = null,
        protected ?string $link = null,
        protected ?string $feed_url = null,
        protected ?string $pubDate = null,
        protected ?string $image = null,
        protected ?array $items = null,
        protected string $language = 'ja',
        protected int $ttl = 5,
    ) {
        //
    }

    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function toResponse($request): Response
    {
        $rss = Blade::render(File::get(__DIR__.'/views/rss2.blade.php'), [
            'title' => $this->title,
            'description' => $this->description,
            'link' => $this->link,
            'feed_link' => $this->feed_url,
            'pubDate' => $this->pubDate,
            'image' => $this->image,
            'language' => $this->language,
            'ttl' => $this->ttl,
            'items' => $this->items(),
        ]);

        return response($rss)
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    protected function items(): array
    {
        return collect($this->items)
            ->map(fn ($item) => is_array($item) ? $item : $item->toArray())
            ->map(fn ($item) => array_filter($item))
            ->map(function ($item) {
                if (Arr::exists($item, 'date_published')) {
                    $date_published = $item['date_published'];
                    if (! $date_published instanceof Carbon) {
                        $date_published = Carbon::parse($date_published);
                    }
                    $item['date_published'] = $date_published->toRssString();
                }
                if (Arr::exists($item, 'date_modified')) {
                    $date_modified = $item['date_modified'];
                    if (! $date_modified instanceof Carbon) {
                        $date_modified = Carbon::parse($date_modified);
                    }
                    $item['date_modified'] = $date_modified->toRssString();
                }

                return $item;
            })
            ->all();
    }
}
