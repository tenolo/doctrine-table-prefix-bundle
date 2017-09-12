<?php

namespace Tenolo\Bundle\DoctrineTablePrefixBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tenolo\Bundle\DoctrineTablePrefixBundle\EventListener\ResolveTargetEntityListener;

/**
 * Class ResolveTargetEntityCompilerPass
 *
 * @package Tenolo\Bundle\DoctrineTablePrefixBundle\DependencyInjection\Compiler
 * @author  Nikita Loges
 * @company tenolo GbR
 */
class ResolveTargetEntityCompilerPass implements CompilerPassInterface
{

    /**
     * @inheritDoc
     */
    public function process(ContainerBuilder $container)
    {
        $definition = $container->getDefinition('doctrine.orm.listeners.resolve_target_entity');
        $definition->setClass(ResolveTargetEntityListener::class);
    }

}
