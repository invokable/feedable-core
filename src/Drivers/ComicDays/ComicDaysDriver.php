<?php

declare(strict_types=1);

namespace Revolution\Feedable\Drivers\ComicDays;

use Carbon\Carbon;
use DOMDocument;
use DOMXPath;
use Exception;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Revolution\Feedable\Core\Contracts\FeedableDriver;
use Revolution\Feedable\Core\Elements\FeedItem;
use Revolution\Feedable\Core\Enums\Format;
use Revolution\Feedable\Core\Enums\Timezone;
use Revolution\Feedable\Core\Response\ErrorResponse;
use Revolution\Feedable\Core\Response\ResponseFactory;

class ComicDaysDriver implements FeedableDriver
{
    protected string $baseUrl = 'https://comic-days.com/';

    public function __invoke(Format $format = Format::RSS): Responsable
    {
        try {
            // 12時更新なので翌日までキャッシュ
            $items = cache()->remember(
                'comic-days-items',
                Carbon::tomorrow(Timezone::AsiaTokyo->value)->addHours(12),
                fn () => $this->handle(),
            );
        } catch (Exception $e) {
            return new ErrorResponse(
                error: 'Whoops! Something went wrong.',
                message: $e->getMessage(),
            );
        }

        return ResponseFactory::format($format)->make(
            title: 'コミックDAYS - 今日の無料連載',
            home_page_url: $this->baseUrl,
            feed_url: url()->current(),
            description: 'コミックDAYSの今日更新された無料連載の最新話一覧',
            items: $items,
        );
    }

    /**
     * @throws Exception
     */
    public function handle(): array
    {
        /**
         * 公式RSSには連載作の最新話しか含まれていない。
         * 新しく無料で読めるようになった話数は取得できない。
         * 公式RSSから減らす方法は使えないのでトップページから取得する。
         *
         * <section id="days-original">がオリジナルを含む無料連載のエリア
         * その中に曜日毎のスライドがある。htmlは今日の曜日が先頭に来る。
         * <div id="days-original-sunday">
         * <div id="days-original-monday">
         * 今日の更新分だけのRSSにするので「days-original-」を含む最初の要素を取得。
         * 曜日毎の中の<a class="gtm-top-days-original-item">が作品の情報
         * a.hrefからlink、img.srcからthumbnail, h3のtitle、pのdescriptionを取得。
         * pubDateは更新時間が昼12時固定なので今日の日付＋12時にする
         */
        $response = Http::get($this->baseUrl);

        if ($response->failed()) {
            throw new Exception;
        }

        if (app()->isLocal()) {
            Storage::put('comic-days/home.html', $response->body());
        }

        $dom = new DOMDocument;
        @$dom->loadHTML($response->body());
        $xpath = new DOMXPath($dom);
        $sectionNodes = $xpath->query('//section[@id="days-original"]//div[starts-with(@id, "days-original-")]');

        if ($sectionNodes->length === 0) {
            throw new Exception;
        }

        $firstSection = $sectionNodes->item(0);
        $linkNodes = $xpath->query('.//a[@class="gtm-top-days-original-item"]', $firstSection);
        $items = [];
        $today = now(Timezone::AsiaTokyo->value)->setTime(12, 0, 0);

        foreach ($linkNodes as $linkNode) {
            $link = $linkNode->getAttribute('href');
            $titleNode = $xpath->query('.//h3', $linkNode)->item(0);
            $descriptionNode = $xpath->query('.//p', $linkNode)->item(0);
            $imgNode = $xpath->query('.//img', $linkNode)->item(0);
            $thumbnail = $imgNode?->getAttribute('src');

            $items[] = new FeedItem(
                id: $link,
                url: $link,
                title: $titleNode ? trim($titleNode->textContent) : 'No Title',
                summary: $descriptionNode ? trim($descriptionNode->textContent) : '',
                image: $thumbnail,
                date_published: $today,
            );
        }

        return $items;
    }
}
