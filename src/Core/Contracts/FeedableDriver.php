<?php

declare(strict_types=1);

namespace Revolution\Feedable\Core\Contracts;

interface FeedableDriver
{
    public function handle(): mixed;
}
