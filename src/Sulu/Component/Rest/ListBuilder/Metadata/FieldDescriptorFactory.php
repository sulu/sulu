<?php

namespace Sulu\Component\Rest\ListBuilder\Metadata;

use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineConcatenationFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineJoinDescriptor;
use Sulu\Component\Rest\ListBuilder\Metadata\Doctrine\FieldMetadata;
use Sulu\Component\Rest\ListBuilder\Metadata\Doctrine\PropertyMetadata as DoctrinePropertyMetadata;
use Sulu\Component\Rest\ListBuilder\Metadata\Doctrine\Type\ConcatenationType;
use Sulu\Component\Rest\ListBuilder\Metadata\General\PropertyMetadata as GeneralPropertyMetadata;

class FieldDescriptorFactory implements FieldDescriptorFactoryInterface
{
    /**
     * @var ProviderInterface
     */
    private $metadataProvider;

    public function __construct(ProviderInterface $metadataProvider)
    {
        $this->metadataProvider = $metadataProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldDescriptorForClass($className)
    {
        $metadata = $this->metadataProvider->getMetadataForClass($className);

        $fieldDescriptors = [];
        /** @var PropertyMetadata $propertyMetadata */
        foreach ($metadata->propertyMetadata as $propertyMetadata) {
            if (!$propertyMetadata->has(DoctrinePropertyMetadata::class)
                || !$propertyMetadata->has(GeneralPropertyMetadata::class)
            ) {
                continue;
            }

            /** @var DoctrinePropertyMetadata $doctrineMetadata */
            $doctrineMetadata = $propertyMetadata->get(DoctrinePropertyMetadata::class);
            /** @var GeneralPropertyMetadata $generalMetadata */
            $generalMetadata = $propertyMetadata->get(GeneralPropertyMetadata::class);

            switch (get_class($doctrineMetadata->getType())) {
                case ConcatenationType::class:
                    $fieldDescriptor = $this->getConcatenationFieldDescriptor(
                        $generalMetadata,
                        $doctrineMetadata->getType()
                    );
                    break;
                default:
                    $fieldDescriptor = $this->getFieldDescriptor(
                        $generalMetadata,
                        $doctrineMetadata->getType()->getField()
                    );
                    break;
            }

            $fieldDescriptors[$generalMetadata->getName()] = $fieldDescriptor;
        }

        return $fieldDescriptors;
    }

    protected function getFieldDescriptor(GeneralPropertyMetadata $generalMetadata, FieldMetadata $fieldMetadata)
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

        return new DoctrineFieldDescriptor(
            $fieldMetadata->getName(),
            $generalMetadata->getName(),
            $fieldMetadata->getEntityName(),
            $generalMetadata->getTranslation(),
            $joins,
            $generalMetadata->isDisabled(),
            $generalMetadata->isDefault(),
            $generalMetadata->getType(),
            $generalMetadata->getWith(),
            $generalMetadata->getMinWidth(),
            $generalMetadata->isSortable(),
            $generalMetadata->isEditable(),
            $generalMetadata->getCssClass()
        );
    }

    protected function getConcatenationFieldDescriptor(
        GeneralPropertyMetadata $generalMetadata,
        ConcatenationType $type
    ) {
        return new DoctrineConcatenationFieldDescriptor(
            array_map(
                function (FieldMetadata $fieldMetadata) use ($generalMetadata) {
                    return $this->getFieldDescriptor($generalMetadata, $fieldMetadata);
                },
                $type->getFields()
            ),
            $generalMetadata->getName(),
            $generalMetadata->getTranslation(),
            $type->getGlue(),
            $generalMetadata->isDisabled(),
            $generalMetadata->isDefault(),
            $generalMetadata->getType(),
            $generalMetadata->getWith(),
            $generalMetadata->getMinWidth(),
            $generalMetadata->isSortable(),
            $generalMetadata->isEditable(),
            $generalMetadata->getCssClass()
        );
    }
}
