<?php

declare(strict_types=1);

namespace Overblog\DataLoaderBundle;

use Closure;
use LogicException;
use Overblog\DataLoaderBundle\Attribute\AsDataLoader;
use Overblog\DataLoaderBundle\DependencyInjection\OverblogDataLoaderExtension;
use Overblog\DataLoaderBundle\Scheduler\SyncScheduler;
use ReflectionClass;
use Reflector;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use function lcfirst;
use function sprintf;

final class OverblogDataLoaderBundle extends Bundle
{
    public function getContainerExtension(): ExtensionInterface
    {
        return new OverblogDataLoaderExtension();
    }

    public function build(ContainerBuilder $container)
    {
        $container->registerAttributeForAutoconfiguration(
            AsDataLoader::class,
            static function (ChildDefinition $definition, AsDataLoader $attribute, Reflector $reflector): void {
                assert($reflector instanceof ReflectionClass);
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
                /**
                 * @param ContainerBuilder $container
                 * @param array{alias: string, cacheKeyFn?: string} $config
                 * @param string $batchLoadFn
                 * @return void
                 */
                private function registerDataLoader(
                    ContainerBuilder $container,
                    array $config,
                    string $batchLoadFn
                ): void {
                    $name = $config['alias'];

                    $id = sprintf('overblog_dataloader.%s_loader.factory', $container::underscore($name));

                    $batchLoadFnDef = new Definition(Closure::class);
                    $batchLoadFnDef->setFactory([Closure::class, 'fromCallable']);
                    $batchLoadFnDef->addArgument(new Reference($batchLoadFn));

                    $cacheKeyFnDef = null;
                    if (isset($config['cacheKeyFn'])) {
                        $cacheKeyFnDef = new Definition(Closure::class);
                        $cacheKeyFnDef->setFactory([Closure::class, 'fromCallable']);
                        $cacheKeyFnDef->addArgument([new Reference($batchLoadFn), $config['cacheKeyFn']]);
                    }


                    $container->register($id, Factory::class)
                        ->setArguments([
                            '$batchLoadFn' => $batchLoadFnDef,
                            '$promiseAdapter' => new Reference('overblog_dataloader.promise_adapter'),
                            '$scheduler' => new Reference(SyncScheduler::class),
                            '$cacheKeyFn' => $cacheKeyFnDef,
                        ]);
                    $container->registerAliasForArgument($id, Factory::class, $name);
                }

                public function process(ContainerBuilder $container): void
                {
                    foreach ($container->findTaggedServiceIds('overblog.dataloader') as $id => $tags) {
                        foreach ($tags as $attrs) {
                            $this->registerDataLoader(
                                $container,
                                /** @phpstan-ignore-next-line */
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
