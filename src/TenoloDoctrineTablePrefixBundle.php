<?php

namespace Tenolo\Bundle\DoctrineTablePrefixBundle;

use Doctrine\Common\Annotations\AnnotationRegistry;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Class TenoloDoctrineTablePrefixBundle
 * @package Tenolo\Bundle\DoctrineTablePrefixBundle
 * @author Nikita Loges
 * @company tenolo GbR
 */
class TenoloDoctrineTablePrefixBundle extends Bundle
{

    /**
     * @inheritdoc
     */
    public function boot()
    {
        // register doctrine annotation
        AnnotationRegistry::registerFile(__DIR__."/Doctrine/Annotations/Prefix.php");
    }
}
