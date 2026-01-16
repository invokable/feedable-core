<?php

declare(strict_types=1);

use Revolution\Feedable\Core\Support\RSS;

$xml = <<<'RSS'
<?xml version="1.0"?>
<rss version="2.0">
    <channel>
        <item>
            <link>https://example.com/1</link>
        </item>
        <item>
            <link>https://example.com/2</link>
        </item>
    </channel>
</rss>
RSS;

test('RSS filter links', function () use ($xml) {
    $xml = RSS::filterLinks($xml, ['https://example.com/1']);

    expect($xml)->toContain('https://example.com/1')
        ->and($xml)->not->toContain('https://example.com/2');
});

test('RSS reject links', function () use ($xml) {
    $xml = RSS::rejectLinks($xml, ['https://example.com/1']);

    expect($xml)->toContain('https://example.com/2')
        ->and($xml)->not->toContain('https://example.com/1');
});
