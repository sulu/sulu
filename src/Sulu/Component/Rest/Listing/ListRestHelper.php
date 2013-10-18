<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\Listing;

use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\HttpFoundation\Request;

/**
 * This is an service helper for ListResources accessed
 * by an REST-API. It contains a few getters, which
 * deliver some values needed by the inheriting controller.
 * These values are calculated from the request paramaters.
 *
 * For lists it allocates a Repository
 *
 * @package Sulu\Bundle\TranslateBundle\Controller
 */
class ListRestHelper
{
    /**
     * The current request object
     * @var Request
     */
    protected $request;

    /**
     * @var ObjectManager
     */
    protected $em;

    /**
     * The constructor takes the request as an argument, which
     * is injected by the service container
     * @param Request $request
     * @param ObjectManager $em
     */
    public function __construct(Request $request, ObjectManager $em)
    {
        $this->request = $request;
        $this->em = $em;
    }

    /**
     * Create a ListRepository for given EntityName
     * @param string $entityName
     * @return ListRepository
     */
    public function getRepository($entityName)
    {
        return new ListRepository($this->em, $this->em->getClassMetadata($entityName), $this);
    }

    /**
     * Create a ListRepository for given EntityName and find Entities for list
     * @param string $entityName
     * @param array $where
     * @return \Doctrine\ORM\Query
     */
    public function find($entityName, $where = array())
    {
        return $this->getRepository($entityName)->find($where);
    }

    /**
     * Returns the current Request
     * @return Request
     */
    protected function getRequest()
    {
        return $this->request;
    }

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

    /**
     * Returns the pattern of the search
     * @return mixed
     */
    public function getSearchPattern()
    {
        return $this->getRequest()->get('search');
    }

    /**
     * Returns an array with all the fields the search pattern should be executed on
     * @return array|null
     */
    public function getSearchFields()
    {
        $searchFields = $this->getRequest()->get('searchFields');

        return ($searchFields != null) ? explode(',', $searchFields) : array();
    }
}
