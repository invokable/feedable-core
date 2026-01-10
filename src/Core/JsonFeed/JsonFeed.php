<?php

declare(strict_types=1);

namespace Revolution\Feedable\Core\JsonFeed;

use Carbon\Carbon;
use DOMDocument;
use DOMElement;
use Exception;
use Illuminate\Support\Str;
use Revolution\Feedable\Core\Support\AbsoluteUri;

class JsonFeed
{
    /**
     * Original feed URL
     */
    protected ?string $feed_url = null;

    /**
     * Limit number of items
     */
    protected int $limit = 0;

    protected const string XML_CONTENT_NS = 'http://purl.org/rss/1.0/modules/content/';

    protected const string XML_DC_NS = 'http://purl.org/dc/elements/1.1/';

    protected const string XML_MEDIA_NS = 'http://search.yahoo.com/mrss/';

    /**
     * Convert RSS/Atom feed data to JSON Feed format.
     *
     * @throws Exception
     */
    public function convert(string $feed, ?string $feed_url = null, int $limit = 0): string
    {
        $this->feed_url = $feed_url;
        $this->limit = $limit;

        return match ($this->detect($feed)) {
            'rdf' => $this->rdf($feed),
            'rss2' => $this->rss2($feed),
            'atom' => $this->atom($feed),
            'json' => $feed,
            default => throw new Exception('Unsupported feed format.'),
        };
    }

    /**
     * Detect feed format.
     * RDF(RSS0.x), RSS2, Atom, JSON Feed.
     */
    protected function detect(string $body): string
    {
        if (str_contains($body, '<rdf:RDF')) {
            return 'rdf';
        }

        if (str_contains($body, '<rss')) {
            return 'rss2';
        }

        if (str_contains($body, '<feed')) {
            return 'atom';
        }

        if (Str::isJson($body) && str_contains(data_get(json_decode($body, true), 'version') ?? '', 'https://jsonfeed.org/version/1')) {
            return 'json';
        }

        return 'unknown';
    }

    protected function rdf(string $body): string
    {
        $body = $this->convertEncoding($body);

        $doc = new DOMDocument;
        $doc->loadXML($body);

        $channel = $doc->getElementsByTagName('channel')->item(0);

        $feed = [
            'version' => 'https://jsonfeed.org/version/1.1',
            'title' => $this->getNodeValue($channel, 'title'),
            'home_page_url' => $this->getNodeValue($channel, 'link'),
            'feed_url' => $this->feed_url,
            'description' => $this->getNodeValue($channel, 'description'),
            'items' => [],
        ];

        $items = $doc->getElementsByTagName('item');

        foreach ($items as $item) {
            /** @var DOMElement $item */
            $feedItem = [
                'id' => $item->getAttribute('rdf:about') ?: $this->getNodeValue($item, 'link'),
                'url' => $this->getNodeValue($item, 'link'),
                'title' => $this->getNodeValue($item, 'title'),
                'content_html' => $this->getNodeValueNS($item, 'encoded', self::XML_CONTENT_NS),
                'summary' => $this->getNodeValue($item, 'description'),
                'date_published' => $this->formatDate($this->getNodeValueNS($item, 'date', self::XML_DC_NS)),
            ];

            $author = $this->getNodeValueNS($item, 'creator', self::XML_DC_NS);
            if ($author) {
                $feedItem['authors'] = [['name' => $author]];
            }

            $subject = $this->getNodeValueNS($item, 'subject', self::XML_DC_NS);
            if ($subject) {
                $feedItem['tags'] = [$subject];
            }

            $feed['items'][] = array_filter($feedItem);

            if ($this->limit > 0 && count($feed['items']) >= $this->limit) {
                break;
            }
        }

        return json_encode($feed, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }

    protected function rss2(string $body): string
    {
        $body = $this->convertEncoding($body);

        $doc = new DOMDocument;
        $doc->loadXML($body);

        $channel = $doc->getElementsByTagName('channel')->item(0);

        $feed = [
            'version' => 'https://jsonfeed.org/version/1.1',
            'title' => $this->getNodeValue($channel, 'title'),
            'home_page_url' => $this->getNodeValue($channel, 'link'),
            'feed_url' => $this->feed_url,
            'description' => $this->getNodeValue($channel, 'description'),
            'items' => [],
        ];

        $items = $doc->getElementsByTagName('item');

        foreach ($items as $item) {
            $feedItem = [
                'id' => $this->getNodeValue($item, 'guid') ?: $this->getNodeValue($item, 'link'),
                'url' => $this->getNodeValue($item, 'link'),
                'title' => $this->getNodeValue($item, 'title'),
                'content_html' => $this->getNodeValueNS($item, 'encoded', self::XML_CONTENT_NS),
                'summary' => $this->getNodeValue($item, 'description'),
                'date_published' => $this->formatDate($this->getNodeValue($item, 'pubDate')),
                'image' => $this->getRss2Image($item),
            ];

            $author = $this->getNodeValue($item, 'author') ?: $this->getNodeValueNS($item, 'creator', self::XML_DC_NS);
            if ($author) {
                $feedItem['authors'] = [['name' => $author]];
            }

            $feed['items'][] = array_filter($feedItem);

            if ($this->limit > 0 && count($feed['items']) >= $this->limit) {
                break;
            }
        }

        return json_encode($feed, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }

    protected function atom(string $body): string
    {
        $doc = new DOMDocument;
        $doc->loadXML($body);

        $feedElement = $doc->getElementsByTagName('feed')->item(0);

        $home_page_url = $this->getAtomLink($feedElement, 'alternate');
        if (filled($home_page_url) && ! Str::isUrl($home_page_url)) {
            $home_page_url = AbsoluteUri::resolve($this->feed_url, $home_page_url);
        } else {
            $home_page_url = $this->feed_url;
        }

        $feed = [
            'version' => 'https://jsonfeed.org/version/1.1',
            'title' => $this->getNodeValue($feedElement, 'title'),
            'home_page_url' => $home_page_url,
            'feed_url' => $this->feed_url,
            'description' => $this->getNodeValue($feedElement, 'subtitle'),
            'items' => [],
        ];

        $entries = $doc->getElementsByTagName('entry');

        foreach ($entries as $entry) {
            $feedItem = [
                'id' => $this->getNodeValue($entry, 'id'),
                'url' => $this->getAtomEntryLink($entry, 'alternate'),
                'title' => $this->getNodeValue($entry, 'title'),
                ...$this->getAtomContent($entry),
                'summary' => $this->getNodeValue($entry, 'summary'),
                'date_published' => $this->formatDate($this->getNodeValue($entry, 'published')),
                'date_modified' => $this->formatDate($this->getNodeValue($entry, 'updated')),
                'image' => $this->getAtomImage($entry),
            ];

            $authorNode = $entry->getElementsByTagName('author')->item(0);
            if ($authorNode) {
                $feedItem['authors'] = [['name' => $this->getNodeValue($authorNode, 'name')]];
            }

            $feed['items'][] = array_filter($feedItem);

            if ($this->limit > 0 && count($feed['items']) >= $this->limit) {
                break;
            }
        }

        return json_encode($feed, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }

    protected function getNodeValue(DOMElement $parent, string $tagName): ?string
    {
        $nodes = $parent->getElementsByTagName($tagName);
        if ($nodes->length === 0) {
            return null;
        }

        return trim($nodes->item(0)->textContent) ?: null;
    }

    protected function getNodeValueNS(DOMElement $parent, string $tagName, ?string $namespace = null): ?string
    {
        $nodes = $parent->getElementsByTagNameNS($namespace, $tagName);
        if ($nodes->length === 0) {
            return null;
        }

        return trim($nodes->item(0)->textContent) ?: null;
    }

    protected function getAtomLink(DOMElement $parent, string $rel): ?string
    {
        $links = $parent->getElementsByTagName('link');
        foreach ($links as $link) {
            if (
                $link->getAttribute('rel') !== 'enclosure'
                && $link->getAttribute('rel') === $rel
                || ($rel === 'alternate' && ! $link->hasAttribute('rel'))
            ) {
                return $link->getAttribute('href') ?: null;
            }
        }

        return null;
    }

    protected function getAtomEntryLink(DOMElement $parent, string $rel): ?string
    {
        $link = $this->getAtomLink($parent, $rel);

        // contentタグにURLがある場合もある
        // <content src="http://" type="application/xhtml+xml"/>
        if (empty($link) && $rel === 'alternate') {
            $content = $parent->getElementsByTagName('content')->item(0);
            if ($content && $content->hasAttribute('src')) {
                return $content->getAttribute('src') ?: null;
            }
        }

        return null;
    }

    /**
     * Get image from RSS2 item.
     * <enclosure url="https://" length="0" type="image/jpeg"/>
     * <media:content url="https://" type="image/jpeg" medium="image">
     * <media:thumbnail>https://</media:thumbnail>
     */
    protected function getRss2Image(DOMElement $item): ?string
    {
        // enclosure
        $enclosure = $item->getElementsByTagName('enclosure')->item(0);
        if ($enclosure && str_starts_with($enclosure->getAttribute('type'), 'image/')) {
            return $enclosure->getAttribute('url') ?: null;
        }

        // media:content
        $mediaContent = $item->getElementsByTagNameNS(self::XML_MEDIA_NS, 'content')->item(0);
        if ($mediaContent && $mediaContent->getAttribute('medium') === 'image') {
            return $mediaContent->getAttribute('url') ?: null;
        }

        // media:thumbnail
        $mediaThumbnail = $item->getElementsByTagNameNS(self::XML_MEDIA_NS, 'thumbnail')->item(0);
        if ($mediaThumbnail) {
            return $mediaThumbnail->getAttribute('url') ?: trim($mediaThumbnail->textContent) ?: null;
        }

        // image_url
        $image_url = $item->getElementsByTagName('image_url')->item(0);
        if ($image_url) {
            return trim($image_url->textContent) ?: null;
        }

        return null;
    }

    /**
     * Get image from Atom entry.
     * <link rel="enclosure" href="https://" length="0" type="image/jpeg" />
     */
    protected function getAtomImage(DOMElement $entry): ?string
    {
        $links = $entry->getElementsByTagName('link');
        foreach ($links as $link) {
            if ($link->getAttribute('rel') === 'enclosure' && str_starts_with($link->getAttribute('type'), 'image/')) {
                return $link->getAttribute('href') ?: null;
            }
        }

        return null;
    }

    /**
     * Get content from Atom entry.
     * type="text" -> content_text, type="html" or type="xhtml" -> content_html
     *
     * @return array{content_text?: string, content_html?: string}
     */
    protected function getAtomContent(DOMElement $entry): array
    {
        $contentNode = $entry->getElementsByTagName('content')->item(0);
        if (! $contentNode) {
            return [];
        }

        $type = $contentNode->getAttribute('type') ?: 'text';

        if ($type === 'text') {
            $content = trim($contentNode->textContent) ?: null;

            return $content ? ['content_text' => $content] : [];
        }

        // html or xhtml
        $html = '';
        foreach ($contentNode->childNodes as $child) {
            $html .= $contentNode->ownerDocument->saveXML($child);
        }
        $html = Str::of($html)->trim()->chopStart('<![CDATA[')->chopEnd(']]>')->trim()->toString();

        return filled($html) ? ['content_html' => $html] : [];
    }

    protected function formatDate(?string $date = null): ?string
    {
        if (! $date) {
            return null;
        }

        return Carbon::parse($date)->toRfc3339String();
    }

    /**
     * Convert EUC-JP or Shift-JIS encoding to UTF-8.
     */
    protected function convertEncoding(string $body): string
    {
        // atom以降はUTF-8が前提なのでRSSのみに使用

        // PHP8.4以降ならxml内のencodingを見て自動的に変換されるけど
        // 8.3でlibxml2のiconvサポートが正しく設定されてない環境=vercel-phpなどではEUC-JPな場合にエラーが出て動かない。
        // 8.4でのDom\XMLDocument導入と同時に従来のDOMDocumentも改善が入っている。
        // どの場合でも確実な解決方法は事前に文字コードを変換。

        // encoding="EUC-JP"な場合
        // まだ使われているサイトがあるので対応
        if (Str::startsWith(trim($body), '<?xml version="1.0" encoding="EUC-JP"?>')) {
            $body = mb_convert_encoding($body, 'UTF-8', 'EUC-JP');
            $body = Str::replaceFirst('encoding="EUC-JP"?>', 'encoding="UTF-8"?>', $body);
        }

        // encoding="Shift-JIS"な場合
        if (Str::startsWith(trim($body), '<?xml version="1.0" encoding="Shift-JIS"?>')) {
            $body = mb_convert_encoding($body, 'UTF-8', 'Shift-JIS');
            $body = Str::replaceFirst('encoding="Shift-JIS"?>', 'encoding="UTF-8"?>', $body);
        }

        return $body;
    }
}
