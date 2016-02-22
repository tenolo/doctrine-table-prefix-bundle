<?php

namespace Tenolo\Bundle\DoctrineTablePrefixBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * Class TenoloDoctrineTablePrefixExtension
 * @package Tenolo\Bundle\DoctrineTablePrefixBundle\DependencyInjection
 * @author Nikita Loges
 * @company tenolo GbR
 */
class TenoloDoctrineTablePrefixExtension extends Extension
{

    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');
    }
}
