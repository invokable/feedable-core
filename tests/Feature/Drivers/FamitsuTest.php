<?php

declare(strict_types=1);

it('returns json feed', function (): void {
    $response = $this->getJson('/famitsu/category/new-article.json');

    $response->assertOk()
        ->assertJsonStructure([
            'version',
            'title',
            'home_page_url',
            'feed_url',
            'description',
            'icon',
            'favicon',
            'items' => [
                [
                    'id',
                    'url',
                    'title',
                    'content_html',
                    'date_published',
                    'tags',
                ],
            ],
        ]);
});
