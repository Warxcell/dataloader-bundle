<?php

namespace Overblog\DataLoaderBundle\DependencyInjection;

use LogicException;
use Overblog\DataLoaderBundle\Scheduler\SyncScheduler;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

final class OverblogDataLoaderExtension extends Extension
{
    /**
     * @throws LogicException
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = $this->getConfiguration($configs, $container);
        if ($configuration === null) {
            throw new LogicException('No Configuration available');
        }
        $config = $this->processConfiguration($configuration, $configs);

        /** @phpstan-ignore-next-line */
        $container->setAlias('overblog_dataloader.promise_adapter', $config['promise_adapter']);

        $container->setDefinition(SyncScheduler::class, new Definition(SyncScheduler::class));
    }

    public function getAlias(): string
    {
        return 'overblog_dataloader';
    }
}
