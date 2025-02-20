<?php

/*
 * This file is part of the OverblogDataLoaderBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
        $treeBuilder
            ->getRootNode()
            ->children()
            ->scalarNode('promise_adapter')->defaultValue(PromiseAdapter::class)->end()
            ->end();

        return $treeBuilder;
    }
}
