<?php

namespace Tenolo\DoctrineTablePrefixBundle\Subscriber;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Common\Annotations\Reader;
use Symfony\Component\Security\Core\Util\StringUtils;
use Tenolo\CoreBundle\Util\Crypt;
use Tenolo\CoreBundle\Util\String;

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

        // Do not re-apply the prefix in an inheritance hierarchy.
        if (!$classMetadata->isInheritanceTypeSingleTable() || $classMetadata->isRootEntity()) {
            $classReflection = $classMetadata->getReflectionClass();
            $className = $classReflection->getName();

            if ($this->loadedClasses->contains($className)) {
                return;
            } else {
                $this->loadedClasses->add($className);
            }

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
        }

        foreach ($classMetadata->getAssociationMappings() as $fieldName => $mapping) {
            if ($mapping['type'] == ClassMetadataInfo::MANY_TO_MANY && isset($classMetadata->associationMappings[$fieldName]['joinTable']['name'])) {
                $sourceEntity = $classMetadata->associationMappings[$fieldName]['sourceEntity'];
                $targetEntity = $classMetadata->associationMappings[$fieldName]['targetEntity'];
                $serial = Crypt::getHash($sourceEntity . '-' . $targetEntity);

                if (!$this->processedAssociation->contains($serial)) {
                    $mappedTableName = $classMetadata->associationMappings[$fieldName]['joinTable']['name'];
                    $classMetadata->associationMappings[$fieldName]['joinTable']['name'] = $this->prefix . $mappedTableName . '_map';

                    $this->processedAssociation->add($serial);
                }
            }
        }
    }
}