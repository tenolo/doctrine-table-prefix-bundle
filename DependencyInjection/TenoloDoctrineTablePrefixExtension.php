<?php

namespace Tenolo\DoctrineTablePrefixBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * Class TenoloDoctrineTablePrefixExtension
 * @package Tenolo\DoctrineTablePrefixBundle\DependencyInjection
 * @author Nikita Loges
 * @company tenolo GbR
 * @date ${DATE}
 */
class TenoloDoctrineTablePrefixExtension extends Extension
{

    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');
    }
}
