<?php

declare(strict_types=1);

use Revolution\Feedable\Core\Elements\FeedItem;

test('feed item', function () {
    $feed = new FeedItem(
        id: 'id',
        url: 'http://example.com/sample-link',
        title: 'Sample Title',
        content_text: 'This is a sample description for the feed item.',
    );
    $feed->set('authors', ['Author'])
        ->set('nonexistent', 'Some Value');

    $feed->when(true, function (FeedItem $item) {
        $item->test = 'Extra Property';
    });

    expect($feed->toArray())
        ->toBeArray()
        ->toMatchArray([
            'id' => 'id',
            'title' => 'Sample Title',
            'url' => 'http://example.com/sample-link',
            'authors' => ['Author'],
            'content_text' => 'This is a sample description for the feed item.',
            'nonexistent' => 'Some Value',
            'test' => 'Extra Property',
        ])
        ->and($feed->get('test'))
        ->toBe('Extra Property')
        ->and($feed->test)
        ->toBe('Extra Property')
        ->and($feed->get('test2'))
        ->toBeNull();
});
