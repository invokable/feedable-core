<?php

declare(strict_types=1);

namespace Revolution\Feedable\Core\Support;

use Dom\Element;
use Dom\XMLDocument;
use Dom\XPath;

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
     * @param  callable(Element $item): void  $callback  A callback function that takes a Element representing an <item>.
     * @return string The modified RSS feed XML as a string.
     */
    public static function each(string $xml, callable $callback): string
    {
        $dom = XMLDocument::createFromString($xml);
        $xpath = new XPath($dom);
        $items = $xpath->query(self::ITEM_XPATH);

        foreach ($items as $item) {
            $callback($item);
        }

        return $dom->saveXml();
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
        return static::each($xml, function (Element $item) use ($links) {
            $linkNode = $item->getElementsByTagName('link')->item(0);
            if ($linkNode && ! in_array($linkNode->textContent, $links, true)) {
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
        return static::each($xml, function (Element $item) use ($links) {
            $linkNode = $item->getElementsByTagName('link')->item(0);
            if ($linkNode && in_array($linkNode->textContent, $links, true)) {
                $item->parentNode->removeChild($item);
            }
        });
    }
}
