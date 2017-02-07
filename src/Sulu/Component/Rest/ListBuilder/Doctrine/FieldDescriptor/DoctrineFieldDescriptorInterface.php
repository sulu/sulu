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

use Sulu\Component\Rest\ListBuilder\FieldDescriptorInterface;

/**
 * The interface for the different types of DoctrineFieldDescriptors.
 */
interface DoctrineFieldDescriptorInterface extends FieldDescriptorInterface
{
    /**
     * Returns the select statement for this field without the alias.
     *
     * @return string
     */
    public function getSelect();

    /**
     * Returns a simple select statement (for where statements as an example).
     *
     * @return string
     */
    public function getWhere();

    /**
     * Returns the where statement for search.
     *
     * @return string
     */
    public function getSearch();

    /**
     * Returns all the joins required for this field.
     *
     * @return DoctrineJoinDescriptor[]
     */
    public function getJoins();
}
