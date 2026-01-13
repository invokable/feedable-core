<?php

declare(strict_types=1);

namespace Revolution\Feedable\Drivers\Nicovideo;

use Exception;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Revolution\Feedable\Core\Contracts\FeedableDriver;
use Revolution\Feedable\Core\Elements\Author;
use Revolution\Feedable\Core\Elements\FeedItem;
use Revolution\Feedable\Core\Enums\Format;
use Revolution\Feedable\Core\Enums\Timezone;
use Revolution\Feedable\Core\Response\ErrorResponse;
use Revolution\Feedable\Core\Response\ResponseFactory;
use Revolution\Feedable\Core\Support\AbsoluteUri;
use Revolution\Feedable\Drivers\Nicovideo\Enums\Category;
use Symfony\Component\DomCrawler\Crawler;

class NicoMangaDriver implements FeedableDriver
{
    protected string $baseUrl = 'https://manga.nicovideo.jp/manga/list';

    protected string $category;

    public function __invoke(Category $category = Category::All, Format $format = Format::RSS): Responsable
    {
        $this->category = $category->value;

        try {
            // 1日中更新かつ更新量が多いのでキャッシュ時間は短め
            // それでも取りこぼす場合はカテゴリーごとに読むのを推奨。
            $items = cache()->flexible(
                'nicovideo-manga-items:'.$category->value,
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
            title: 'ニコニコ静画マンガ '.$category->value,
            home_page_url: $this->baseUrl,
            feed_url: url()->current(),
            description: 'ニコニコ静画マンガの最近更新された作品',
            items: $items,
        );
    }

    /**
     * @throws Exception
     */
    public function handle(): array
    {
        // 本来の「すべて」はcategory指定なしだけどallでも結果は同じ
        $response = Http::get($this->baseUrl.'?category='.$this->category.'&sort=manga_updated');

        if ($response->failed()) {
            throw new Exception('Failed to fetch Nico manga page');
        }

        if (app()->runningUnitTests()) {
            Storage::put("nicovideo/manga/$this->category.html", $response->body());
        }

        $crawler = new Crawler($response->body());

        return $crawler->filter('li.mg_item')
            ->each(function (Crawler $node) {
                $titleNode = $node->filter('div.title a');
                $title = $titleNode->text();
                $link = Str::chopEnd($titleNode->attr('href'), '?track=list');
                $link = AbsoluteUri::resolve($this->baseUrl, $link);

                $description = $node->filter('div.mg_body div.description')->text();

                $author = $node->filter('span.mg_author')->text();
                $author = Str::chopStart($author, '作者:');

                $image = $node->filter('img.thumb_image')->attr('src');

                $updatedNode = $node->filter('span.updated');
                $updatedText = $updatedNode->text();
                $updatedText = Str::of($updatedText)->before('更新')->trim()->value();
                $updated = Carbon::parse($updatedText, timezone: Timezone::AsiaTokyo->value);

                // 個別話数へのURLはなくnewで最新話へのリンクしかないのでフィードリーダー側で重複しないように日付を付ける
                $link = $link.'/new?ep='.$updated->format('Ymd');

                return new FeedItem(
                    id: $link,
                    url: $link,
                    title: $title,
                    summary: $description,
                    image: $image,
                    date_published: $updated,
                    authors: [Author::make(name: $author)->toArray()],
                );
            });
    }
}
