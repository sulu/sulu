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
 * This class defines the necessary information for a field to resolve it within a Doctrine Query for the ListBuilder.
 *
 * @ExclusionPolicy("all")
 */
class DoctrineCaseFieldDescriptor extends AbstractDoctrineFieldDescriptor
{
    /**
     * @var DoctrineDescriptor
     */
    private $case1;
    /**
     * @var DoctrineDescriptor
     */
    private $case2;

    public function __construct(
        $name,
        DoctrineDescriptor $case1,
        DoctrineDescriptor $case2,
        $translation = null,
        $disabled = false,
        $default = false,
        $type = '',
        $width = '',
        $minWidth = '',
        $sortable = true,
        $editable = false,
        $cssClass = ''
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

        $this->case1 = $case1;
        $this->case2 = $case2;
    }

    /**
     * {@inheritdoc}
     */
    public function getSelect()
    {
        return sprintf(
            '(CASE WHEN %s IS NOT NULL THEN %s ELSE %s END)',
            $this->case1->getSelect(),
            $this->case1->getSelect(),
            $this->case2->getSelect()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getSearch()
    {
        return sprintf(
            '%s LIKE :search OR (%s is NULL AND %s LIKE :search)',
            $this->case1->getSelect(),
            $this->case1->getSelect(),
            $this->case2->getSelect()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getJoins()
    {
        return array_merge($this->case1->getJoins(), $this->case2->getJoins());
    }
}
