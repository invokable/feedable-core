<?php

declare(strict_types=1);

namespace Revolution\Feedable\Drivers\MagazinePocket;

use const Dom\HTML_NO_DEFAULT_NS;

use Carbon\Carbon;
use Dom\HTMLDocument;
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

class MagazinePocketDriver implements FeedableDriver
{
    protected string $baseUrl = 'https://pocket.shonenmagazine.com/';

    public function __invoke(Format $format = Format::RSS): Responsable
    {
        try {
            // 0時更新なので翌日までキャッシュ
            $items = cache()->remember(
                'shonenmagazine-pocket-items',
                today(Timezone::AsiaTokyo)->plus(days: 1),
                fn () => $this->handle(),
            );
        } catch (Exception $e) {
            return new ErrorResponse(
                error: 'Whoops! Something went wrong.',
                message: $e->getMessage(),
            );
        }

        return ResponseFactory::format($format)->make(
            title: 'マガポケ',
            home_page_url: $this->baseUrl,
            feed_url: url()->current(),
            description: 'マガポケの今日の更新作品',
            items: $items,
        );
    }

    /**
     * @throws Exception
     */
    public function handle(): array
    {
        /**
         * 作品ごとの個別RSSはあるけど全体の新着情報はなさそう。
         * コミックDAYSと同様にトップページから取得する。
         *
         * Nuxt製っぽいけどhtmlだけでも最低限の情報は取得できる。
         */
        $response = Http::get($this->baseUrl);

        if ($response->failed()) {
            throw new Exception;
        }

        if (app()->runningUnitTests()) {
            Storage::put('pocket/home.html', $response->body());
        }

        $dom = HTMLDocument::createFromString(
            source: $response->body(),
            options: LIBXML_HTML_NOIMPLIED | LIBXML_NOERROR | HTML_NO_DEFAULT_NS
        );

        // <span class="p-index-update__date p-index-sec__ttl">12/19</span>
        $dateNode = $dom->querySelector('span.p-index-update__date');
        if (! $dateNode) {
            throw new Exception;
        }
        $dateText = trim($dateNode->textContent); // "12/19"

        // 更新時間は0時のようなので今日の年＋今日の日付＋0時にする
        // 0時更新なので年跨ぎは不要なはず
        $date = Carbon::create(now(Timezone::AsiaTokyo)->year, Str::before($dateText, '/'), Str::after($dateText, '/'), 0, 0, 0, timezone: Timezone::AsiaTokyo->value);

        // <li class="p-index-update__item">が一作品分。更新作品にしかこのクラスは使われてないのでこれだけ取得すればいい
        $itemNodes = $dom->querySelectorAll('li.p-index-update__item');
        $items = [];

        foreach ($itemNodes as $itemNode) {
            $url = $itemNode->querySelector('a')?->getAttribute('href');
            $url = AbsoluteUri::resolve($this->baseUrl, $url);

            $titleNode = $itemNode->querySelector('h3.c-comic-item__ttl');
            $title = $titleNode ? trim($titleNode->textContent) : 'No Title';

            $descriptionNode = $itemNode->querySelector('div.c-comic-item__description');
            $summary = $descriptionNode ? trim($descriptionNode->textContent) : '';

            $imgNode = $itemNode->querySelector('img');
            $image = $imgNode?->getAttribute('src');

            $items[] = new FeedItem(
                id: $url,
                url: $url,
                title: $title,
                summary: $summary,
                image: $image,
                date_published: $date,
            );
        }

        return $items;
    }
}
