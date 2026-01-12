<?php

declare(strict_types=1);

namespace Revolution\Feedable\Drivers\Yomiuri;

use Exception;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Revolution\Feedable\Core\Contracts\FeedableDriver;
use Revolution\Feedable\Core\Elements\FeedItem;
use Revolution\Feedable\Core\Enums\Format;
use Revolution\Feedable\Core\Enums\Timezone;
use Revolution\Feedable\Core\Response\ErrorResponse;
use Revolution\Feedable\Core\Response\ResponseFactory;
use Revolution\Feedable\Core\Support\AbsoluteUri;
use Symfony\Component\DomCrawler\Crawler;

class YomiuriNewsDriver implements FeedableDriver
{
    protected string $baseUrl = 'https://www.yomiuri.co.jp';

    protected string $feedUrl = 'https://www.yomiuri.co.jp/news/';

    public function __invoke(Format $format = Format::RSS): Responsable
    {
        try {
            $items = cache()->flexible(
                'yomiuri-news-items',
                [now()->plus(minutes: 10), now()->plus(minutes: 20)],
                fn () => $this->handle(),
            );
        } catch (Exception $e) {
            return new ErrorResponse(
                error: 'Whoops! Something went wrong.',
                message: $e->getMessage(),
            );
        }

        return ResponseFactory::format($format)->make(
            title: '読売新聞 ニュース速報',
            home_page_url: $this->feedUrl,
            feed_url: url()->current(),
            description: '読売新聞のニュースサイト。速報ニュースを国内、スポーツ、政治、経済、国際、環境などジャンル別の記事で紹介。',
            items: $items,
        );
    }

    public function handle(): array
    {
        $html = Http::get($this->feedUrl)->body();

        $crawler = new Crawler($html);

        $items = $crawler->filter('article.news-top-latest__list-item')->each(function (Crawler $node) {
            $titleNode = $node->filter('h3 a');
            if ($titleNode->count() === 0) {
                return null;
            }

            $title = $titleNode->text();
            $url = AbsoluteUri::resolve($this->baseUrl, $titleNode->attr('href') ?? '');

            // icon-lockedがある記事は🔐を付ける
            $isLocked = $node->filter('.icon-locked')->count() > 0;
            if ($isLocked) {
                $title = '🔐 '.$title;
            }

            // 日時を取得
            $timeNode = $node->filter('time[datetime]');
            $datePublished = null;
            if ($timeNode->count() > 0) {
                $datetime = $timeNode->attr('datetime');
                if ($datetime) {
                    $datePublished = Carbon::parse($datetime, Timezone::AsiaTokyo->value);
                }
            }

            // サムネイル画像
            $image = null;
            $imgNode = $node->filter('figure img');
            if ($imgNode->count() > 0) {
                $image = $imgNode->attr('src');
                if ($image) {
                    $image = AbsoluteUri::resolve($this->baseUrl, $image);
                }
            }

            return new FeedItem(
                id: $url,
                url: $url,
                title: $title,
                image: $image,
                date_published: $datePublished,
            );
        });

        return collect($items)->filter()->values()->all();
    }
}
