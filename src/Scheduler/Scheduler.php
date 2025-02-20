<?php

namespace Overblog\DataLoaderBundle\Scheduler;

use Closure;

interface Scheduler
{
    public function scheduleDispatch(Closure $callback): void;
}
