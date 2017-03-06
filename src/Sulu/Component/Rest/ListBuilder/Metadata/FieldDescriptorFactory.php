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
use Sulu\Component\Rest\ListBuilder\FieldDescriptorInterface;
use Sulu\Component\Rest\ListBuilder\Metadata\Doctrine\FieldMetadata;
use Sulu\Component\Rest\ListBuilder\Metadata\Doctrine\JoinMetadata;
use Sulu\Component\Rest\ListBuilder\Metadata\Doctrine\PropertyMetadata as DoctrinePropertyMetadata;
use Sulu\Component\Rest\ListBuilder\Metadata\Doctrine\Type\CaseTypeMetadata;
use Sulu\Component\Rest\ListBuilder\Metadata\Doctrine\Type\ConcatenationTypeMetadata;
use Sulu\Component\Rest\ListBuilder\Metadata\Doctrine\Type\CountTypeMetadata;
use Sulu\Component\Rest\ListBuilder\Metadata\Doctrine\Type\GroupConcatTypeMetadata;
use Sulu\Component\Rest\ListBuilder\Metadata\Doctrine\Type\IdentityTypeMetadata;
use Sulu\Component\Rest\ListBuilder\Metadata\Doctrine\Type\SingleTypeMetadata;
use Sulu\Component\Rest\ListBuilder\Metadata\General\PropertyMetadata as GeneralPropertyMetadata;
use Symfony\Component\Config\ConfigCache;

/**
 * Creates legacy field-descriptors for metadata.
 */
class FieldDescriptorFactory implements FieldDescriptorFactoryInterface
{
    /**
     * @var ProviderInterface
     */
    private $metadataProvider;

    /**
     * @var string
     */
    private $cachePath;

    /**
     * @var bool
     */
    private $debug;

    public function __construct(ProviderInterface $metadataProvider, $cachePath, $debug)
    {
        $this->metadataProvider = $metadataProvider;
        $this->cachePath = $cachePath;
        $this->debug = $debug;
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldDescriptorForClass($className, $options = [], $type = null)
    {
        $cacheKey = md5(json_encode($options));

        $cache = new ConfigCache(
            sprintf(
                '%s/%s-%s-%s.php',
                $this->cachePath,
                str_replace('\\', '-', $className),
                str_replace('\\', '-', $type),
                $cacheKey
            ),
            $this->debug
        );

        if ($cache->isFresh()) {
            return require $cache->getPath();
        }

        $metadata = $this->metadataProvider->getMetadataForClass($className);

        $fieldDescriptors = [];
        /** @var PropertyMetadata $propertyMetadata */
        foreach ($metadata->propertyMetadata as $propertyMetadata) {
            /** @var GeneralPropertyMetadata $generalMetadata */
            $generalMetadata = $propertyMetadata->get(GeneralPropertyMetadata::class);

            if (!$propertyMetadata->has(DoctrinePropertyMetadata::class)) {
                $fieldDescriptor = $this->getGeneralFieldDescriptor($generalMetadata, $options);
                if (!$type || is_a($fieldDescriptor, $type)) {
                    $fieldDescriptors[$generalMetadata->getName()] = $fieldDescriptor;
                }

                continue;
            }

            /** @var DoctrinePropertyMetadata $doctrineMetadata */
            $doctrineMetadata = $propertyMetadata->get(DoctrinePropertyMetadata::class);

            $fieldDescriptor = null;
            if ($doctrineMetadata->getType() instanceof ConcatenationTypeMetadata) {
                $fieldDescriptor = $this->getConcatenationFieldDescriptor(
                    $generalMetadata,
                    $doctrineMetadata->getType(),
                    $options
                );
            } elseif ($doctrineMetadata->getType() instanceof GroupConcatTypeMetadata) {
                $fieldDescriptor = $this->getGroupConcatenationFieldDescriptor(
                    $generalMetadata,
                    $doctrineMetadata->getType(),
                    $options
                );
            } elseif ($doctrineMetadata->getType() instanceof IdentityTypeMetadata) {
                $fieldDescriptor = $this->getIdentityFieldDescriptor(
                    $generalMetadata,
                    $doctrineMetadata->getType(),
                    $options
                );
            } elseif ($doctrineMetadata->getType() instanceof SingleTypeMetadata) {
                $fieldDescriptor = $this->getFieldDescriptor(
                    $generalMetadata,
                    $doctrineMetadata->getType()->getField(),
                    $options
                );
            } elseif ($doctrineMetadata->getType() instanceof CountTypeMetadata) {
                $fieldDescriptor = $this->getCountFieldDescriptor(
                    $generalMetadata,
                    $doctrineMetadata->getType()->getField()
                );
            } elseif ($doctrineMetadata->getType() instanceof CaseTypeMetadata) {
                $fieldDescriptor = $this->getCaseFieldDescriptor(
                    $generalMetadata,
                    $doctrineMetadata->getType(),
                    $options
                );
            }

            if (null !== $fieldDescriptor
                && (!$type || is_a($fieldDescriptor, $type))
            ) {
                $fieldDescriptor->setMetadata($propertyMetadata);
                $fieldDescriptors[$generalMetadata->getName()] = $fieldDescriptor;
            }
        }

        $cache->write('<?php return unserialize(' . var_export(serialize($fieldDescriptors), true) . ');');

        return $fieldDescriptors;
    }

    /**
     * Returns field-descriptor for given general metadata.
     *
     * @param GeneralPropertyMetadata $generalMetadata
     * @param FieldMetadata $fieldMetadata
     * @param array $options
     *
     * @return DoctrineFieldDescriptor
     */
    protected function getFieldDescriptor(
        GeneralPropertyMetadata $generalMetadata,
        FieldMetadata $fieldMetadata,
        $options
    ) {
        $joins = [];
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
            $this->resolveOptions($generalMetadata->getName(), $options),
            $this->resolveOptions($fieldMetadata->getEntityName(), $options),
            $generalMetadata->getTranslation(),
            $joins,
            $this->isDisabled($generalMetadata),
            $this->isDefault($generalMetadata),
            $generalMetadata->getType(),
            $generalMetadata->getWidth(),
            $generalMetadata->getMinWidth(),
            $generalMetadata->isSortable(),
            $generalMetadata->isEditable(),
            $generalMetadata->getCssClass()
        );
    }

    /**
     * Returns count-field-descriptor for given general metadata.
     *
     * @param GeneralPropertyMetadata $generalMetadata
     * @param FieldMetadata $fieldMetadata
     *
     * @return DoctrineCountFieldDescriptor
     */
    protected function getCountFieldDescriptor(GeneralPropertyMetadata $generalMetadata, FieldMetadata $fieldMetadata)
    {
        $joins = [];
        foreach ($fieldMetadata->getJoins() as $joinMetadata) {
            $joins[$joinMetadata->getEntityName()] = new DoctrineJoinDescriptor(
                $joinMetadata->getEntityName(),
                $joinMetadata->getEntityField(),
                $joinMetadata->getCondition(),
                $joinMetadata->getMethod(),
                $joinMetadata->getConditionMethod()
            );
        }

        return new DoctrineCountFieldDescriptor(
            $fieldMetadata->getName(),
            $generalMetadata->getName(),
            $fieldMetadata->getEntityName(),
            $generalMetadata->getTranslation(),
            $joins,
            $this->isDisabled($generalMetadata),
            $this->isDefault($generalMetadata),
            $generalMetadata->getType(),
            $generalMetadata->getWidth(),
            $generalMetadata->getMinWidth(),
            $generalMetadata->isSortable(),
            $generalMetadata->isEditable(),
            $generalMetadata->getCssClass()
        );
    }

    /**
     * Returns concatenation field-descriptor for given general metadata.
     *
     * @param GeneralPropertyMetadata $generalMetadata
     * @param ConcatenationTypeMetadata $type
     * @param array $options
     *
     * @return DoctrineConcatenationFieldDescriptor
     */
    protected function getConcatenationFieldDescriptor(
        GeneralPropertyMetadata $generalMetadata,
        ConcatenationTypeMetadata $type,
        $options
    ) {
        return new DoctrineConcatenationFieldDescriptor(
            array_map(
                function (FieldMetadata $fieldMetadata) use ($generalMetadata, $options) {
                    return $this->getFieldDescriptor($generalMetadata, $fieldMetadata, $options);
                },
                $type->getFields()
            ),
            $this->resolveOptions($generalMetadata->getName(), $options),
            $generalMetadata->getTranslation(),
            $this->resolveOptions($type->getGlue(), $options),
            $this->isDisabled($generalMetadata),
            $this->isDefault($generalMetadata),
            $generalMetadata->getType(),
            $generalMetadata->getWidth(),
            $generalMetadata->getMinWidth(),
            $generalMetadata->isSortable(),
            $generalMetadata->isEditable(),
            $generalMetadata->getCssClass()
        );
    }

    /**
     * Returns concatenation field-descriptor for given general metadata.
     *
     * @param GeneralPropertyMetadata $generalMetadata
     * @param GroupConcatTypeMetadata $type
     * @param array $options
     *
     * @return DoctrineGroupConcatFieldDescriptor
     */
    protected function getGroupConcatenationFieldDescriptor(
        GeneralPropertyMetadata $generalMetadata,
        GroupConcatTypeMetadata $type,
        $options
    ) {
        return new DoctrineGroupConcatFieldDescriptor(
            $this->getFieldDescriptor($generalMetadata, $type->getField(), $options),
            $this->resolveOptions($generalMetadata->getName(), $options),
            $this->resolveOptions($generalMetadata->getTranslation(), $options),
            $this->resolveOptions($type->getGlue(), $options),
            $this->isDisabled($generalMetadata),
            $this->isDefault($generalMetadata),
            $generalMetadata->getType(),
            $generalMetadata->getWidth(),
            $generalMetadata->getMinWidth(),
            $generalMetadata->isSortable(),
            $generalMetadata->isEditable(),
            $generalMetadata->getCssClass(),
            $this->resolveOptions($type->getDistinct(), $options)
        );
    }

    /**
     * Returns identity field-descriptor for given general metadata.
     *
     * @param GeneralPropertyMetadata $generalMetadata
     * @param IdentityTypeMetadata $type
     * @param array $options
     *
     * @return DoctrineIdentityFieldDescriptor
     */
    private function getIdentityFieldDescriptor(
        GeneralPropertyMetadata $generalMetadata,
        IdentityTypeMetadata $type,
        $options
    ) {
        $fieldMetadata = $type->getField();

        return new DoctrineIdentityFieldDescriptor(
            $this->resolveOptions($fieldMetadata->getName(), $options),
            $this->resolveOptions($generalMetadata->getName(), $options),
            $this->resolveOptions($fieldMetadata->getEntityName(), $options),
            $generalMetadata->getTranslation(),
            $this->getDoctrineJoins($fieldMetadata->getJoins(), $options),
            $this->isDisabled($generalMetadata),
            $this->isDefault($generalMetadata),
            $generalMetadata->getType(),
            $generalMetadata->getWidth(),
            $generalMetadata->getMinWidth(),
            $generalMetadata->isSortable(),
            $generalMetadata->isEditable(),
            $generalMetadata->getCssClass()
        );
    }

    /**
     * Returns case field-descriptor for given general metadata.
     *
     * @param GeneralPropertyMetadata $generalMetadata
     * @param CaseTypeMetadata $type
     * @param array $options
     *
     * @return DoctrineCaseFieldDescriptor
     */
    private function getCaseFieldDescriptor(
        GeneralPropertyMetadata $generalMetadata,
        CaseTypeMetadata $type,
        $options
    ) {
        $case1 = $type->getCase(0);
        $case2 = $type->getCase(1);

        return new DoctrineCaseFieldDescriptor(
            $this->resolveOptions($generalMetadata->getName(), $options),
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
            $generalMetadata->getTranslation(),
            $this->isDisabled($generalMetadata),
            $this->isDefault($generalMetadata),
            $generalMetadata->getType(),
            $generalMetadata->getWidth(),
            $generalMetadata->getMinWidth(),
            $generalMetadata->isSortable(),
            $generalMetadata->isEditable(),
            $generalMetadata->getCssClass()
        );
    }

    /**
     * Returns general field-descriptor.
     *
     * @param $generalMetadata
     *
     * @return FieldDescriptorInterface
     */
    private function getGeneralFieldDescriptor(GeneralPropertyMetadata $generalMetadata, $options)
    {
        return new FieldDescriptor(
            $this->resolveOptions($generalMetadata->getName(), $options),
            $generalMetadata->getTranslation(),
            $this->isDisabled($generalMetadata),
            $this->isDefault($generalMetadata),
            $generalMetadata->getType(),
            $generalMetadata->getWidth(),
            $generalMetadata->getMinWidth(),
            $generalMetadata->isSortable(),
            $generalMetadata->isEditable(),
            $generalMetadata->getCssClass()
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

    /**
     * Creates doctrine-joins.
     *
     * @param JoinMetadata[] $joinMetadata
     * @param array $options
     *
     * @return DoctrineFieldDescriptor[]
     */
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

    /**
     * Determine disabled state.
     *
     * @param GeneralPropertyMetadata $generalMetadata
     *
     * @return bool
     */
    private function isDisabled(GeneralPropertyMetadata $generalMetadata)
    {
        return in_array(
            $generalMetadata->getDisplay(),
            [GeneralPropertyMetadata::DISPLAY_NEVER, GeneralPropertyMetadata::DISPLAY_NO]
        );
    }

    /**
     * Determine default state.
     *
     * @param GeneralPropertyMetadata $generalMetadata
     *
     * @return bool
     */
    private function isDefault(GeneralPropertyMetadata $generalMetadata)
    {
        return GeneralPropertyMetadata::DISPLAY_ALWAYS === $generalMetadata->getDisplay();
    }
}
