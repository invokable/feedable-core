<?php

declare(strict_types=1);

namespace Revolution\Feedable\Drivers\Laravel;

use Carbon\Carbon;
use DOMDocument;
use DOMXPath;
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

class LaravelBlogDriver implements FeedableDriver
{
    protected string $baseUrl = 'https://laravel.com/blog';

    public function __invoke(Format $format = Format::RSS): Responsable
    {
        try {
            // 不定期更新なので1時間だけキャッシュ
            $items = cache()->flexible(
                'laravel-blog-items',
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
            title: 'Laravel Official Blog',
            home_page_url: $this->baseUrl,
            feed_url: url()->current(),
            description: 'Laravel Official Blog',
            language: 'en',
            items: $items,
        );
    }

    /**
     * @throws Exception
     */
    public function handle(): array
    {
        /**
         * Timezoneはアメリカだろうけど時間は表示されてないし、厳密に扱う必要はないのでUTC。
         *
         * リンクのみで本文は含めない。
         *
         * Laravel公式なのでLivewireが使われている。
         * htmlで取得できるけど難しいのはtailwindばかりで特徴的なidやclass名がほとんどないこと。
         * <div id="posts-section">内のa要素を集めればいいはず。posts-section内のカテゴリーはbuttonで実装してる。
         *
         * 一記事分の構造例:
         * <a
         * href="/blog/open-source-as-a-way-of-giving-back-the-artisan-of-the-day-is-daniel-petrica"
         * class="cursor-pointer py-4 md:py-10 px-4 xl:px-16 relative group hover:bg-white
         * border-b border-transparent hover:border-sand-light-7
         * flex flex-col lg:flex-row gap-2 lg:gap-8 z-10
         * transition-colors"
         * >
         * <!-- title: first on mobile, left on desktop -->
         * <div class="order-1 lg:order-1 flex-1">
         * <span class="text-sand-light-12 text-balance text-base font-medium leading-normal group-hover:text-black transition-colors" style="text-wrap: balance;">
         * Open Source as a Way of Giving Back: The Artisan of the Day Is Daniel&nbsp;Petrica.
         * </span>
         * </div>
         *
         * <!-- category: second on mobile, left on desktop -->
         * <div class="order-2 lg:order-2 mb-1 lg:mb-0 lg:w-40">
         * <span class="text-sand-light-11 text-base font-medium leading-normal">
         * Community
         * </span>
         * <span class="inline-block ml-8 md:hidden text-sand-light-11 text-nowrap text-base font-medium leading-normal">
         * December 19, 2025
         * </span>
         * </div>
         *
         * <!-- date: third on mobile, right on desktop -->
         * <div class="hidden md:block order-3 lg:order-3 lg:w-32 lg:text-right">
         * <span class="text-sand-light-11 text-nowrap text-base font-medium leading-normal">
         * December 19, 2025
         * </span>
         * </div>
         * </a>
         */
        $response = Http::get($this->baseUrl);

        if ($response->failed()) {
            throw new Exception;
        }

        if (app()->isLocal()) {
            Storage::put('laravel/blog.html', $response->body());
        }

        $dom = new DOMDocument;
        @$dom->loadHTML($response->body());
        $xpath = new DOMXPath($dom);

        /**
         * <div
         * id="posts-section"
         * x-data
         *
         * @posts-updated.window="setTimeout(() => $el.scrollIntoView({ behavior: 'smooth', block: 'start' }), 100)"
         * >
         */
        $postsSection = $xpath->query('//div[@id="posts-section"]')->item(0);
        if (! $postsSection) {
            throw new Exception;
        }

        $nodes = $xpath->query('.//a', $postsSection);
        $items = [];

        foreach ($nodes as $node) {
            $url = AbsoluteUri::resolve($this->baseUrl, $node->getAttribute('href'));

            if (Str::contains($url, '/blog/category/')) {
                // カテゴリーページへのリンクはスキップ
                continue;
            }

            $titleNode = $xpath->query('.//div[contains(@class, "order-1")]//span', $node)->item(0);
            $categoryNode = $xpath->query('.//div[contains(@class, "order-2")]//span', $node)->item(0);
            $dateNode = $xpath->query('.//div[contains(@class, "order-3")]//span', $node)->item(0);
            $title = trim($titleNode?->textContent ?? 'No title');
            $category = trim($categoryNode?->textContent ?? 'Uncategorized');
            $dateText = trim($dateNode?->textContent) ?? '';
            $date = Carbon::parse($dateText, timezone: Timezone::UTC->value);

            $items[] = new FeedItem(
                id: $url,
                url: $url,
                title: $title,
                summary: "[$category] $title",
                date_published: $date,
                tags: [$category],
            );
        }

        return $items;
    }
}
