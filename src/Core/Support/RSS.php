<?php

declare(strict_types=1);

namespace Revolution\Feedable\Core\Support;

use DOMDocument;
use DOMElement;
use DOMXPath;

/**
 * RSS manipulation helpers.
 */
class RSS
{
    protected const string ITEM_XPATH = '//item';

    /**
     * Iterate over each <item> in the RSS feed XML and apply a callback function.
     *
     * @param  string  $xml  The RSS feed XML as a string.
     * @param  callable(DOMElement $item): void  $callback  A callback function that takes a DOMElement representing an <item>.
     * @return string The modified RSS feed XML as a string.
     */
    public static function each(string $xml, callable $callback): string
    {
        // TODO: VercelがPHP8.4対応したらDom\XMLDocumentに変更

        $dom = new DOMDocument;
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;

        $dom->loadXML($xml);
        $xpath = new DOMXPath($dom);
        $items = $xpath->query(self::ITEM_XPATH);

        foreach ($items as $item) {
            $callback($item);
        }

        return $dom->saveXML();
    }

    /**
     * Filter RSS feed items to include only those with links in the specified array.
     *
     * @param  string  $xml  The RSS feed XML as a string.
     * @param  array  $links  An array of links to filter by.
     * @return string The filtered RSS feed XML as a string.
     */
    public static function filterLinks(string $xml, array $links): string
    {
        return static::each($xml, function (DOMElement $item) use ($links) {
            $linkNode = $item->getElementsByTagName('link')->item(0);
            if ($linkNode && ! in_array($linkNode->nodeValue, $links, true)) {
                $item->parentNode->removeChild($item);
            }
        });
    }

    /**
     * Reject RSS feed items with links in the specified array.
     *
     * @param  string  $xml  The RSS feed XML as a string.
     * @param  array  $links  An array of links to reject.
     * @return string The modified RSS feed XML as a string.
     */
    public static function rejectLinks(string $xml, array $links): string
    {
        return static::each($xml, function (DOMElement $item) use ($links) {
            $linkNode = $item->getElementsByTagName('link')->item(0);
            if ($linkNode && in_array($linkNode->nodeValue, $links, true)) {
                $item->parentNode->removeChild($item);
            }
        });
    }
}
