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

namespace Overblog\DataLoaderBundle;

use LogicException;
use Overblog\DataLoaderBundle\Attribute\AsDataLoader;
use Overblog\DataLoaderBundle\DependencyInjection\OverblogDataLoaderExtension;
use ReflectionClass;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use function lcfirst;
use function sprintf;

final class OverblogDataLoaderBundle extends Bundle
{
    public function getContainerExtension(): ?ExtensionInterface
    {
        return new OverblogDataLoaderExtension();
    }

    public function build(ContainerBuilder $container)
    {
        $container->registerAttributeForAutoconfiguration(
            AsDataLoader::class,
            static function (ChildDefinition $definition, AsDataLoader $attribute, ReflectionClass $reflector): void {
                if (!$reflector->implementsInterface(DataLoaderFnInterface::class)) {
                    throw new LogicException(sprintf('Please implement %s', DataLoaderFnInterface::class));
                }

                $definition->addTag('overblog.dataloader', [
                        'alias' => $attribute->alias ?? lcfirst($reflector->getShortName()),
                        'maxBatchSize' => $attribute->maxBatchSize,
                        'batch' => $attribute->batch,
                        'cache' => $attribute->cache,
                        'cacheKeyFn' => $attribute->cacheKeyFn,
                    ]
                );
            }
        );

        $container->addCompilerPass(
            new class implements CompilerPassInterface {
                private function registerDataLoader(
                    ContainerBuilder $container,
                    array $rawConfig,
                    string $batchLoadFn
                ): void {
                    $name = $rawConfig['alias'];
                    $dataLoaderRef = new Reference($batchLoadFn);
                    $config = [];

                    foreach (['batch', 'maxBatchSize', 'cache'] as $key) {
                        if (isset($rawConfig[$key])) {
                            $config[$key] = $rawConfig[$key];
                        }
                    }

                    if (isset($rawConfig['cacheKeyFn'])) {
                        $config['cacheKeyFn'] = [$dataLoaderRef, $rawConfig['cacheKeyFn']];
                    }

                    $id = $this->generateDataLoaderServiceIDFromName($name, $container);

                    $container->register($id, Factory::class)
                        ->setArguments([
                            $dataLoaderRef,
                            new Reference('overblog_dataloader.webonyx_graphql_sync_promise_adapter'),
                            $config,
                        ]);
                    $container->registerAliasForArgument($id, Factory::class, $name);
                }

                private function generateDataLoaderServiceIDFromName($name, ContainerBuilder $container): string
                {
                    return sprintf('overblog_dataloader.%s_loader.factory', $container::underscore($name));
                }

                public function process(ContainerBuilder $container)
                {
                    foreach ($container->findTaggedServiceIds('overblog.dataloader') as $id => $tags) {
                        foreach ($tags as $attrs) {
                            $this->registerDataLoader(
                                $container,
                                $attrs,
                                $id
                            );
                        }
                    }
                }
            }
        );
    }
}
