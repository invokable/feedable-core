<?php

declare(strict_types=1);

namespace Revolution\Feedable\Drivers\Asahi;

use DOMDocument;
use DOMNode;
use DOMXPath;
use Exception;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Support\Carbon;
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

    public function __invoke(Format $format = Format::RSS): Responsable
    {
        try {
            $items = cache()->flexible(
                'asahi-news-items',
                [now()->plus(minutes: 15), now()->plus(minutes: 30)],
                fn () => $this->handle(),
            );
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

        $dom = new DOMDocument;
        @$dom->loadHTML($response->body());
        $xpath = new DOMXPath($dom);

        // <ul class="List">内の<li>要素を取得
        $listNode = $xpath->query('//ul[@class="List"]')->item(0);
        if (! $listNode) {
            throw new Exception('List element not found');
        }

        $nodes = $xpath->query('.//li', $listNode);
        $items = [];
        $now = now(Timezone::AsiaTokyo);
        $yesterday = today(Timezone::AsiaTokyo)->minus(days: 1);

        foreach ($nodes as $node) {
            $anchor = $xpath->query('.//a', $node)->item(0);
            if (! $anchor) {
                continue;
            }

            $href = $anchor->getAttribute('href');
            $url = AbsoluteUri::resolve($this->baseUrl, $href);

            // <span class="Time">から日時を取得
            $timeNode = $xpath->query('.//span[@class="Time"]', $anchor)->item(0);
            $timeText = trim($timeNode?->textContent ?? '');

            $date = $this->parseDateTime($timeText, $now);

            // 昨日より前の記事はスキップ
            if ($date->lt($yesterday)) {
                continue;
            }

            // タイトルはアンカー内のテキスト（Time spanより前の部分）
            $title = $this->extractTitle($anchor, $xpath);

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
    protected function parseDateTime(string $timeText, Carbon $now): Carbon
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
    protected function extractTitle(DOMNode $anchor, DOMXPath $xpath): string
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
