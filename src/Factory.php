<?php

declare(strict_types=1);

namespace Overblog\DataLoaderBundle;

use Closure;
use GraphQL\Executor\Promise\Promise;
use GraphQL\Executor\Promise\PromiseAdapter;
use Overblog\DataLoaderBundle\Scheduler\Scheduler;

/**
 * @template K
 * @template V
 */
final readonly class Factory
{
    /**
     * @param Closure(K[]): Promise $batchLoadFn
     * @param PromiseAdapter $promiseAdapter
     * @param Scheduler $scheduler
     * @param Closure(K): array-key|null $cacheKeyFn
     */
    public function __construct(
        private Closure $batchLoadFn,
        private PromiseAdapter $promiseAdapter,
        private Scheduler $scheduler,
        private ?Closure $cacheKeyFn = null,
    ) {
    }

    /**
     * @return DataLoaderInterface<K, V>
     */
    public function create(): DataLoaderInterface
    {
        /** @phpstan-ignore-next-line */
        return new DataLoader($this->batchLoadFn, $this->promiseAdapter, $this->scheduler, $this->cacheKeyFn);
    }
}
