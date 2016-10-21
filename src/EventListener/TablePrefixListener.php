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
 */
class TablePrefixListener extends AbstractService
{

    /** @var ArrayCollection */
    protected $loadedClasses;

    /** @var ArrayCollection */
    protected $processedAssociation;

    /**
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     */
    public function __construct($container)
    {
        parent::__construct($container);

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
                $serial = CryptUtil::getHash($sourceEntity . '-' . $targetEntity);

                // set only new associations
                if (!$this->getProcessedAssociation()->contains($serial)) {
                    $mappedTableName = $mapping['joinTable']['name'];

                    $mappedTableNameNew = $prefix . $this->getTableNameSeparator() . $mappedTableName . $this->getTableNameSeparator() . 'map';

                    // remove interface name and save new name
                    $mapping['joinTable']['name'] = $mappedTableNameNew;

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

        foreach ($namespaceParts as $key => $value) {
            if (empty($value)) {
                unset($namespaceParts[$key]);
            } else {
                if (!ctype_upper($value)) {
                    $values = preg_split('/(?=[A-Z])/', $value);

                    $values = array_map('strtolower', $values);
                    $values = array_filter($values, function ($el) {
                        return !empty($el);
                    });
                    $values = array_filter($values, function ($value) use ($blackList) {
                        return !in_array($value, $blackList);
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

        $namespaceParts = array_filter($namespaceParts, function ($el) {
            return !empty($el);
        });

        $namespacePrefix = implode($this->getTableNameSeparator(), $namespaceParts);
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
     * @return string
     */
    protected function getDatabasePrefix()
    {
        $prefix = $this->getContainer()->getParameter('tenolo_doctrine_table_prefix.database_prefix');

        if (!empty($prefix)) {
            $prefix = trim($prefix, '_');
        }

        return $prefix;
    }

    /**
     * @return string
     */
    protected function getTableNameSeparator()
    {
        return $this->getContainer()->getParameter('tenolo_doctrine_table_prefix.table_name_separator');
    }

    /**
     * @return boolean
     */
    protected function isAnnotationPrefixEnable()
    {
        return $this->getContainer()->getParameter('tenolo_doctrine_table_prefix.annotation_prefix.enable');
    }

    /**
     * @return boolean
     */
    protected function isNamespacePrefixEnable()
    {
        return $this->getContainer()->getParameter('tenolo_doctrine_table_prefix.namespace_prefix.enable');
    }

    /**
     * @return array
     */
    protected function getWordBlackList()
    {
        $blackList = array_merge($this->getDefaultWordBlackList(), $this->getConfigWordBlackList());

        $blackList = array_map('trim', $blackList);
        $blackList = array_filter($blackList, function ($value) {
            return !empty($value);
        });

        $blackList = array_map('strtolower', $blackList);

        return $blackList;
    }

    /**
     * @return array
     */
    protected function getConfigWordBlackList()
    {
        return $this->getContainer()->getParameter('tenolo_doctrine_table_prefix.namespace_prefix.word_blacklist');
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
}