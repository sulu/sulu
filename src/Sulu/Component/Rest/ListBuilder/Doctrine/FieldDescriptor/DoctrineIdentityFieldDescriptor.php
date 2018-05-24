<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor;

use JMS\Serializer\Annotation\ExclusionPolicy;
use Sulu\Component\Rest\ListBuilder\Doctrine\EncodeAliasTrait;
use Sulu\Component\Rest\ListBuilder\FieldDescriptorInterface;

/**
 * This class defines the necessary information for a field to resolve it within a Doctrine Query for the ListBuilder.
 *
 * @ExclusionPolicy("all")
 */
class DoctrineIdentityFieldDescriptor extends AbstractDoctrineFieldDescriptor
{
    use EncodeAliasTrait;

    /**
     * The name of the field in the database.
     *
     * @var string
     */
    private $fieldName;

    /**
     * The name of the entity.
     *
     * @var string
     */
    private $entityName;

    /**
     * The joins, which have to be made to get to the result.
     *
     * @var DoctrineJoinDescriptor[]
     */
    private $joins;

    public function __construct(
        string $fieldName,
        string $name,
        string $entityName,
        string $translation = null,
        array $joins = [],
        string $visibility = FieldDescriptorInterface::VISIBILITY_YES,
        string $searchability = FieldDescriptorInterface::SEARCHABILITY_YES,
        string $type = '',
        string $width = '',
        string $minWidth = '',
        bool $sortable = true,
        bool $editable = false,
        string $cssClass = ''
    ) {
        parent::__construct(
            $name,
            $translation,
            $visibility,
            $searchability,
            $type,
            $width,
            $minWidth,
            $sortable,
            $editable,
            $cssClass
        );

        $this->fieldName = $fieldName;
        $this->entityName = $entityName;
        $this->joins = $joins;
    }

    /**
     * {@inheritdoc}
     */
    public function getSelect()
    {
        return sprintf('IDENTITY(%s.%s)', $this->encodeAlias($this->entityName), $this->getFieldName());
    }

    /**
     * Returns the alias for the field in the database.
     *
     * @return string
     */
    public function getFieldName()
    {
        return $this->fieldName;
    }

    /**
     * Returns the name of the entity this field is contained in.
     *
     * @return string
     */
    public function getEntityName()
    {
        return $this->entityName;
    }

    /**
     * {@inheritdoc}
     */
    public function getJoins()
    {
        return $this->joins;
    }

    /**
     * {@inheritdoc}
     */
    public function compare(FieldDescriptorInterface $other)
    {
        if (!$other instanceof self) {
            return false;
        }

        return $this->getEntityName() === $other->getEntityName()
            && $this->getFieldName() === $other->getFieldName();
    }
}
