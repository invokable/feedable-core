<?php

declare(strict_types=1);

namespace Revolution\Feedable\Core\Support;

use Illuminate\Support\Str;
use League\Uri\Uri;
use League\Uri\UriString;

class AbsoluteUri
{
    /**
     * Range of invalid characters in URI 3987 string.
     *
     * From League\Uri\UriString
     *
     * @var string
     */
    private const string REGEXP_INVALID_URI_RFC3987_CHARS = '/[\x00-\x1f\x7f\s]/';

    /**
     * Resolve a relative URI against a base URI.
     *
     * ```
     * use Revolution\Feedable\Core\Support\AbsoluteUri;
     *
     * $absoluteUri = AbsoluteUri::resolve('http://example.com/path/', '../other-path/resource');
     * // Result: 'http://example.com/other-path/resource'
     * ```
     */
    public static function resolve(string $base, ?string $relative): string
    {
        return Uri::new(self::normalize($base))
            ->resolve(self::normalize($relative))
            ->toString();
    }

    private static function normalize(?string $uri = null): string
    {
        $uri = Str::of($uri)
            ->stripTags()
            ->replaceMatches(self::REGEXP_INVALID_URI_RFC3987_CHARS, '')
            ->trim()
            ->toString();

        // 例外時は元のuriを返す。
        // UriString::normalizeは厳しいので消えるよりは元のuriがいいだろうという設計。
        // ただし返した先のUri::newでエラーの可能性は残る。
        // ほとんどはRFC3987のエラーなので事前に削除していれば頻度は低い。
        return rescue(fn () => UriString::normalize($uri), $uri, report: false);
    }
}
