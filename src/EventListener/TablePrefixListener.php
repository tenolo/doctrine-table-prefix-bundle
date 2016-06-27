<?php

namespace Tenolo\Bundle\DoctrineTablePrefixBundle\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Tenolo\Bundle\CoreBundle\Service\AbstractService;
use Tenolo\Bundle\DoctrineTablePrefixBundle\Doctrine\Annotations\Prefix;
use Tenolo\Utilities\Utils\CryptUtil;

/**
 * Class TablePrefixListener
 *
 * @package Tenolo\Bundle\DoctrineTablePrefixBundle\EventListener
 * @author  Nikita Loges
 * @company tenolo GbR
 * @date    03.06.14
 */
class TablePrefixListener extends AbstractService
{

    /** @var string */
    protected $prefix = '';

    /** @var ArrayCollection */
    protected $loadedClasses;

    /** @var ArrayCollection */
    protected $processedAssociation;

    /**
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     * @param                                                           $prefix
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

        if (!$classReflection) {
            return;
        }

        $className = $classReflection->getName();
        $classAnnotation = $this->getAnnotationReader()->getClassAnnotation($classReflection, Prefix::class);

        $namespace = $classReflection->getNamespaceName();
        $namespaceParts = explode('\\', $namespace);

        foreach ($namespaceParts as $key => $value) {
            $value = str_replace(['Entity', 'Bundle', 'Application', 'Extension'], '', $value);

            if (empty($value)) {
                unset($namespaceParts[$key]);
            } else {
                if (!ctype_upper($value)) {
                    $values = preg_split('/(?=[A-Z])/', $value);
                    $values = array_filter($values, function($el) {
                        return (!empty($el));                        
                    });

                    if (count($values) > 1) {
                        $value = '';
                        foreach ($values as $v) {
                            $value .= $v[0];
                        }
                    } else {
                        $value = array_shift($values);
                    }
                }

                $namespaceParts[$key] = $value;
            }
        }

        $namespacePrefix = strtolower(implode('_', $namespaceParts));

        $prefix = $this->prefix . $namespacePrefix . '_';
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

            $classMetadata->setPrimaryTable([
                'name' => $prefix . $classMetadata->getTableName()
            ]);
        }

        foreach ($classMetadata->getAssociationMappings() as $fieldName => $mapping) {
            if ($mapping['type'] == ClassMetadataInfo::MANY_TO_MANY && isset($mapping['joinTable']['name'])) {
                $sourceEntity = $mapping['sourceEntity'];
                $targetEntity = $mapping['targetEntity'];
                $serial = CryptUtil::getHash($sourceEntity . '-' . $targetEntity);

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