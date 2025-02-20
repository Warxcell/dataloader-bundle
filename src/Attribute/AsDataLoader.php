<?php

declare(strict_types=1);

namespace Overblog\DataLoaderBundle\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final readonly class AsDataLoader
{
    public function __construct(
        public ?string $alias = null,
        public ?string $cacheKeyFn = null,
    ) {
    }
}
