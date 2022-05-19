<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Resources\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class HookablePass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        foreach ($container->findTaggedServiceIds('import_export.proxyable') as $id => $options) {
            $definition = $container->getDefinition($id);

            $arguments = $definition->getArguments();
            $definition->setArguments([
                new Reference('hooks'),
                $definition->getClass(),
                $arguments,
            ]);
            $definition->setFactory('Shopware\Components\DependencyInjection\ProxyFactory::getProxy');
        }
    }
}
