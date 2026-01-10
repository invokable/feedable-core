<?php

declare(strict_types=1);

test('FeedableServiceProvider registers build-in drivers', function (): void {
    $environments = Boost::getCodeEnvironments();

    expect($environments)->toHaveKey('copilot-cli')
        ->and($environments['copilot-cli'])->toBe(CopilotCli::class);
});
