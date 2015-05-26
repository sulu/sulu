<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor;

use Sulu\Component\Rest\ListBuilder\AbstractFieldDescriptor;

/**
 * The abstract class for the different types of DoctrineFieldDescriptors.
 */
abstract class AbstractDoctrineFieldDescriptor extends AbstractFieldDescriptor
{
    /**
     * Returns the select statement for this field without the alias.
     *
     * @return string
     */
    abstract public function getSelect();

    /**
     * Returns all the joins required for this field.
     *
     * @return DoctrineJoinDescriptor[]
     */
    abstract public function getJoins();
}
