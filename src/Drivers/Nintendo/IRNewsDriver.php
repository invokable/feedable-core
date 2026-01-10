<?php

declare(strict_types=1);

namespace Revolution\Feedable\Drivers\Nintendo;

use DOMDocument;
use Exception;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Uri;
use Revolution\Feedable\Core\Contracts\FeedableDriver;
use Revolution\Feedable\Core\Elements\FeedItem;
use Revolution\Feedable\Core\Enums\Format;
use Revolution\Feedable\Core\Enums\Timezone;
use Revolution\Feedable\Core\Response\ErrorResponse;
use Revolution\Feedable\Core\Response\ResponseFactory;

class IRNewsDriver implements FeedableDriver
{
    protected string $baseUrl = 'https://www.nintendo.co.jp/ir/news/index.html';

    protected string $xmlUrl = 'https://www.nintendo.co.jp/corporate/common/data/news_jp.xml';

    public function __invoke(Format $format = Format::RSS): Responsable
    {
        try {
            $items = cache()->flexible(
                'nintendo-ir-news-items',
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
            title: '任天堂 IRニュース',
            home_page_url: $this->baseUrl,
            feed_url: url()->current(),
            description: '任天堂のIRニュース',
            items: $items,
        );
    }

    /**
     * @throws Exception
     */
    public function handle(): array
    {
        /**
         * xmlを元にJavaScriptで動的に生成されているのでxmlを直接取得してRSSに変換。
         *
         * RSSとは違う独自形式。<item>が並ぶ形式。
         * <?xml version="1.0" encoding="UTF-8" standalone="yes"?>
         * <items><item><date_updated>2025.12.4</date_updated><date_released>2025.12.4</date_released><name_main>2026年3月期 第3四半期 決算発表予定日</name_main><url>/ir/schedule/index.html</url><year>2025</year><category>お知らせ</category><news_type>2</news_type></item>
         *
         * 時間情報がないので00:00として扱う。
         */
        $response = Http::get($this->xmlUrl)->throw();

        $dom = new DOMDocument;
        @$dom->loadXML($response->body());
        $xmlItems = $dom->getElementsByTagName('item');

        $items = [];
        foreach ($xmlItems as $item) {
            /** @var DOMDocument $item */
            $title = $item->getElementsByTagName('name_main')->item(0)?->textContent;
            if (empty($title)) {
                // item内の<sub_links><item>だった場合はスキップ
                continue;
            }

            $link = $item->getElementsByTagName('url')->item(0)->textContent;

            $category = $item->getElementsByTagName('category')->item(0)->textContent;

            $dateReleased = $item->getElementsByTagName('date_released')->item(0)->textContent;
            $pubDate = Carbon::createFromFormat('Y.n.j', $dateReleased, timezone: Timezone::AsiaTokyo->value)->setTime(0, 0, 0);

            $url = Uri::of('https://www.nintendo.co.jp')->withPath($link)->value();

            $items[] = new FeedItem(
                // 同じURLが多いので日付を付与してユニークにする
                id: $url.'#'.$pubDate->format('Ymd'),
                url: $url.'#'.$pubDate->format('Ymd'),
                external_url: $url,
                title: $title,
                summary: $title,
                date_published: $pubDate->toRssString(),
                tags: [$category],
            );

            if (count($items) >= 100) {
                // 1997年からのニュースが全て含まれているので最新100件のみ
                break;
            }
        }

        return $items;
    }
}
