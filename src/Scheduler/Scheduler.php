<?php

declare(strict_types=1);

namespace Overblog\DataLoaderBundle\Scheduler;

use Closure;

interface Scheduler
{
    /**
     * @param Closure(): void $callback
     */
    public function scheduleDispatch(Closure $callback): void;
}
