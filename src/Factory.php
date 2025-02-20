<?php

declare(strict_types=1);

namespace Overblog\DataLoaderBundle;

use Closure;
use GraphQL\Executor\Promise\Adapter\SyncPromiseAdapter;
use GraphQL\Executor\Promise\PromiseAdapter;
use Overblog\DataLoaderBundle\Scheduler\Scheduler;
use Overblog\DataLoaderBundle\Scheduler\SyncScheduler;

final readonly class Factory
{
    private Scheduler $scheduler;

    public function __construct(
        private Closure        $batchLoadFn,
        private PromiseAdapter $promiseAdapter,
        private ?Closure       $cacheKeyFn = null,
    )
    {
        if ($this->promiseAdapter instanceof SyncPromiseAdapter) {
            $this->scheduler = new SyncScheduler();
        }
    }

    public function create(): DataLoaderInterface
    {
        return new DataLoader($this->batchLoadFn, $this->promiseAdapter, $this->scheduler, $this->cacheKeyFn,);
    }
}
