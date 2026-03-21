<?php

declare(strict_types=1);

namespace Revolution\Feedable\Drivers\Asahi;

use const Dom\HTML_NO_DEFAULT_NS;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Dom\HTMLDocument;
use Dom\Node;
use Exception;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Revolution\Feedable\Core\Contracts\FeedableDriver;
use Revolution\Feedable\Core\Elements\FeedItem;
use Revolution\Feedable\Core\Enums\Format;
use Revolution\Feedable\Core\Enums\Timezone;
use Revolution\Feedable\Core\Response\ErrorResponse;
use Revolution\Feedable\Core\Response\ResponseFactory;
use Revolution\Feedable\Core\Support\AbsoluteUri;

class AsahiNewsDriver implements FeedableDriver
{
    protected string $baseUrl = 'https://www.asahi.com';

    /*
     * 有料記事をフィードに含めるかどうか
     */
    protected bool $compact = false;

    public function __invoke(Request $request, Format $format = Format::RSS): Responsable
    {
        $this->compact = $request->has('compact');

        try {
            $cached = cache()->flexible(
                'asahi-news-items',
                [now()->plus(minutes: 15), now()->plus(minutes: 30)],
                fn () => array_map(
                    fn (FeedItem $item) => $item->toArray(),
                    $this->handle(),
                ),
            );

            $items = array_map(FeedItem::fromArray(...), $cached);
        } catch (Exception $e) {
            return new ErrorResponse(
                error: 'Whoops! Something went wrong.',
                message: $e->getMessage(),
            );
        }

        return ResponseFactory::format($format)->make(
            title: '朝日新聞：速報・新着ニュース一覧',
            home_page_url: $this->baseUrl.'/news/',
            feed_url: url()->current(),
            description: '朝日新聞デジタルの速報・新着ニュース一覧',
            language: 'ja',
            items: $items,
        );
    }

    /**
     * @throws Exception
     */
    public function handle(): array
    {
        $response = Http::get($this->baseUrl.'/news/');

        if ($response->failed()) {
            throw new Exception('Failed to fetch Asahi news page');
        }

        if (app()->runningUnitTests()) {
            Storage::put('asahi/news.html', $response->body());
        }

        $dom = HTMLDocument::createFromString(
            source: $response->body(),
            options: LIBXML_HTML_NOIMPLIED | LIBXML_NOERROR | HTML_NO_DEFAULT_NS
        );

        // <ul class="List">内の<li>要素を取得
        $listNode = $dom->querySelector('ul.List');
        if (! $listNode) {
            throw new Exception('List element not found');
        }

        $nodes = $listNode->querySelectorAll('li');
        $items = [];
        $now = now(Timezone::AsiaTokyo);
        $yesterday = today(Timezone::AsiaTokyo)->minus(days: 1);

        foreach ($nodes as $node) {
            $anchor = $node->querySelector('a');
            if (! $anchor) {
                continue;
            }

            $href = $anchor->getAttribute('href');
            $url = AbsoluteUri::resolve($this->baseUrl, $href);

            // <span class="Time">から日時を取得
            $timeNode = $anchor->querySelector('span.Time');
            $timeText = trim($timeNode?->textContent ?? '');

            $date = $this->parseDateTime($timeText, $now);

            // 昨日より前の記事はスキップ
            if ($date->lt($yesterday)) {
                continue;
            }

            // タイトルはアンカー内のテキスト（Time spanより前の部分）
            $title = $this->extractTitle($anchor);

            $key_gold = $anchor->querySelector('span.KeyGold') !== null;
            if ($key_gold) {
                if ($this->compact) {
                    continue;
                }

                $title = '🔐 '.$title;
            }

            $items[] = new FeedItem(
                id: $url,
                url: $url,
                title: $title,
                date_published: $date,
            );
        }

        return $items;
    }

    /**
     * 日時文字列をパース
     * 今日の記事: "10:00"
     * 昨日以前: "1/10 21:30"
     */
    protected function parseDateTime(string $timeText, CarbonInterface $now): CarbonInterface
    {
        if (preg_match('/^(\d{1,2}):(\d{2})$/', $timeText, $matches)) {
            // 時間のみ = 今日
            return $now->copy()->setTime((int) $matches[1], (int) $matches[2], 0);
        }

        if (preg_match('/^(\d{1,2})\/(\d{1,2})\s+(\d{1,2}):(\d{2})$/', $timeText, $matches)) {
            // 月/日 時:分
            $month = (int) $matches[1];
            $day = (int) $matches[2];
            $hour = (int) $matches[3];
            $minute = (int) $matches[4];

            $year = $now->year;
            // 12月に1月の記事がある場合（年またぎ対応）
            if ($now->month === 1 && $month === 12) {
                $year--;
            }

            return Carbon::create($year, $month, $day, $hour, $minute, 0, Timezone::AsiaTokyo->value);
        }

        return $now->copy();
    }

    /**
     * アンカー要素からタイトルを抽出（Time spanなどを除く）
     */
    protected function extractTitle(Node $anchor): string
    {
        $title = '';
        foreach ($anchor->childNodes as $child) {
            if ($child->nodeType === XML_TEXT_NODE) {
                $title .= $child->textContent;
            }
        }

        return trim($title) ?: 'No title';
    }
}
