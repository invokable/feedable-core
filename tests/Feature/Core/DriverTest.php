<?php

declare(strict_types=1);

use Revolution\Feedable\Core\Driver;

test('Driver test', function () {
    Driver::about(
        id: 'test',
        name: 'test',
        description: 'test',
        example: '/test',
    );

    expect(Driver::get('test'))->toBeArray()
        ->and(Driver::collect()->all())->toBeArray()
        ->and(Driver::toPrettyJson())->toBeString();
});
