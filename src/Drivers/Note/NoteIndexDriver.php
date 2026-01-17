<?php

declare(strict_types=1);

namespace Revolution\Feedable\Drivers\Note;

use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\Support\Responsable;
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
use Revolution\Salvager\AgentBrowser;
use Revolution\Salvager\Facades\Salvager;
use Symfony\Component\DomCrawler\Crawler;

class NoteIndexDriver implements FeedableDriver
{
    protected string $baseUrl = 'https://note.com/';

    public function __invoke(Format $format = Format::RSS): Responsable
    {
        try {
            $items = cache()->flexible(
                'note-index-items',
                [now()->plus(hours: 3), now()->plus(hours: 4)],
                fn () => $this->handle(),
            );
        } catch (Exception $e) {
            return new ErrorResponse(
                error: 'Whoops! Something went wrong.',
                message: $e->getMessage(),
            );
        }

        return ResponseFactory::format($format)->make(
            title: 'note 注目記事',
            home_page_url: $this->baseUrl,
            feed_url: url()->current(),
            description: 'note 注目記事',
            items: $items,
        );
    }

    public function handle(): array
    {
        // agent-browserで取得するサンプル。
        Salvager::agent(function (AgentBrowser $agent) use (&$html) {
            // ブラウザで開く
            $agent->open($this->baseUrl);
            // ページの読み込み完了を待つ
            $agent->run('wait --load networkidle');

            // HTMLを取得
            // css=はCSSセレクタで要素を指定できる
            // xpath=はXPathで要素を指定
            $html = $agent->html('css=body');

            cache(['note-index-html' => $html], now()->plus(hours: 1));

            // ここで複雑なことはせずhtmlだけ取得してすぐに抜ける

            // ブラウザを閉じる
            $agent->close();
        });

        if (app()->runningUnitTests()) {
            Storage::put('note/index.html', $html);
        }

        $crawler = new Crawler($html);

        $items = $crawler->filter('section.m-horizontalScrollingList')
            ->first()
            ->filter('div.m-largeNoteWrapper__card')
            ->each(function (Crawler $node) {
                $title = $node->filter('h3')->text();
                $link = $node->filter('a')->attr('href');
                if (empty($link)) {
                    return null;
                }
                $link = AbsoluteUri::resolve($this->baseUrl, $link);

                $image = $node->filter('img.m-thumbnail__image')->attr('src');
                if (Str::startsWith($image, 'data:')) {
                    $image = null;
                }

                $author = $node->filter('span.o-verticalTimeLineNote__userText')->text();

                $date = $node->filter('time')->text();
                // ○時間前、○日前、○年前などをCarbon::parse可能な英語に
                $date = str_replace(
                    ['前', '時間', '分', '日', '週間', 'か月', '年'],
                    [' ago', ' hours', ' minutes', ' days', ' weeks', ' months', ' years'],
                    $date,
                );

                return new FeedItem(
                    id: $link,
                    url: $link,
                    title: $title,
                    image: $image,
                    date_published: Carbon::parse($date, Timezone::AsiaTokyo->value),
                    authors: [Author::make(name: $author)->toArray()],
                );
            });

        return collect($items)->filter()->toArray();
    }
}
