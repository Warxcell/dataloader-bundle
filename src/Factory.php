<?php

declare(strict_types=1);

namespace Overblog\DataLoaderBundle;

use Overblog\DataLoader\DataLoader;
use Overblog\DataLoader\DataLoaderInterface;
use Overblog\DataLoader\Option;
use Overblog\PromiseAdapter\PromiseAdapterInterface;

final class Factory
{
    public function __construct(
        private readonly DataLoaderFnInterface $batchLoadFn,
        private readonly PromiseAdapterInterface $promiseAdapter,
        private readonly Option $option
    ) {
    }

    public function create(): DataLoaderInterface
    {
        return new DataLoader($this->batchLoadFn, $this->promiseAdapter, $this->option);
    }
}

