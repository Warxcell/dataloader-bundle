<?php

declare(strict_types=1);

namespace Overblog\DataLoaderBundle\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class AsDataLoader
{
    public function __construct(
        public readonly ?string $alias = null,
        public readonly ?string $cacheKeyFn = null,
    )
    {
    }
}
