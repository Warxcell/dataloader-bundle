<?php

declare(strict_types=1);

namespace Overblog\DataLoaderBundle;

use GraphQL\Executor\Promise\Promise;

interface DataLoaderFnInterface
{
    public function __invoke(array $keys): Promise;
}
