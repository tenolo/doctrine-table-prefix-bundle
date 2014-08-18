<?php

namespace Tenolo\DoctrineTablePrefixBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Class Configuration
 * @package Tenolo\DoctrineTablePrefixBundle\DependencyInjection
 * @author Nikita Loges
 * @company tenolo GbR
 */
class Configuration implements ConfigurationInterface
{

    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('tenolo_doctrine_table_prefix');

        return $treeBuilder;
    }
}
