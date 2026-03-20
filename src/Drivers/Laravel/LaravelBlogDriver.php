<?php

declare(strict_types=1);

namespace Revolution\Feedable\Drivers\Laravel;

use const Dom\HTML_NO_DEFAULT_NS;

use Carbon\Carbon;
use Dom\HTMLDocument;
use Exception;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Uri;
use Revolution\Feedable\Core\Contracts\FeedableDriver;
use Revolution\Feedable\Core\Elements\Author;
use Revolution\Feedable\Core\Elements\FeedItem;
use Revolution\Feedable\Core\Enums\Format;
use Revolution\Feedable\Core\Enums\Timezone;
use Revolution\Feedable\Core\Response\ErrorResponse;
use Revolution\Feedable\Core\Response\ResponseFactory;

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
         * Laravel 13リリースに合わせてLivewireからInertiaに移行された。
         * 記事データはInertiaの<script type="application/json">内のJSONに含まれている。
         * Inertia v3ではdata-page属性が削除されるのでtype属性で探してprops.posts.dataの有無で判定。
         * props.posts.dataに記事の配列があり、各記事は以下のフィールドを持つ:
         * id, title, slug, excerpt, published_at_iso, category.name, authors[].name, image_url
         *
         * highlightedPostやfeaturedPostsもあるがposts.dataに含まれているので重複なし。
         *
         * 一記事分の構造例:
         * {
         *   "id": 431,
         *   "title": "Which AI Model Is Best for Laravel?",
         *   "slug": "which-ai-model-is-best-for-laravel",
         *   "excerpt": "We benchmarked 6 AI models on 17 real Laravel tasks...",
         *   "published_at": "Mar 18, 2026",
         *   "published_at_iso": "2026-03-18T14:47:42.000000Z",
         *   "image_url": "https://...png",
         *   "category": {"name": "Laravel Framework", "slug": "laravel-framework"},
         *   "authors": [{"name": "Pushpak Chhajed"}]
         * }
         */
        $response = Http::get($this->baseUrl);

        if ($response->failed()) {
            throw new Exception;
        }

        if (app()->runningUnitTests()) {
            Storage::put('laravel/blog.html', $response->body());
        }

        $dom = HTMLDocument::createFromString(
            source: $response->body(),
            options: LIBXML_HTML_NOIMPLIED | LIBXML_NOERROR | HTML_NO_DEFAULT_NS
        );

        // Inertiaのページデータを取得。v3でdata-page属性が削除されるのでtype属性で探す。
        $posts = [];
        foreach ($dom->querySelectorAll('script[type="application/json"]') as $script) {
            $pageData = json_decode($script->textContent, associative: true);
            $posts = data_get($pageData, 'props.posts.data', []);
            if ($posts) {
                break;
            }
        }

        if (! $posts) {
            throw new Exception;
        }
        $items = [];

        foreach ($posts as $post) {
            $url = Uri::of('https://laravel.com')->withPath('blog/'.$post['slug'])->value();
            $title = trim($post['title'] ?? 'No title');
            $category = data_get($post, 'category.name', 'Uncategorized');
            $authors = collect(data_get($post, 'authors', []))
                ->map(fn (array $author) => Author::make(name: $author['name'])->toArray())
                ->all();
            $date = Carbon::parse($post['published_at_iso'], timezone: Timezone::UTC->value);

            $items[] = new FeedItem(
                id: $url,
                url: $url,
                title: $title,
                summary: trim($post['excerpt'] ?? "[$category] $title"),
                image: $post['image_url'] ?? null,
                date_published: $date,
                authors: $authors,
                tags: [$category],
            );
        }

        return $items;
    }
}
