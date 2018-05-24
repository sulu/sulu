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
use Sulu\Component\Rest\ListBuilder\FieldDescriptorInterface;

/**
 * This field descriptor can be used to group-concatenate a joined (1:n) field descriptor.
 *
 * @ExclusionPolicy("all")
 */
class DoctrineGroupConcatFieldDescriptor extends AbstractDoctrineFieldDescriptor
{
    /**
     * The field descriptor which will be group concatenated.
     *
     * @var DoctrineFieldDescriptorInterface
     */
    private $fieldDescriptor;

    /**
     * @var string
     */
    private $glue;

    /**
     * @var bool
     */
    private $distinct;

    public function __construct(
        DoctrineFieldDescriptorInterface $fieldDescriptor,
        string $name,
        string $translation = null,
        string $glue = ',',
        string $visibility = FieldDescriptorInterface::VISIBILITY_YES,
        string $searchability = FieldDescriptorInterface::SEARCHABILITY_YES,
        string $type = '',
        string $width = '',
        string $minWidth = '',
        bool $sortable = true,
        bool $editable = false,
        string $cssClass = '',
        bool $distinct = false
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

        $this->fieldDescriptor = $fieldDescriptor;
        $this->glue = $glue;
        $this->distinct = $distinct;
    }

    /**
     * {@inheritdoc}
     */
    public function getSelect()
    {
        return 'GROUP_CONCAT(' . ($this->distinct ? 'DISTINCT ' : '') . $this->fieldDescriptor->getSelect() . ' SEPARATOR \'' . $this->glue . '\')';
    }

    /**
     * {@inheritdoc}
     */
    public function getWhere()
    {
        return $this->fieldDescriptor->getSelect();
    }

    /**
     * {@inheritdoc}
     */
    public function getJoins()
    {
        return $this->fieldDescriptor->getJoins();
    }
}
