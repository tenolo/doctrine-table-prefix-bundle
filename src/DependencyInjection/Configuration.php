<?php

namespace Tenolo\Bundle\DoctrineTablePrefixBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Class Configuration
 *
 * @package Tenolo\Bundle\DoctrineTablePrefixBundle\DependencyInjection
 * @author  Nikita Loges
 * @company tenolo GbR
 */
class Configuration implements ConfigurationInterface
{

    /**
     * @inheritDoc
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('tenolo_doctrine_table_prefix');

        $rootNode
            ->children()
                ->scalarNode('table_name_separator')->defaultValue('_')->cannotBeEmpty()->end()
                ->scalarNode('database_prefix')->defaultNull()->end()
                ->booleanNode('rename_join_table')->defaultTrue()->end()
                ->arrayNode('namespace_prefix')
                    ->addDefaultsIfNotSet()
                        ->children()
                            ->booleanNode('enable')->defaultFalse()->end()
                            ->arrayNode('word_blacklist')
                                ->prototype('scalar')->end()
                            ->end()
                            ->arrayNode('replacements')->useAttributeAsKey('name')->prototype('scalar')->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('annotation_prefix')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enable')->defaultTrue()->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }

}
