<?php

declare(strict_types=1);

namespace Revolution\Feedable\Drivers\Famitsu;

use const Dom\HTML_NO_DEFAULT_NS;

use Dom\HTMLDocument;
use Exception;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Uri;
use Revolution\Feedable\Core\Contracts\FeedableDriver;
use Revolution\Feedable\Core\Elements\Author;
use Revolution\Feedable\Core\Elements\FeedItem;
use Revolution\Feedable\Core\Enums\Format;
use Revolution\Feedable\Core\Enums\Timezone;
use Revolution\Feedable\Core\Response\ErrorResponse;
use Revolution\Feedable\Core\Response\ResponseFactory;
use Revolution\Feedable\Core\Support\AbsoluteUri;
use Revolution\Feedable\Drivers\Famitsu\Enums\Category;

class FamitsuCategoryDriver implements FeedableDriver
{
    protected string $baseUrl = 'https://www.famitsu.com';

    protected string $category = '';

    protected string $title = '';

    protected ?string $buildId = null;

    public function __invoke(Category $category, Format $format = Format::RSS): Responsable
    {
        $this->category = $category->value;

        try {
            $items = $this->handle();
        } catch (Exception $e) {
            return new ErrorResponse(
                error: 'Whoops! Something went wrong.',
                message: $e->getMessage(),
            );
        }

        return ResponseFactory::format($format)->make(
            title: $this->title,
            home_page_url: Uri::of($this->baseUrl)->withPath('/category/'.$this->category.'/page/1')->value(),
            feed_url: url()->current(),
            description: $this->title,
            icon: 'https://www.famitsu.com/res/images/headIcons/apple-touch-icon.png',
            favicon: 'https://www.famitsu.com/res/images/headIcons/apple-touch-icon.png',
            items: $items,
        );
    }

    /**
     * @throws Exception
     */
    public function handle(): array
    {
        $this->getBuildId();

        if (empty($this->buildId)) {
            throw new Exception;
        }

        $response = Http::baseUrl($this->baseUrl)
            ->get("/_next/data/$this->buildId/category/$this->category/page/1.json");

        if ($response->failed()) {
            // タイミングによってはここでjsonが取得できないことがあるので後で詳細なエラーメッセージに更新。
            throw new Exception;
        }

        if (app()->isLocal()) {
            Storage::put('famitsu/'.$this->category.'.json', $response->body());
        }

        $this->title = $response->json('pageProps.targetCategory.nameJa', '').'の最新記事 | ゲーム・エンタメ最新情報のファミ通.com';

        return $response->collect('pageProps.categoryArticleDataForPc')
            ->reject(fn ($item) => Arr::has($item, 'advertiserName'))
            ->map($this->articleList(...))
            ->map($this->getArticle(...))
            ->values()
            ->toArray();
    }

    /**
     * カテゴリーのjsonから記事リストに変換
     */
    protected function articleList(array $item): array
    {
        $publicationDate = Str::of(data_get($item, 'publishedAt'))->take(7)->remove('-')->toString();

        $categories = collect(data_get($item, 'subCategories', []))
            ->map(fn ($sub) => data_get($sub, 'nameJa'))
            ->prepend(data_get($item, 'mainCategory.nameJa'))
            ->toArray();

        return [
            'title' => data_get($item, 'title'),
            'link' => Uri::of($this->baseUrl)->withPath('/article/'.$publicationDate.'/'.data_get($item, 'id'))->value(),
            'pubDate' => Carbon::parse(data_get($item, 'publishedAt'), timezone: Timezone::AsiaTokyo->value),
            'publicationDate' => $publicationDate,
            'categories' => $categories,
            'articleId' => data_get($item, 'id'),
        ];
    }

    /**
     * 記事詳細のjsonを取得。一度取得すればいいので長くキャッシュ
     */
    protected function getArticle(array $item): FeedItem|array|null
    {
        return Cache::remember('famitsu_article_'.$this->buildId.'_'.data_get($item, 'articleId'),
            now()->addDays(7),
            function () use ($item) {
                $response = Http::baseUrl($this->baseUrl)
                    ->get("/_next/data/$this->buildId/article/".data_get($item, 'publicationDate').'/'.data_get($item, 'articleId').'.json');

                if ($response->failed()) {
                    return null;
                }

                if (app()->isLocal()) {
                    Storage::put('famitsu/'.data_get($item, 'articleId').'.json', $response->body());
                }

                $article = $response->collect('pageProps.articleDetailData');

                $authors = collect($article->get('authors'))->map(fn ($author) => Author::make(name: data_get($author, 'name_ja')));

                $thumbnail = data_get($article, 'ogpImageUrl', data_get($article, 'thumbnailUrl'));

                $description = $this->renderJson(data_get($article, 'content', []));

                return (new FeedItem(
                    id: data_get($item, 'link'),
                    url: data_get($item, 'link'),
                    title: data_get($item, 'title'),
                    content_html: $description,
                    date_published: data_get($item, 'pubDate'),
                    tags: data_get($item, 'categories'),
                ))->when($authors->isNotEmpty(), fn (FeedItem $feedItem) => $feedItem->set('authors', $authors->toArray()))
                    ->when(filled($thumbnail), fn (FeedItem $feedItem) => $feedItem->tap(fn (FeedItem $item) => $item->image = $thumbnail));
            });
    }

    protected function getBuildId(): void
    {
        $response = Http::get($this->baseUrl);

        // TODO: VercelがPHP8.4対応したらDom\HTMLDocumentのみに変更
        if (PHP_VERSION_ID >= 80400) {
            $html = HTMLDocument::createFromString(
                source: $response->body(),
                options: LIBXML_HTML_NOIMPLIED | LIBXML_NOERROR | HTML_NO_DEFAULT_NS,
            );
            $json = $html->querySelector('#__NEXT_DATA__')->innerHTML;
        } else {
            // PHP8.3以下の場合は旧DOMDocumentを使用
            $dom = new \DOMDocument;
            @$dom->loadHTML($response->body());
            $json = $dom->getElementById('__NEXT_DATA__')->nodeValue;
        }

        $this->buildId = data_get(json_decode($json, true), 'buildId');
    }

    /**
     * contentはjson形式で格納されているのでHTMLに変換する
     */
    protected function renderJson(array $content): string
    {
        return collect($content)
            ->map(fn ($c) => collect(data_get($c, 'contents', []))
                ->map(fn ($con) => $this->renderContent($con))
                ->join(''),
            )
            ->join('');
    }

    protected function renderContent(array $c): string
    {
        $content = data_get($c, 'content');

        if (is_array($content)) {
            $content = collect($content)
                ->map(fn ($con) => is_array($con) && isset($con['type']) ? $this->renderContent($con) : '')
                ->join('');
        }

        return match (data_get($c, 'type')) {
            'B', 'INTERVIEWEE', 'STRONG' => "<b>{$content}</b>",
            'HEAD' => "<h2>{$content}</h2>",
            'SHEAD' => "<h3>{$content}</h3>",
            'LINK_B', 'LINK_B_TAB' => '<a href="'.AbsoluteUri::resolve($this->baseUrl, data_get($c, 'url')).'"><b>'.$content.'</b></a>',
            'IMAGE' => '<img src="'.data_get($c, 'path').'">',
            'NEWS' => '<a href="'.AbsoluteUri::resolve($this->baseUrl, data_get($c, 'url')).'">'.$content.'<br>'.data_get($c, 'description').'</a><br>',
            'HTML' => $content,
            'ANNOTATION', 'CAPTION', 'ITEMIZATION', 'ITEMIZATION_NUM', 'NOLINK', 'STRING', 'TWITTER', 'YOUTUBE' => "<span>{$content}</span>",
            'BUTTON', 'BUTTON_ANDROID', 'BUTTON_EC', 'BUTTON_IOS', 'BUTTON_TAB', 'LINK', 'LINK_TAB' => '<a href="'.AbsoluteUri::resolve($this->baseUrl, data_get($c, 'url')).'">'.$content.'</a><br>',
            default => '',
        };
    }
}
