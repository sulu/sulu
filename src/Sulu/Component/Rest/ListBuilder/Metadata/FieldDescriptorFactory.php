<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\ListBuilder\Metadata;

use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineCaseFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineConcatenationFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineCountFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineDescriptor;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineGroupConcatFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineIdentityFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineJoinDescriptor;
use Sulu\Component\Rest\ListBuilder\FieldDescriptor;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

/**
 * Creates legacy field-descriptors for metadata.
 */
class FieldDescriptorFactory implements FieldDescriptorFactoryInterface, CacheWarmerInterface
{
    /**
     * @var DatagridXmlLoader
     */
    private $datagridXmlLoader;

    /**
     * @var string[]
     */
    private $datagridDirectories;

    /**
     * @var string
     */
    private $cachePath;

    /**
     * @var bool
     */
    private $debug;

    public function __construct(
        DatagridXmlLoader $datagridXmlLoader,
        array $datagridDirectories,
        string $cachePath,
        bool $debug
    ) {
        $this->datagridXmlLoader = $datagridXmlLoader;
        $this->datagridDirectories = $datagridDirectories;
        $this->cachePath = $cachePath;
        $this->debug = $debug;
    }

    public function warmUp($cacheDir)
    {
        $datagridsMetadataByKey = [];

        $datagridFinder = (new Finder())->in($this->datagridDirectories)->name('*.xml');
        foreach ($datagridFinder as $datagridFile) {
            $datagridMetadata = $this->datagridXmlLoader->load($datagridFile->getPathName());
            $datagridKey = $datagridMetadata->getKey();
            if (!array_key_exists($datagridKey, $datagridsMetadataByKey)) {
                $datagridsMetadataByKey[$datagridKey] = [];
            }

            $datagridsMetadataByKey[$datagridKey][] = $datagridMetadata;
        }

        /** @var AbstractPropertyMetadata $propertyMetadata */
        foreach ($datagridsMetadataByKey as $datagridKey => $datagridsMetadata) {
            $fieldDescriptors = [];
            foreach ($datagridsMetadata as $datagridMetadata) {
                foreach ($datagridMetadata->getPropertiesMetadata() as $propertyMetadata) {
                    $fieldDescriptor = null;
                    $options = [];
                    if ($propertyMetadata instanceof ConcatenationPropertyMetadata) {
                        $fieldDescriptor = $this->getConcatenationFieldDescriptor(
                            $propertyMetadata,
                            $options
                        );
                    } elseif ($propertyMetadata instanceof GroupConcatPropertyMetadata) {
                        $fieldDescriptor = $this->getGroupConcatenationFieldDescriptor(
                            $propertyMetadata,
                            $options
                        );
                    } elseif ($propertyMetadata instanceof IdentityPropertyMetadata) {
                        $fieldDescriptor = $this->getIdentityFieldDescriptor(
                            $propertyMetadata,
                            $options
                        );
                    } elseif ($propertyMetadata instanceof SinglePropertyMetadata) {
                        $fieldDescriptor = $this->getSingleFieldDescriptor(
                            $propertyMetadata,
                            $options
                        );
                    } elseif ($propertyMetadata instanceof CountPropertyMetadata) {
                        $fieldDescriptor = $this->getCountFieldDescriptor(
                            $propertyMetadata
                        );
                    } elseif ($propertyMetadata instanceof CasePropertyMetadata) {
                        $fieldDescriptor = $this->getCaseFieldDescriptor(
                            $propertyMetadata,
                            $options
                        );
                    }

                    if (null !== $fieldDescriptor) {
                        $fieldDescriptor->setMetadata($propertyMetadata);
                        $fieldDescriptors[$propertyMetadata->getName()] = $fieldDescriptor;
                    }
                }
            }

            $configCache = $this->getConfigCache($datagridKey);
            $configCache->write(serialize($fieldDescriptors), array_map(function(DatagridMetadata $datagridMetadata) {
                return new FileResource($datagridMetadata->getResource());
            }, $datagridsMetadata));
        }
    }

    public function isOptional()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldDescriptors(string $datagridKey): ?array
    {
        $configCache = $this->getConfigCache($datagridKey);

        if (!$configCache->isFresh()) {
            $this->warmUp($this->cachePath);
        }

        if (!file_exists($configCache->getPath())) {
            return null;
        }

        return unserialize(file_get_contents($configCache->getPath()));
    }

    private function getSingleFieldDescriptor(AbstractPropertyMetadata $propertyMetadata, $options)
    {
        return $this->getFieldDescriptor($propertyMetadata, $propertyMetadata->getField(), $options);
    }

    private function getFieldDescriptor(
        AbstractPropertyMetadata $propertyMetadata,
        ?FieldMetadata $fieldMetadata,
        $options
    ): FieldDescriptor {
        $joins = [];
        if ($fieldMetadata) {
            foreach ($fieldMetadata->getJoins() as $joinMetadata) {
                $joins[$joinMetadata->getEntityName()] = new DoctrineJoinDescriptor(
                    $this->resolveOptions($joinMetadata->getEntityName(), $options),
                    $this->resolveOptions($joinMetadata->getEntityField(), $options),
                    $this->resolveOptions($joinMetadata->getCondition(), $options),
                    $joinMetadata->getMethod(),
                    $joinMetadata->getConditionMethod()
                );
            }

            return new DoctrineFieldDescriptor(
                $this->resolveOptions($fieldMetadata->getName(), $options),
                $this->resolveOptions($propertyMetadata->getName(), $options),
                $this->resolveOptions($fieldMetadata->getEntityName(), $options),
                $propertyMetadata->getTranslation(),
                $joins,
                $propertyMetadata->getVisibility(),
                $propertyMetadata->getSearchability(),
                $propertyMetadata->getType(),
                $propertyMetadata->isSortable()
            );
        }

        // TODO handle this in a separate "display-property" tag?
        return new FieldDescriptor(
            $this->resolveOptions($propertyMetadata->getName(), $options),
            $propertyMetadata->getTranslation(),
            $propertyMetadata->getVisibility(),
            $propertyMetadata->getSearchability(),
            $propertyMetadata->getType(),
            $propertyMetadata->isSortable()
        );
    }

    private function getCountFieldDescriptor(AbstractPropertyMetadata $propertyMetadata)
    {
        $joins = [];
        foreach ($propertyMetadata->getField()->getJoins() as $joinMetadata) {
            $joins[$joinMetadata->getEntityName()] = new DoctrineJoinDescriptor(
                $joinMetadata->getEntityName(),
                $joinMetadata->getEntityField(),
                $joinMetadata->getCondition(),
                $joinMetadata->getMethod(),
                $joinMetadata->getConditionMethod()
            );
        }

        return new DoctrineCountFieldDescriptor(
            $propertyMetadata->getField()->getName(),
            $propertyMetadata->getName(),
            $propertyMetadata->getField()->getEntityName(),
            $propertyMetadata->getTranslation(),
            $joins,
            $propertyMetadata->getVisibility(),
            $propertyMetadata->getSearchability(),
            $propertyMetadata->getType(),
            $propertyMetadata->isSortable()
        );
    }

    private function getConcatenationFieldDescriptor(
        ConcatenationPropertyMetadata $propertyMetadata,
        $options
    ): DoctrineConcatenationFieldDescriptor {
        return new DoctrineConcatenationFieldDescriptor(
            array_map(
                function (FieldMetadata $fieldMetadata) use ($propertyMetadata, $options) {
                    return $this->getFieldDescriptor($propertyMetadata, $fieldMetadata, $options);
                },
                $propertyMetadata->getFields()
            ),
            $this->resolveOptions($propertyMetadata->getName(), $options),
            $propertyMetadata->getTranslation(),
            $this->resolveOptions($propertyMetadata->getGlue(), $options),
            $propertyMetadata->getVisibility(),
            $propertyMetadata->getSearchability(),
            $propertyMetadata->getType(),
            $propertyMetadata->isSortable()
        );
    }

    private function getGroupConcatenationFieldDescriptor(
        GroupConcatPropertyMetadata $propertyMetadata,
        $options
    ): DoctrineGroupConcatFieldDescriptor {
        return new DoctrineGroupConcatFieldDescriptor(
            $this->getFieldDescriptor($propertyMetadata, $propertyMetadata->getField(), $options),
            $this->resolveOptions($propertyMetadata->getName(), $options),
            $this->resolveOptions($propertyMetadata->getTranslation(), $options),
            $this->resolveOptions($propertyMetadata->getGlue(), $options),
            $propertyMetadata->getVisibility(),
            $propertyMetadata->getSearchability(),
            $propertyMetadata->getType(),
            $propertyMetadata->isSortable(),
            $this->resolveOptions($propertyMetadata->getDistinct(), $options)
        );
    }

    private function getIdentityFieldDescriptor(
        IdentityPropertyMetadata $propertyMetadata,
        $options
    ) {
        $fieldMetadata = $propertyMetadata->getField();

        return new DoctrineIdentityFieldDescriptor(
            $this->resolveOptions($fieldMetadata->getName(), $options),
            $this->resolveOptions($propertyMetadata->getName(), $options),
            $this->resolveOptions($fieldMetadata->getEntityName(), $options),
            $propertyMetadata->getTranslation(),
            $this->getDoctrineJoins($fieldMetadata->getJoins(), $options),
            $propertyMetadata->getVisibility(),
            $propertyMetadata->getSearchability(),
            $propertyMetadata->getType(),
            $propertyMetadata->isSortable()
        );
    }

    private function getCaseFieldDescriptor(
        CasePropertyMetadata $propertyMetadata,
        $options
    ): DoctrineCaseFieldDescriptor {
        $case1 = $propertyMetadata->getCase(0);
        $case2 = $propertyMetadata->getCase(1);

        return new DoctrineCaseFieldDescriptor(
            $this->resolveOptions($propertyMetadata->getName(), $options),
            new DoctrineDescriptor(
                $case1->getEntityName(),
                $case1->getName(),
                $this->getDoctrineJoins($case1->getJoins(), $options)
            ),
            new DoctrineDescriptor(
                $case2->getEntityName(),
                $case2->getName(),
                $this->getDoctrineJoins($case2->getJoins(), $options)
            ),
            $propertyMetadata->getTranslation(),
            $propertyMetadata->getVisibility(),
            $propertyMetadata->getSearchability(),
            $propertyMetadata->getType(),
            $propertyMetadata->isSortable()
        );
    }

    private function getGeneralFieldDescriptor(AbstractPropertyMetadata $generalMetadata, $options)
    {
        return new FieldDescriptor(
            $this->resolveOptions($generalMetadata->getName(), $options),
            $generalMetadata->getTranslation(),
            $generalMetadata->getVisibility(),
            $generalMetadata->getSearchability(),
            $generalMetadata->getType(),
            $generalMetadata->isSortable()
        );
    }

    /**
     * Resolves options for string.
     *
     * @param string $string
     * @param array $options
     *
     * @return string
     */
    private function resolveOptions($string, array $options)
    {
        foreach ($options as $key => $value) {
            $string = str_replace(':' . $key, $value, $string);
        }

        return $string;
    }

    private function getDoctrineJoins(array $joinMetadata, array $options)
    {
        $joins = [];
        foreach ($joinMetadata as $metadata) {
            $name = $this->resolveOptions($metadata->getEntityName(), $options);
            $joins[$name] = new DoctrineJoinDescriptor(
                $name,
                $this->resolveOptions($metadata->getEntityField(), $options),
                $this->resolveOptions($metadata->getCondition(), $options),
                $metadata->getMethod(),
                $metadata->getConditionMethod()
            );
        }

        return $joins;
    }

    private function getConfigCache($datagridKey)
    {
        return new ConfigCache(
            sprintf(
                '%s%s%s',
                $this->cachePath,
                DIRECTORY_SEPARATOR,
                $datagridKey
            ),
            $this->debug
        );
    }
}
