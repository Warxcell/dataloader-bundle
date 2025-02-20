<?php
declare(strict_types=1);

namespace Overblog\DataLoaderBundle\Scheduler;

use Closure;
use GraphQL\Executor\Promise\Adapter\SyncPromise;

final readonly class SyncScheduler implements Scheduler
{
    public function scheduleDispatch(Closure $callback): void
    {
        SyncPromise::getQueue()->enqueue($callback);
    }
}
