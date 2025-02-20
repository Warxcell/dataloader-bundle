<?php

declare(strict_types=1);

namespace Overblog\DataLoaderBundle\DependencyInjection;

use GraphQL\Executor\Promise\PromiseAdapter;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('overblog_dataloader');
        /** @phpstan-ignore-next-line */
        $treeBuilder
            ->getRootNode()
            ->children()
            ->scalarNode('promise_adapter')->defaultValue(PromiseAdapter::class)->end()
            ->end();

        return $treeBuilder;
    }
}
