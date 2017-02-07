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

use Sulu\Component\Rest\ListBuilder\FieldDescriptor;

/**
 * Extends field-descriptor with database information.
 */
abstract class AbstractDoctrineFieldDescriptor extends FieldDescriptor implements DoctrineFieldDescriptorInterface
{
    /**
     * {@inheritdoc}
     */
    abstract public function getSelect();

    /**
     * {@inheritdoc}
     */
    public function getSearch()
    {
        return sprintf('%s LIKE :search', $this->getSelect());
    }

    /**
     * {@inheritdoc}
     */
    public function getWhere()
    {
        return $this->getSelect();
    }

    /**
     * {@inheritdoc}
     */
    abstract public function getJoins();
}
