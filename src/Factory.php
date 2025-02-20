<?php

declare(strict_types=1);

namespace Overblog\DataLoaderBundle;

use Closure;
use GraphQL\Executor\Promise\PromiseAdapter;

final class Factory
{
    public function __construct(
        private readonly Closure       $batchLoadFn,
        private readonly PromiseAdapter $promiseAdapter,
    )
    {
    }

    public function create(): DataLoaderInterface
    {
        return new DataLoader($this->batchLoadFn, $this->promiseAdapter);
    }
}
