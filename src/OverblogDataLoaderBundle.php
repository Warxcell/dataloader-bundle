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
                if (!$reflector->hasMethod('__invoke')) {
                    throw new LogicException('Please implement "__invoke" method',);
                }

                $definition->addTag('overblog.dataloader', [
                        'alias' => $attribute->alias ?? lcfirst($reflector->getShortName()),
                        'cacheKeyFn' => $attribute->cacheKeyFn,
                    ]
                );
            }
        );

        $container->addCompilerPass(
            new class implements CompilerPassInterface {
                private function registerDataLoader(
                    ContainerBuilder $container,
                    array            $rawConfig,
                    string           $batchLoadFn
                ): void
                {
                    $name = $rawConfig['alias'];
                    $dataLoaderRef = new Reference($batchLoadFn);

                    $id = $this->generateDataLoaderServiceIDFromName($name, $container);

                    $container->register($id, Factory::class)
                        ->setArguments([
                            '$batchLoadFn' => [$dataLoaderRef, '__invoke'],
                            '$promiseAdapter' => new Reference('overblog_dataloader.promise_adapter'),
                            '$cacheKeyFn' => [$dataLoaderRef, $rawConfig['cacheKeyFn']],
                        ]);
                    $container->registerAliasForArgument($id, Factory::class, $name);
                }

                private function generateDataLoaderServiceIDFromName($name, ContainerBuilder $container): string
                {
                    return sprintf('overblog_dataloader.%s_loader.factory', $container::underscore($name));
                }

                public function process(ContainerBuilder $container): void
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
