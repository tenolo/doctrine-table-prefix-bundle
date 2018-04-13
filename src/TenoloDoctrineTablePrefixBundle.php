<?php

namespace Tenolo\Bundle\DoctrineTablePrefixBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Tenolo\Bundle\DoctrineTablePrefixBundle\DependencyInjection\Compiler\ResolveTargetEntityCompilerPass;

/**
 * Class TenoloDoctrineTablePrefixBundle
 *
 * @package Tenolo\Bundle\DoctrineTablePrefixBundle
 * @author  Nikita Loges
 * @company tenolo GbR
 */
class TenoloDoctrineTablePrefixBundle extends Bundle
{

    /**
     * @inheritDoc
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new ResolveTargetEntityCompilerPass());
    }
}
