<?php

namespace Tenolo\DoctrineTablePrefixBundle\Subscriber;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Common\Annotations\Reader;

/**
 * Class TablePrefixSubscriber
 *
 * @package RabeConcept\MSSBundle\Subscriber
 * @author Nikita Loges
 * @company tenolo GbR
 * @date 03.06.14
 */
class TablePrefixSubscriber implements EventSubscriber
{

    /**
     * @var string
     */
    protected $prefix = '';

    /**
     * @param $prefix
     * @param Reader $reader
     */
    public function __construct($prefix, Reader $reader)
    {
        $this->prefix = (string)$prefix;
        $this->reader = $reader;
    }

    /**
     * @return array
     */
    public function getSubscribedEvents()
    {
        return array('loadClassMetadata');
    }

    /**
     * @param LoadClassMetadataEventArgs $args
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $args)
    {
        /** @var ClassMetadata $classMetadata */
        $classMetadata = $args->getClassMetadata();

        // Do not re-apply the prefix in an inheritance hierarchy.
        if ($classMetadata->isInheritanceTypeSingleTable() && !$classMetadata->isRootEntity()) {
            return;
        }

        $classReflection = $classMetadata->getReflectionClass();
        $classAnnotation = $this->reader->getClassAnnotation($classReflection, 'Tenolo\DoctrineTablePrefixBundle\Doctrine\Annotations\Prefix');

        $prefix = $this->prefix;

        if (!is_null($classAnnotation)) {
            $tablePrefix = trim($classAnnotation->name);

            if (!empty($tablePrefix)) {
                $prefix .= $tablePrefix . "_";
            }
        }

        $classMetadata->setPrimaryTable(array(
            'name' => $prefix . $classMetadata->getTableName()
        ));

        foreach ($classMetadata->getAssociationMappings() as $fieldName => $mapping) {
            if ($mapping['type'] == ClassMetadataInfo::MANY_TO_MANY && isset($classMetadata->associationMappings[$fieldName]['joinTable']['name'])) {
                $mappedTableName = $classMetadata->associationMappings[$fieldName]['joinTable']['name'];
                $classMetadata->associationMappings[$fieldName]['joinTable']['name'] = $this->prefix . $mappedTableName;
            }
        }
    }
}