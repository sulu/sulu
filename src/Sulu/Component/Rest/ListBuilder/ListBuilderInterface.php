<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\ListBuilder;

use Sulu\Bundle\CoreBundle\Entity\ApiEntity;

/**
 * This interface defines the the ListBuilder functionality, for the creation of REST list responses
 * @package Sulu\Component\Rest\ListBuilder
 */
interface ListBuilderInterface
{
    /**
     * Adds a field descriptor to the ListBuilder, which is then used to retrieve and return the list
     * @param $fieldDescriptor
     * @return ListBuilderInterface
     */
    public function add($fieldDescriptor);

    /**
     * Defines the field by which the table is sorted
     * @param $fieldDescriptor
     * @return ListBuilderInterface
     */
    public function sortBy($fieldDescriptor);

    /**
     * Defines the order of the sorting
     * @param $order
     * @return ListBuilderInterface
     */
    public function sortOrder($order);

    /**
     * Returns the objects for the built query
     * @return mixed
     */
    public function execute();
} 
