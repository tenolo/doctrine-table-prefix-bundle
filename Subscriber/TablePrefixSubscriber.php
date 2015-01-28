<?php

namespace Tenolo\Bundle\DoctrineTablePrefixBundle\Subscriber;

use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Tenolo\Bundle\CoreBundle\Util\Crypt;

/**
 * Class TablePrefixSubscriber
 *
 * @package Tenolo\Bundle\DoctrineTablePrefixBundle\Subscriber
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
     * @var ArrayCollection
     */
    protected $loadedClasses;

    /**
     * @var ArrayCollection
     */
    protected $processedAssociation;

    /**
     * @param $prefix
     * @param Reader $reader
     */
    public function __construct($prefix, Reader $reader)
    {
        $this->prefix = (string)$prefix;
        $this->reader = $reader;

        $this->loadedClasses = new ArrayCollection();
        $this->processedAssociation = new ArrayCollection();
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

        $classReflection = $classMetadata->getReflectionClass();
        $className = $classReflection->getName();
        $classAnnotation = $this->reader->getClassAnnotation($classReflection, 'Tenolo\Bundle\DoctrineTablePrefixBundle\Doctrine\Annotations\Prefix');

        $prefix = $this->prefix;
        if (!is_null($classAnnotation)) {
            $tablePrefix = trim($classAnnotation->name);

            if (!empty($tablePrefix)) {
                $prefix .= $tablePrefix . "_";
            }
        }

        // Do not re-apply the prefix in an inheritance hierarchy.
        if (!$classMetadata->isInheritanceTypeSingleTable() || $classMetadata->isRootEntity()) {

            if ($this->loadedClasses->contains($className)) {
                return;
            } else {
                $this->loadedClasses->add($className);
            }

            $classMetadata->setPrimaryTable(array(
                'name' => $prefix . $classMetadata->getTableName()
            ));
        }

        foreach ($classMetadata->getAssociationMappings() as $fieldName => $mapping) {
            if ($mapping['type'] == ClassMetadataInfo::MANY_TO_MANY && isset($classMetadata->associationMappings[$fieldName]['joinTable']['name'])) {
                $sourceEntity = $classMetadata->associationMappings[$fieldName]['sourceEntity'];
                $targetEntity = $classMetadata->associationMappings[$fieldName]['targetEntity'];
                $serial = Crypt::getHash($sourceEntity . '-' . $targetEntity);

                if (!$this->processedAssociation->contains($serial)) {
                    $mappedTableName = $classMetadata->associationMappings[$fieldName]['joinTable']['name'];
                    $classMetadata->associationMappings[$fieldName]['joinTable']['name'] = $prefix . $mappedTableName . '_map';

                    $this->processedAssociation->add($serial);
                }
            }
        }
    }
}