<?php

namespace Tenolo\Bundle\DoctrineTablePrefixBundle;

use Doctrine\Common\Annotations\AnnotationRegistry;
use Mmoreram\SymfonyBundleDependencies\DependentBundleInterface;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\HttpKernel\KernelInterface;
use Tenolo\Bundle\CoreBundle\TenoloCoreBundle;

/**
 * Class TenoloDoctrineTablePrefixBundle
 * @package Tenolo\Bundle\DoctrineTablePrefixBundle
 * @author Nikita Loges
 * @company tenolo GbR
 */
class TenoloDoctrineTablePrefixBundle extends Bundle implements DependentBundleInterface
{

    /**
     * @inheritdoc
     */
    public static function getBundleDependencies(KernelInterface $kernel)
    {
        return [
            FrameworkBundle::class,
            TenoloCoreBundle::class,
        ];
    }

    /**
     * @inheritdoc
     */
    public function boot()
    {
        // register doctrine annotation
        AnnotationRegistry::registerFile(__DIR__."/Doctrine/Annotations/Prefix.php");
    }
}
