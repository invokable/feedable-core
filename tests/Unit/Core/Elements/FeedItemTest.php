<?php

declare(strict_types=1);

use Carbon\Carbon;
use Revolution\Feedable\Core\Elements\Author;
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

test('fromArray with minimal data', function () {
    $item = FeedItem::fromArray([
        'id' => '1',
        'title' => 'Test',
    ]);

    expect($item)
        ->id->toBe('1')
        ->title->toBe('Test')
        ->url->toBeNull()
        ->date_published->toBeNull();
});

test('fromArray with all known fields', function () {
    $data = [
        'id' => '42',
        'url' => 'https://example.com/post',
        'external_url' => 'https://external.com',
        'title' => 'Full Post',
        'content_html' => '<p>HTML</p>',
        'content_text' => 'Text',
        'summary' => 'Summary',
        'image' => 'https://example.com/img.jpg',
        'banner_image' => 'https://example.com/banner.jpg',
        'date_published' => '2026-03-21T00:00:00+09:00',
        'date_modified' => '2026-03-21T12:00:00+09:00',
        'authors' => [['name' => 'Author']],
        'tags' => ['tag1', 'tag2'],
        'language' => 'ja',
        'attachments' => [['url' => 'https://example.com/file.pdf']],
    ];

    $item = FeedItem::fromArray($data);

    expect($item)
        ->id->toBe('42')
        ->url->toBe('https://example.com/post')
        ->external_url->toBe('https://external.com')
        ->title->toBe('Full Post')
        ->content_html->toBe('<p>HTML</p>')
        ->content_text->toBe('Text')
        ->summary->toBe('Summary')
        ->image->toBe('https://example.com/img.jpg')
        ->banner_image->toBe('https://example.com/banner.jpg')
        ->date_published->toBeInstanceOf(Carbon::class)
        ->authors->toBe([['name' => 'Author']])
        ->tags->toBe(['tag1', 'tag2'])
        ->language->toBe('ja')
        ->attachments->toBe([['url' => 'https://example.com/file.pdf']]);
});

test('fromArray preserves extra fields', function () {
    $item = FeedItem::fromArray([
        'id' => '1',
        'custom_field' => 'custom_value',
        'categories' => ['News'],
    ]);

    expect($item->get('custom_field'))->toBe('custom_value')
        ->and($item->get('categories'))->toBe(['News']);
});

test('fromArray with empty id defaults to empty string', function () {
    $item = FeedItem::fromArray([]);

    expect($item->id)->toBe('');
});

test('toArray converts Carbon to ISO 8601 string', function () {
    $date = Carbon::parse('2026-03-21T09:00:00', 'Asia/Tokyo');

    $item = new FeedItem(
        id: '1',
        title: 'Test',
        date_published: $date,
    );

    $array = $item->toArray();

    expect($array['date_published'])
        ->toBeString()
        ->toContain('2026-03-21');
});

test('toArray with string dates passes through as-is', function () {
    $item = new FeedItem(
        id: '1',
        date_published: '2026-03-21',
    );

    expect($item->toArray()['date_published'])->toBe('2026-03-21');
});

test('toArray filters null values', function () {
    $item = new FeedItem(id: '1', title: 'Test');

    $array = $item->toArray();

    expect($array)
        ->toHaveKeys(['id', 'title'])
        ->not->toHaveKey('url')
        ->not->toHaveKey('summary');
});

test('round-trip toArray and fromArray with Carbon dates', function () {
    $original = new FeedItem(
        id: 'https://example.com/post/1',
        url: 'https://example.com/post/1',
        title: 'Round Trip',
        summary: 'Testing round-trip',
        image: 'https://example.com/img.jpg',
        date_published: Carbon::parse('2026-03-21T09:00:00+09:00'),
        authors: [Author::make(name: 'Test Author')->toArray()],
        tags: ['Laravel', 'PHP'],
    );

    $array = $original->toArray();
    $restored = FeedItem::fromArray($array);

    expect($restored)
        ->id->toBe($original->id)
        ->url->toBe($original->url)
        ->title->toBe($original->title)
        ->summary->toBe($original->summary)
        ->image->toBe($original->image)
        ->date_published->toBeInstanceOf(Carbon::class)
        ->tags->toBe($original->tags)
        ->authors->toBe($original->authors);

    // 復元後もtoArrayの結果が一致
    expect($restored->toArray())->toBe($array);
});

test('round-trip preserves extra fields', function () {
    $original = new FeedItem(id: '1', title: 'Test');
    $original->set('custom', 'value');
    $original->categories = ['News', 'Updates'];

    $restored = FeedItem::fromArray($original->toArray());

    expect($restored->get('custom'))->toBe('value')
        ->and($restored->get('categories'))->toBe(['News', 'Updates'])
        ->and($restored->toArray())->toBe($original->toArray());
});

test('fromArray parses various date formats', function (string $input) {
    $item = FeedItem::fromArray([
        'id' => '1',
        'date_published' => $input,
    ]);

    expect($item->date_published)
        ->toBeInstanceOf(Carbon::class);
})->with([
    '2026-03-21T09:00:00+09:00',
    '2026-03-21T00:00:00.000000Z',
    '2026-03-21',
    'Sat, 21 Mar 2026 00:00:00 GMT',
]);
