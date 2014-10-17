<?php

namespace Tenolo\DoctrineTablePrefixBundle;

use Doctrine\Common\Annotations\AnnotationRegistry;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Class TenoloDoctrineTablePrefixBundle
 * @package Tenolo\DoctrineTablePrefixBundle
 * @author Nikita Loges
 * @company tenolo GbR
 */
class TenoloDoctrineTablePrefixBundle extends Bundle
{
    /**
     * @{@inheritdoc}
     */
    public function boot()
    {
        // register doctrine annotation
        AnnotationRegistry::registerAutoloadNamespace('Tenolo\DoctrineTablePrefixBundle\Doctrine\Annotations', __DIR__ . '/../..');
    }
}
