<?php

/*
 * This file is part of the DataLoaderPhp package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\DataLoaderBundle;

use GraphQL\Executor\Promise\Promise;

/**
 * @template V
 * @template K
 */
interface DataLoaderInterface
{
    /**
     * @param K $key
     * @return Promise
     */
    public function load(mixed $key): Promise;

    /**
     * Loads multiple keys, promising an array of values:
     *
     *     list($a, $b) = $myLoader->loadMany(['a', 'b']);
     *
     * This is equivalent to the more verbose:
     *
     *     [$a, $b] = $promiseAdapter->all([
     *       $myLoader->load('a'),
     *       $myLoader->load('b')
     *     ]);
     *
     * @param K[] $keys
     * @return Promise
     */
    public function loadMany(array $keys): Promise;

    /**
     * Clears the value at `key` from the cache, if it exists.
     *
     * @param K $key
     */
    public function clear(mixed $key): self;

    /**
     * Clears the entire cache. To be used when some event results in unknown
     * invalidations across this particular `DataLoader`.
     */
    public function clearAll(): self;

    /**
     * Adds the provided key and value to the cache. If the key already exists, no
     * change is made. Returns itself for method chaining.
     * @param K $key
     * @param V $value
     */
    public function prime(mixed $key, mixed $value): self;
}
