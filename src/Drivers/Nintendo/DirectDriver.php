<?php

declare(strict_types=1);

namespace Revolution\Feedable\Drivers\Nintendo;

use DOMDocument;
use DOMXPath;
use Exception;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Uri;
use Revolution\Feedable\Core\Contracts\FeedableDriver;
use Revolution\Feedable\Core\Elements\FeedItem;
use Revolution\Feedable\Core\Enums\Format;
use Revolution\Feedable\Core\Enums\Timezone;
use Revolution\Feedable\Core\Response\ErrorResponse;
use Revolution\Feedable\Core\Response\ResponseFactory;

class DirectDriver implements FeedableDriver
{
    protected string $baseUrl = 'https://www.nintendo.com/jp/nintendo-direct/';

    public function __invoke(Format $format = Format::RSS): Responsable
    {
        try {
            $items = cache()->flexible(
                'nintendo-direct-items',
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
            title: '任天堂 ニンテンドーダイレクト',
            home_page_url: $this->baseUrl,
            feed_url: url()->current(),
            description: '最新のニンテンドーダイレクト',
            items: $items,
        );
    }

    /**
     * @throws Exception
     */
    public function handle(): array
    {
        /**
         * baseUrlのリダイレクト先が最新のニンテンドーダイレクトページになるのでtitleとlinkを取得して返す。
         */
        $response = Http::get($this->baseUrl)->throw();

        $redirect = new DOMDocument;
        @$redirect->loadHTML($response->body());
        $xpath = new DOMXPath($redirect);
        $refresh = $xpath->query('//meta[@http-equiv="Refresh"]');

        $content = $refresh->item(0)?->getAttribute('content') ?? '';
        preg_match('/URL=(.+)$/', $content, $matches);
        if (! isset($matches[1])) {
            throw new Exception;
        }
        $link = $matches[1];

        // linkから日付
        // https://www.nintendo.com/jp/nintendo-direct/20250912/index.html
        $date = Str::of(Uri::of($link)->path())->dirname()->afterLast('/')->toString();

        if (Carbon::canBeCreatedFromFormat($date, 'Ymd')) {
            $pubDate = Carbon::createFromFormat('Ymd', $date, timezone: Timezone::AsiaTokyo->value)
                ->setTime(0, 0, 0)
                ->toRssString();
        } else {
            // linkから日付が取得できなかった場合は現在日時にする
            $pubDate = now(Timezone::AsiaTokyo->value)->toRssString();
        }

        $response = Http::get($link)->throw();

        $direct = new DOMDocument;
        @$direct->loadHTML($response->body());
        $xpath = new DOMXPath($direct);

        $descriptionNode = $xpath->query('//meta[@name="description"]');
        $description = $descriptionNode->item(0)?->getAttribute('content');

        $title = $direct->getElementsByTagName('title')->item(0)?->textContent;

        return [
            new FeedItem(
                id: $link,
                url: $link,
                title: $title,
                summary: $description,
                date_published: $pubDate,
            ),
        ];
    }
}
