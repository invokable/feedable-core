<?php

declare(strict_types=1);

namespace Revolution\Feedable\Drivers\JumpPlus;

use Carbon\Carbon;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Exception;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Revolution\Feedable\Core\Contracts\FeedableDriver;
use Revolution\Feedable\Core\Enums\Format;
use Revolution\Feedable\Core\Enums\Timezone;
use Revolution\Feedable\Core\JsonFeed\JsonFeed;
use Revolution\Feedable\Core\Response\ErrorResponse;
use Revolution\Feedable\Core\Support\RSS;

class JumpPlusDriver implements FeedableDriver
{
    protected string $baseUrl = 'https://shonenjumpplus.com/';

    protected string $rssUrl = 'https://shonenjumpplus.com/rss';

    /**
     * @throws Exception
     */
    public function __invoke(Format $format = Format::RSS): Responsable|Response
    {
        try {
            // 0時更新なので翌日までキャッシュ
            $xml = cache()->remember(
                'jump-plus-daily-rss',
                today(Timezone::AsiaTokyo)->plus(days: 1),
                fn () => $this->handle(),
            );
        } catch (Exception $e) {
            return new ErrorResponse(
                error: 'Whoops! Something went wrong.',
                message: $e->getMessage(),
            );
        }

        if ($format === Format::JSON) {
            $json = app(JsonFeed::class)->convert($xml, $this->rssUrl);

            return response($json)
                ->header('Content-Type', 'application/json; charset=UTF-8');
        }

        return response($xml)
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    /**
     * @throws Exception
     */
    public function handle(): string
    {
        $links = $this->getDailySeries();

        // 公式RSSから$linksに含まれてるURLだけ返す
        $response = Http::get($this->rssUrl)->throw();

        $rss = RSS::filterLinks($response->body(), $links);

        // 元RSSのpubDateが変な時間なので日本時間0時に変更
        // 22日0時が<pubDate>Sun, 21 Dec 2025 15:00:00 +0000</pubDate>になっている
        $rss = RSS::each($rss, function (DOMElement $node) {
            $pubDateNode = $node->getElementsByTagName('pubDate')->item(0);
            if ($pubDateNode) {
                $pubDate = Carbon::parse($pubDateNode->nodeValue, Timezone::UTC->value)
                    ->setTimezone(Timezone::AsiaTokyo->value)
                    ->startOfDay()
                    ->toRssString();
                $pubDateNode->nodeValue = $pubDate;
            }
        });

        return $rss;
    }

    /**
     * @throws Exception
     */
    protected function getDailySeries(): ?array
    {
        $response = Http::get($this->baseUrl)->throw();

        if (app()->runningUnitTests()) {
            Storage::put('jumpplus/daily.html', $response->body());
        }

        $dom = new DOMDocument;
        @$dom->loadHTML($response->body());
        $xpath = new DOMXPath($dom);
        $nodes = $xpath->query('//li[contains(@class, "daily-series-item")]/a');
        $links = [];
        foreach ($nodes as $node) {
            $links[] = $node->getAttribute('href');
        }

        return $links;
    }
}
