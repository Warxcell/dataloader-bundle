<?php
declare(strict_types=1);

namespace Overblog\DataLoaderBundle;

use Closure;
use Exception;
use GraphQL\Executor\Promise\Promise;
use GraphQL\Executor\Promise\PromiseAdapter;

use Overblog\DataLoaderBundle\Scheduler\Scheduler;
use function array_map;
use function count;

/**
 * @template K
 * @template V
 * @implements DataLoaderInterface<K, V>
 * @phpstan-type Queue array{resolve: Closure(V): void, reject: Closure(mixed): void}
 */
final class DataLoader implements DataLoaderInterface
{
    /**
     * @var K[]
     */
    private array $keys = [];

    /**
     * @var Queue[]
     */
    private array $queue = [];

    /**
     * @var array<array-key, Promise>
     */
    private array $cache = [];

    /**
     * @var Closure(K): array-key
     */
    private readonly Closure $cacheKeyFn;

    /**
     * @param Closure(K[]): Promise $batchLoadFn
     * @param Closure(K): array-key|null $cacheKeyFn
     */
    public function __construct(
        private readonly Closure $batchLoadFn,
        private readonly PromiseAdapter $promiseAdapter,
        private readonly Scheduler $scheduler,
        ?Closure $cacheKeyFn = null
    ) {
        /** @phpstan-ignore-next-line */
        $this->cacheKeyFn = $cacheKeyFn ?? fn(mixed $key): string|int => $key;
    }

    public function load(mixed $key): Promise
    {
        $cacheKey = ($this->cacheKeyFn)($key);

        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        return $this->promiseAdapter->create(function (callable $resolve, callable $reject) use ($key) {
            $this->keys[] = $key;
            /** @phpstan-ignore-next-line */
            $this->queue[] = [
                'resolve' => $resolve,
                'reject' => $reject,
            ];

            if (count($this->queue) === 1) {
                $this->scheduler->scheduleDispatch(fn() => $this->dispatchQueue());
            }
        });
    }

    public function loadMany(array $keys): Promise
    {
        return $this->promiseAdapter->all(
            array_map(
                fn($key) => $this->load($key),
                $keys
            )
        );
    }

    public function clear(mixed $key): self
    {
        $cacheKey = ($this->cacheKeyFn)($key);
        unset($this->cache[$cacheKey]);

        return $this;
    }

    public function clearAll(): self
    {
        $this->cache = [];

        return $this;
    }

    public function prime(mixed $key, mixed $value): self
    {
        $promise = $value instanceof Exception ? $this->promiseAdapter->createRejected(
            $value
        ) : $this->promiseAdapter->createFulfilled($value);

        $cacheKey = ($this->cacheKeyFn)($key);
        $this->cache[$cacheKey] = $promise;

        return $this;
    }

    private function dispatchQueue(): void
    {
        $queue = $this->queue;
        $this->queue = [];

        $keys = $this->keys;
        $this->keys = [];

        $batchPromise = ($this->batchLoadFn)($keys);

        $batchPromise
            ->then(
            /** @phpstan-ignore-next-line */
                fn($values) => $this->handleSuccessfulDispatch($queue, $values),
                fn($error) => $this->handleFailedDispatch($keys, $queue, $error)
            );
    }

    /**
     * @param Queue[] $batch
     * @param V[] $values
     */
    private function handleSuccessfulDispatch(array $batch, array $values): void
    {
        foreach ($batch as $index => $queueItem) {
            $value = $values[$index];
            $value instanceof Exception
                ? $queueItem['reject']($value)
                : $queueItem['resolve']($value);
        }
    }

    /**
     * @param K[] $keys
     * @param Queue[] $queue
     * @param mixed $error
     */
    private function handleFailedDispatch(array $keys, array $queue, mixed $error): void
    {
        foreach ($keys as $index => $key) {
            // We don't want to cache individual loads if the entire batch dispatch fails.
            $this->clear($key);
            $queue[$index]['reject']($error);
        }
    }
}
