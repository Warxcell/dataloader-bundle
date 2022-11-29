<?php

declare(strict_types=1);

namespace Overblog\DataLoaderBundle;

interface DataLoaderFnInterface
{
    public function __invoke(array $keys): mixed;
}
