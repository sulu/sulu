<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART Webservices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TranslateBundle\Controller;

use FOS\RestBundle\Controller\FOSRestController;

/**
 * This is an abstract controller for ListResources accessed
 * by an REST-API. It just contains a few getters, which
 * deliver some values needed by the inheriting controller.
 *
 * These values are calculated from the request paramaters.
 *
 * @package Sulu\Bundle\TranslateBundle\Controller
 */
class ListRestController extends FOSRestController
{
    /**
     * Returns an array containing the desired sorting
     * @return array
     */
    public function getSorting()
    {
        $sortOrder = $this->getRequest()->get('sortOrder', 'asc');
        $sortBy = $this->getRequest()->get('sortBy', 'id');

        return array($sortBy => $sortOrder);
    }

    /**
     * Returns the maximum number of elements in a single response
     * @return integer
     */
    public function getLimit()
    {
        return $this->getRequest()->get('pageSize');
    }

    /**
     * Returns the calculated value for the starting position based
     * on the page and pagesize values
     * @return integer|null
     */
    public function getOffset()
    {
        $page = $this->getRequest()->get('page', 1);
        $pageSize = $this->getRequest()->get('pageSize');

        return ($pageSize != null) ? $pageSize * ($page - 1) : null;
    }

    /**
     * Returns an array with all the fields, which should be contained in the response.
     * If null is returned every field should be contained.
     * @return array|null
     */
    public function getFields()
    {
        $fields = $this->getRequest()->get('fields');
        return ($fields != null) ? explode(',', $fields) : null;
    }
}