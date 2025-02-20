<?php

namespace Overblog\DataLoaderBundle;

use Closure;
use Exception;
use GraphQL\Executor\Promise\Promise;
use GraphQL\Executor\Promise\PromiseAdapter;

use Overblog\DataLoaderBundle\Scheduler\Scheduler;
use function array_map;

class DataLoader implements DataLoaderInterface
{
    /**
     * @var array<array-key>
     */
    private array $keys = [];
    private array $queue = [];

    private array $cache = [];

    private readonly Closure $cacheKeyFn;

    public function __construct(
        private readonly Closure        $batchLoadFn,
        private readonly PromiseAdapter $promiseAdapter,
        private readonly Scheduler      $scheduler,
        ?Closure                        $cacheKeyFn = null
    )
    {
        $this->cacheKeyFn = $cacheKeyFn ?? fn(string|int $key): string|int => $key;
    }

    public function load(mixed $key): Promise
    {
        $cacheKey = ($this->cacheKeyFn)($key);

        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        return $this->promiseAdapter->create(function (callable $resolve, callable $reject) use ($key) {
            $this->keys[] = $key;
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
                function ($key) {
                    return $this->load($key);
                },
                $keys
            )
        );
    }

    /**
     * {@inheritdoc}
     */
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
        $promise = $value instanceof Exception ? $this->promiseAdapter->createRejected($value) : $this->promiseAdapter->createFulfilled($value);

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
                fn($values) => $this->handleSuccessfulDispatch($queue, $values),
                fn($error) => $this->handleFailedDispatch($keys, $queue, $error)
            );
    }

    private function handleSuccessfulDispatch(array $batch, array $values): void
    {
        foreach ($batch as $index => $queueItem) {
            $value = $values[$index];
            $value instanceof Exception
                ? $queueItem['reject']($value)
                : $queueItem['resolve']($value);
        }
    }

    private function handleFailedDispatch(array $keys, array $queue, Exception $error): void
    {
        foreach ($keys as $index => $key) {
            // We don't want to cache individual loads if the entire batch dispatch fails.
            $this->clear($key);
            $queue[$index]['reject']($error);
        }
    }
}
