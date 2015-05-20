<?php

namespace Tenolo\Bundle\DoctrineTablePrefixBundle\EventListener;

use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Tenolo\Bundle\CoreBundle\Service\AbstractService;
use Tenolo\Bundle\CoreBundle\Util\Crypt;
use Tenolo\Bundle\CoreBundle\Util\String;

/**
 * Class TablePrefixListener
 *
 * @package Tenolo\Bundle\DoctrineTablePrefixBundle\EventListener
 * @author Nikita Loges
 * @company tenolo GbR
 * @date 03.06.14
 */
class TablePrefixListener extends AbstractService
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
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     * @param $prefix
     */
    public function __construct($container, $prefix)
    {
        parent::__construct($container);

        $this->prefix = (string)$prefix;

        $this->loadedClasses = new ArrayCollection();
        $this->processedAssociation = new ArrayCollection();
    }

    /**
     * @return string
     */
    protected function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * @return ArrayCollection
     */
    protected function getLoadedClasses()
    {
        return $this->loadedClasses;
    }

    /**
     * @return ArrayCollection
     */
    protected function getProcessedAssociation()
    {
        return $this->processedAssociation;
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
        $classAnnotation = $this->getAnnotationReader()->getClassAnnotation($classReflection, 'Tenolo\Bundle\DoctrineTablePrefixBundle\Doctrine\Annotations\Prefix');

        $prefix = $this->prefix;
        if (!is_null($classAnnotation)) {
            $tablePrefix = trim($classAnnotation->name);

            if (!empty($tablePrefix)) {
                $prefix .= $tablePrefix . "_";
            }
        }

        // Do not re-apply the prefix in an inheritance hierarchy.
        if (!$classMetadata->isInheritanceTypeSingleTable() || $classMetadata->isRootEntity()) {

            if ($this->getLoadedClasses()->contains($className)) {
                return;
            } else {
                $this->getLoadedClasses()->add($className);
            }

            $classMetadata->setPrimaryTable(array(
                'name' => $prefix . $classMetadata->getTableName()
            ));
        }

        foreach ($classMetadata->getAssociationMappings() as $fieldName => $mapping) {
            if ($mapping['type'] == ClassMetadataInfo::MANY_TO_MANY && isset($mapping['joinTable']['name'])) {
                $sourceEntity = $mapping['sourceEntity'];
                $targetEntity = $mapping['targetEntity'];
                $serial = Crypt::getHash($sourceEntity . '-' . $targetEntity);

                // set only new associations
                if (!$this->getProcessedAssociation()->contains($serial)) {
                    $mappedTableName = $mapping['joinTable']['name'];

                    // remove interface name and save new name
                    $mapping['joinTable']['name'] = $prefix . $mappedTableName . '_map';

                    // set new association
                    unset($classMetadata->associationMappings[$mapping['fieldName']]);
                    $classMetadata->mapManyToMany($mapping);

                    // remember
                    $this->getProcessedAssociation()->add($serial);
                }
            }
        }
    }
}