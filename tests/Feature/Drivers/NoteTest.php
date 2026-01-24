<?php

declare(strict_types=1);

use Revolution\Salvager\Facades\Salvager;

it('returns json feed', function (): void {
    Salvager::expects('agent')->once();

    $response = $this->getJson('/note/index.json');

    $response->assertOk();
    //        ->assertJsonStructure([
    //            'version',
    //            'title',
    //            'home_page_url',
    //            'feed_url',
    //            'description',
    //            'items' => [
    //                [
    //                    'id',
    //                    'url',
    //                    'title',
    //                    'date_published',
    //                ],
    //            ],
    //        ])
});
