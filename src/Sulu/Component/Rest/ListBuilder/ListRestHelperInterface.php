<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\ListBuilder;

/**
 * Interface for retrieving information for lists from Request.
 */
interface ListRestHelperInterface
{
    /**
     * Returns the desired sort column.
     *
     * @return string
     */
    public function getSortColumn();

    /**
     * Returns desired sort order.
     *
     * @return string
     */
    public function getSortOrder();

    /**
     * Returns the maximum number of elements in a single response.
     *
     * @return int
     */
    public function getLimit();

    /**
     * Returns the calculated value for the starting position based
     * on the page and limit values.
     *
     * @return int|null
     */
    public function getOffset();

    /**
     * returns the current page.
     *
     * @return mixed
     */
    public function getPage();

    /**
     * Returns an array with all the fields, which should be contained in the response.
     * If null is returned every field should be contained.
     *
     * @return array|null
     */
    public function getFields();

    /**
     * Returns the pattern of the search.
     *
     * @return mixed
     */
    public function getSearchPattern();

    /**
     * Returns an array with all the fields the search pattern should be executed on.
     *
     * @return array|null
     */
    public function getSearchFields();
}
