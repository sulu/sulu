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
    const SORTORDER_ASC = 'ASC';

    const SORTORDER_DESC = 'DESC';

    /**
     * Adds a field descriptor to the ListBuilder, which is then used to retrieve and return the list
     * @param $fieldDescriptor
     * @return ListBuilderInterface
     */
    public function add($fieldDescriptor);

    /**
     * Defines the field by which the table is sorted
     * @param $fieldDescriptor
     * @param $order
     * @return ListBuilderInterface
     */
    public function sort($fieldDescriptor, $order = self::SORTORDER_ASC);

    /**
     * Defines how many items should be returned
     * @param $limit
     * @return ListBuilderInterface
     */
    public function limit($limit);

    /**
     * Sets the current page for the builder
     * @param $page
     * @return ListBuilderInterface
     */
    public function setCurrentPage($page);

    /**
     * The number of total elements for this list
     * @return integer
     */
    public function count();

    /**
     * Returns the objects for the built query
     * @return mixed
     */
    public function execute();
}
