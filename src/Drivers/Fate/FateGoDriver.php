<?php

declare(strict_types=1);

namespace Revolution\Feedable\Drivers\Fate;

use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Revolution\Feedable\Core\Contracts\FeedableDriver;
use Revolution\Feedable\Core\Elements\FeedItem;
use Revolution\Feedable\Core\Enums\Format;
use Revolution\Feedable\Core\Enums\Timezone;
use Revolution\Feedable\Core\Response\ErrorResponse;
use Revolution\Feedable\Core\Response\ResponseFactory;
use Revolution\Feedable\Core\Support\AbsoluteUri;
use Revolution\Feedable\Drivers\Fate\Enums\Category;
use Symfony\Component\DomCrawler\Crawler;

class FateGoDriver implements FeedableDriver
{
    protected string $baseUrl = 'https://news.fate-go.jp/';

    protected string $category;

    public function __invoke(Category $category = Category::News, Format $format = Format::RSS): Responsable
    {
        $this->category = $category->value;

        try {
            $items = cache()->flexible(
                'fgo-news-items:'.$category->value,
                [now()->plus(hours: 1), now()->plus(hours: 2)],
                fn () => $this->handle(),
            );
        } catch (Exception $e) {
            return new ErrorResponse(
                error: 'Whoops! Something went wrong.',
                message: $e->getMessage(),
            );
        }

        return ResponseFactory::format($format)->make(
            title: 'FGOニュース '.$category->value,
            home_page_url: $this->baseUrl,
            feed_url: url()->current(),
            description: 'FGOニュース',
            items: $items,
        );
    }

    /**
     * @throws Exception
     */
    public function handle(): array
    {
        $url = $this->category !== Category::News->value ? $this->baseUrl.$this->category.'/' : $this->baseUrl;

        $response = Http::get($url);

        if ($response->failed()) {
            throw new Exception('Failed to fetch FGO news page');
        }

        if (app()->runningUnitTests()) {
            Storage::put("fgo/$this->category.html", $response->body());
        }

        $crawler = new Crawler($response->body());

        return $crawler->filter('ul.list_news li')
            ->each(function (Crawler $node) {
                $title = $node->filter('p.title')->text();
                if (empty($title)) {
                    return null;
                }

                $date = explode('.', $node->filter('p.date')->text(now(Timezone::AsiaTokyo)->format('Y.m.d')));
                $date_published = Carbon::create(year: $date[0], month: $date[1], day: $date[2], timezone: Timezone::AsiaTokyo->value);

                $link = rescue(fn () => $node->filter('a')->first()->attr('href'));
                if (empty($link)) {
                    return null;
                }
                if (Str::startsWith($link, '/info/')) {
                    $link .= '#:~:text='.rawurlencode($title);
                }
                $link = AbsoluteUri::resolve($this->baseUrl, $link);

                return new FeedItem(
                    id: $link,
                    url: $link,
                    title: $title,
                    date_published: $date_published,
                );
            });
    }
}
