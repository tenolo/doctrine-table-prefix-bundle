<?php

namespace Tenolo\Bundle\DoctrineTablePrefixBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

/**
 * Class TenoloDoctrineTablePrefixExtension
 *
 * @package Tenolo\Bundle\DoctrineTablePrefixBundle\DependencyInjection
 * @author  Nikita Loges
 * @company tenolo GbR
 */
class TenoloDoctrineTablePrefixExtension extends ConfigurableExtension
{

    /**
     * @inheritDoc
     */
    public function loadInternal(array $config, ContainerBuilder $container)
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');

        $container->setParameter('tenolo_doctrine_table_prefix.table_name_separator', $config['table_name_separator']);
        $container->setParameter('tenolo_doctrine_table_prefix.database_prefix', $config['database_prefix']);
        $container->setParameter('tenolo_doctrine_table_prefix.rename_join_table', $config['rename_join_table']);
        $container->setParameter('tenolo_doctrine_table_prefix.namespace_prefix.enable', $config['namespace_prefix']['enable']);
        $container->setParameter('tenolo_doctrine_table_prefix.namespace_prefix.word_blacklist', $config['namespace_prefix']['word_blacklist']);
        $container->setParameter('tenolo_doctrine_table_prefix.namespace_prefix.replacements', $config['namespace_prefix']['replacements']);
        $container->setParameter('tenolo_doctrine_table_prefix.annotation_prefix.enable', $config['annotation_prefix']['enable']);
    }
}
