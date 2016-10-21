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
 * @date    ${DATE}
 */
class TenoloDoctrineTablePrefixExtension extends ConfigurableExtension
{

    /**
     * {@inheritDoc}
     */
    public function loadInternal(array $configs, ContainerBuilder $container)
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');

        $container->setParameter('tenolo_doctrine_table_prefix.table_name_separator', $configs['table_name_separator']);
        $container->setParameter('tenolo_doctrine_table_prefix.database_prefix', $configs['database_prefix']);
        $container->setParameter('tenolo_doctrine_table_prefix.namespace_prefix.enable', $configs['namespace_prefix']['enable']);
        $container->setParameter('tenolo_doctrine_table_prefix.namespace_prefix.word_blacklist', $configs['namespace_prefix']['word_blacklist']);
        $container->setParameter('tenolo_doctrine_table_prefix.annotation_prefix.enable', $configs['annotation_prefix']['enable']);
    }
}
