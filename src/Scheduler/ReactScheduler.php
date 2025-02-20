<?php

declare(strict_types=1);

namespace Overblog\DataLoaderBundle\Scheduler;

use Closure;
use GraphQL\Executor\Promise\Adapter\SyncPromise;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;

final readonly class ReactScheduler implements Scheduler
{
    private LoopInterface $loop;

    public function __construct(
        ?LoopInterface $loop = null
    ) {
        $this->loop = $loop ?? Loop::get();
    }

    public function scheduleDispatch(Closure $callback): void
    {
        $this->loop->futureTick($callback);
    }
}
