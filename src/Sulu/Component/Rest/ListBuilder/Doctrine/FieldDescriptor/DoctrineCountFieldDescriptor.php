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
class DoctrineCountFieldDescriptor extends DoctrineFieldDescriptor
{
    /**
     * {@inheritdoc}
     */
    public function getSelect()
    {
        return 'COUNT(' . $this->getEntityName() . '.' . $this->getFieldName() . ')';
    }
}
