<?php

namespace Tenolo\Bundle\DoctrineTablePrefixBundle\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;

/**
 * Class ResolveTargetEntityListener
 * @package Tenolo\Bundle\DoctrineTablePrefixBundle\EventListener
 * @author Nikita Loges
 * @company tenolo GbR
 * @date 05.03.2015
 */
class ResolveTargetEntityListener implements EventSubscriber
{

    /**
     * @var array
     */
    protected $resolveTargetEntities = array();

    /**
     * @return array
     */
    public function getSubscribedEvents()
    {
        return array(
          'loadClassMetadata',
        );
    }

    /**
     * Adds a target-entity class name to resolve to a new class name.
     *
     * @param string $originalEntity
     * @param string $newEntity
     * @param array  $mapping
     *
     * @return void
     */
    public function addResolveTargetEntity($originalEntity, $newEntity, array $mapping)
    {
        $mapping['targetEntity'] = ltrim($newEntity, "\\");
        $this->resolveTargetEntities[ltrim($originalEntity, "\\")] = $mapping;
    }

    /**
     * Processes event and resolves new target entity names.
     *
     * @param LoadClassMetadataEventArgs $args
     *
     * @return void
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $args)
    {
        $cm = $args->getClassMetadata();

        foreach ($cm->associationMappings as $mapping) {
            if (isset($this->resolveTargetEntities[$mapping['targetEntity']])) {
                $this->remapAssociation($cm, $mapping);
            }
        }
    }

    /**
     * @param \Doctrine\ORM\Mapping\ClassMetadataInfo $classMetadata
     * @param array $mapping
     *
     * @return void
     */
    protected function remapAssociation($classMetadata, $mapping)
    {
        $newMapping = $this->resolveTargetEntities[$mapping['targetEntity']];
        $newMapping = array_replace_recursive($mapping, $newMapping);
        $newMapping['fieldName'] = $mapping['fieldName'];

        unset($classMetadata->associationMappings[$mapping['fieldName']]);

        switch ($mapping['type']) {
            case ClassMetadata::MANY_TO_MANY:
                $this->remapManyToManyAssociation($classMetadata, $newMapping);
                break;
            case ClassMetadata::MANY_TO_ONE:
                $this->remapManyToOneAssociation($classMetadata, $newMapping);
                break;
            case ClassMetadata::ONE_TO_MANY:
                $this->remapOneToManyAssociation($classMetadata, $newMapping);
                break;
            case ClassMetadata::ONE_TO_ONE:
                $this->remapOneToOneAssociation($classMetadata, $newMapping);
                break;
        }
    }

    /**
     * @param ClassMetadataInfo $classMetadata
     * @param $mapping
     */
    protected function remapManyToManyAssociation(ClassMetadataInfo $classMetadata, $mapping)
    {
        // reset stuff to generate new one
        unset($mapping['joinTable']['name']);
        unset($mapping['joinTable']['joinColumns']);
        unset($mapping['joinTable']['inverseJoinColumns']);
        unset($mapping['relationToSourceKeyColumns']);
        unset($mapping['relationToTargetKeyColumns']);

        $classMetadata->mapManyToMany($mapping);
    }

    /**
     * @param ClassMetadataInfo $classMetadata
     * @param $mapping
     */
    protected function remapManyToOneAssociation(ClassMetadataInfo $classMetadata, $mapping)
    {
        $classMetadata->mapManyToOne($mapping);
    }

    /**
     * @param ClassMetadataInfo $classMetadata
     * @param $mapping
     */
    protected function remapOneToManyAssociation(ClassMetadataInfo $classMetadata, $mapping)
    {
        $classMetadata->mapOneToMany($mapping);
    }

    /**
     * @param ClassMetadataInfo $classMetadata
     * @param $mapping
     */
    protected function remapOneToOneAssociation(ClassMetadataInfo $classMetadata, $mapping)
    {
        $classMetadata->mapOneToOne($mapping);
    }
}
