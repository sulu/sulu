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
        $name,
        $translation = null,
        $glue = ',',
        $disabled = false,
        $default = false,
        $type = '',
        $width = '',
        $minWidth = '',
        $sortable = true,
        $editable = false,
        $cssClass = '',
        $distinct = false
    ) {
        parent::__construct(
            $name,
            $translation,
            $disabled,
            $default,
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
