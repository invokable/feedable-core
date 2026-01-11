<?php

declare(strict_types=1);

it('can convert from atom to json feed', function (): void {
    $response = $this->getJson('/jsonfeed?url=https://github.com/invokable/feedable-core/commits/main.atom');

    $response->assertOk()
        ->assertJsonStructure([
            'version',
            'title',
            'home_page_url',
            'feed_url',
            'description',
            'items' => [
                [
                    'id',
                    'url',
                    'title',
                    'content_html',
                    'date_modified',
                    'authors',
                ],
            ],
        ]);
});
