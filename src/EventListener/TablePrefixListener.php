<?php

namespace Tenolo\Bundle\DoctrineTablePrefixBundle\EventListener;

use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Tenolo\Bundle\DoctrineTablePrefixBundle\Doctrine\Annotations\Prefix;
use Tenolo\Utilities\Utils\CryptUtil;

/**
 * Class TablePrefixListener
 *
 * @package Tenolo\Bundle\DoctrineTablePrefixBundle\EventListener
 * @author  Nikita Loges
 * @company tenolo GbR
 */
class TablePrefixListener
{

    /** @var ManagerRegistry */
    protected $registry;

    /** @var Reader */
    protected $annotationReader;

    /** @var ArrayCollection */
    protected $loadedClasses;

    /** @var ArrayCollection */
    protected $processedAssociation;

    /** @var string */
    protected $databasePrefix;

    /** @var string */
    protected $tableNameSeparator;

    /** @var boolean */
    protected $annotationPrefixEnable = true;

    /** @var boolean */
    protected $namespacePrefixEnable = true;

    /** @var boolean */
    protected $renameRelations = true;

    /** @var array */
    protected $wordBlackList = [];

    /** @var array */
    protected $namespaceReplacements = [];

    /**
     * @param ManagerRegistry $registry
     * @param Reader          $annotationReader
     */
    public function __construct(ManagerRegistry $registry, Reader $annotationReader)
    {
        $this->registry = $registry;
        $this->annotationReader = $annotationReader;

        $this->loadedClasses = new ArrayCollection();
        $this->processedAssociation = new ArrayCollection();
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

        $em = $this->getRegistry()->getManagerForClass($classReflection->getName());
        $namingStrategy = $em->getConfiguration()->getNamingStrategy();

        $prefixes = new ArrayCollection();

        $this->addDatabasePrefix($prefixes);

        if ($this->isNamespacePrefixEnable()) {
            $this->addNamespacePrefix($prefixes, $classReflection);
        }

        if ($this->isAnnotationPrefixEnable()) {
            $this->addAnnotationPrefix($prefixes, $classReflection);
        }

        $className = $classReflection->getName();
        $prefix = implode($this->getTableNameSeparator(), $prefixes->toArray());
        $prefix = rtrim($prefix, '_');  

        // Do not re-apply the prefix in an inheritance hierarchy.
        if (!$classMetadata->isInheritanceTypeSingleTable() || $classMetadata->isRootEntity()) {
            if ($this->getLoadedClasses()->contains($className)) {
                return;
            } else {
                $this->getLoadedClasses()->add($className);
            }

            $classTableName = $prefix . $this->getTableNameSeparator() . $classMetadata->getTableName();

            $classMetadata->setPrimaryTable([
                'name' => $classTableName
            ]);
        }

        foreach ($classMetadata->getAssociationMappings() as $fieldName => $mapping) {
            if ($mapping['type'] == ClassMetadataInfo::MANY_TO_MANY && isset($mapping['joinTable']['name'])) {
                $sourceEntity = $mapping['sourceEntity'];
                $targetEntity = $mapping['targetEntity'];

                if ($this->isUnidirectional($mapping)) {
                    $serial = CryptUtil::getHash($sourceEntity . '-' . $targetEntity . '-' . $fieldName);
                } else {
                    $serial = CryptUtil::getHash($sourceEntity . '-' . $targetEntity);
                }

                // set only new associations
                if (!$this->getProcessedAssociation()->contains($serial)) {

                    if ($this->isRenameRelations()) {
                        if ($this->isUnidirectional($mapping)) {
                            $newClassTableName = $namingStrategy->classToTableName($classReflection->getShortName());
                            $newPropertyTableName = $namingStrategy->propertyToColumnName($mapping['fieldName']);
                            $mappedTableName = $newClassTableName . $this->getTableNameSeparator() . $newPropertyTableName;
                        } else {
                            $mappedTableName = $mapping['joinTable']['name'] . $this->getTableNameSeparator() . 'map';
                        }
                    } else {
                        $mappedTableName = $mapping['joinTable']['name'];
                    }

                    $mappedTableName = $prefix . $this->getTableNameSeparator() . $mappedTableName;

                    // remove interface name and save new name
                    $mapping['joinTable']['name'] = $mappedTableName;

                    // set new association
                    unset($classMetadata->associationMappings[$mapping['fieldName']]);
                    $classMetadata->mapManyToMany($mapping);

                    // remember
                    $this->getProcessedAssociation()->add($serial);
                }
            }
        }
    }

    /**
     * @param ArrayCollection $collection
     */
    protected function addDatabasePrefix(ArrayCollection $collection)
    {
        $databasePrefix = $this->getDatabasePrefix();
        if (!empty($databasePrefix)) {
            $collection->add($databasePrefix);
        }
    }

    /**
     * @param ArrayCollection  $collection
     * @param \ReflectionClass $classReflection
     */
    protected function addNamespacePrefix(ArrayCollection $collection, \ReflectionClass $classReflection)
    {
        $namespace = $classReflection->getNamespaceName();
        $namespaceParts = explode('\\', $namespace);
        $blackList = $this->getWordBlackList();
        $replacements = $this->getNamespaceReplacements();

        foreach ($namespaceParts as $key => $value) {
            if (empty($value)) {
                unset($namespaceParts[$key]);
            } else {
                if (!ctype_upper($value)) {
                    $value = str_ireplace($blackList, '', $value);
                    $values = preg_split('/(?=[A-Z])/', $value);

                    $values = array_map('strtolower', $values);

                    $values = array_filter($values, function ($value) use ($blackList) {
                        return !in_array($value, $blackList);
                    });
                    $values = array_map(function ($value) use ($replacements) {
                        if (array_key_exists($value, $replacements)) {
                            return $replacements[$value];
                        }

                        return $value;
                    }, $values);
                    $values = array_filter($values, function ($el) {
                        return !empty($el);
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

                $value = strtolower($value);

                if (!in_array($value, $blackList)) {
                    $namespaceParts[$key] = $value;
                } else {
                    unset($namespaceParts[$key]);
                }

            }
        }

        $namespaceParts = array_filter($namespaceParts, function ($el) {
            return !empty($el);
        });

        if (!empty($namespaceParts)) {
            $namespacePrefix = implode($this->getTableNameSeparator(), $namespaceParts);
        } else {
            $namespacePrefix = '';
        }
        $collection->add($namespacePrefix);
    }

    /**
     * @param ArrayCollection  $collection
     * @param \ReflectionClass $classReflection
     */
    protected function addAnnotationPrefix(ArrayCollection $collection, \ReflectionClass $classReflection)
    {
        $classAnnotation = $this->getAnnotationReader()->getClassAnnotation($classReflection, Prefix::class);

        if (!is_null($classAnnotation)) {
            $tablePrefix = trim($classAnnotation->name);

            if (!empty($tablePrefix)) {
                $collection->add($tablePrefix);
            }
        }
    }

    /**
     * @return ManagerRegistry
     */
    public function getRegistry()
    {
        return $this->registry;
    }

    /**
     * @return Reader
     */
    public function getAnnotationReader()
    {
        return $this->annotationReader;
    }

    /**
     * @param $mapping
     *
     * @return bool
     */
    protected function isUnidirectional($mapping)
    {
        return is_null($mapping['inversedBy']);
    }

    /**
     * @param $mapping
     *
     * @return bool
     */
    protected function isBidirectional($mapping)
    {
        return !$this->isUnidirectional($mapping);
    }

    /**
     * @return array
     */
    protected function getDefaultWordBlackList()
    {
        return [
            'entity',
            'entities',
            'interface',
            'interfaces',
            'bundle',
            'bundles',
            'application',
            'applications',
            'extension',
            'extensions'
        ];
    }

    /**
     * @param array $wordBlackList
     */
    public function setWordBlackList(array $wordBlackList = [])
    {
        $this->wordBlackList = $wordBlackList;
    }

    /**
     * @return array
     */
    protected function getWordBlackList()
    {
        $blackList = array_merge($this->getDefaultWordBlackList(), $this->wordBlackList);

        $blackList = array_map('trim', $blackList);
        $blackList = array_filter($blackList, function ($value) {
            return !empty($value);
        });

        $blackList = array_map('strtolower', $blackList);

        return $blackList;
    }

    /**
     * @return string
     */
    public function getDatabasePrefix()
    {
        return $this->databasePrefix;
    }

    /**
     * @param string $databasePrefix
     */
    public function setDatabasePrefix($databasePrefix)
    {
        $this->databasePrefix = trim($databasePrefix, '_');
    }

    /**
     * @return string
     */
    public function getTableNameSeparator()
    {
        return $this->tableNameSeparator;
    }

    /**
     * @param string $tableNameSeparator
     */
    public function setTableNameSeparator($tableNameSeparator)
    {
        $this->tableNameSeparator = $tableNameSeparator;
    }

    /**
     * @return bool
     */
    public function isAnnotationPrefixEnable()
    {
        return $this->annotationPrefixEnable;
    }

    /**
     * @param bool $annotationPrefixEnable
     */
    public function setAnnotationPrefixEnable($annotationPrefixEnable)
    {
        $this->annotationPrefixEnable = $annotationPrefixEnable;
    }

    /**
     * @return bool
     */
    public function isNamespacePrefixEnable()
    {
        return $this->namespacePrefixEnable;
    }

    /**
     * @param bool $namespacePrefixEnable
     */
    public function setNamespacePrefixEnable($namespacePrefixEnable)
    {
        $this->namespacePrefixEnable = $namespacePrefixEnable;
    }

    /**
     * @return array
     */
    public function getNamespaceReplacements()
    {
        return $this->namespaceReplacements;
    }

    /**
     * @param array $namespaceReplacements
     */
    public function setNamespaceReplacements($namespaceReplacements)
    {
        $this->namespaceReplacements = $namespaceReplacements;
    }

    /**
     * @return bool
     */
    public function isRenameRelations()
    {
        return $this->renameRelations;
    }

    /**
     * @param bool $renameRelations
     */
    public function setRenameRelations($renameRelations)
    {
        $this->renameRelations = $renameRelations;
    }
}