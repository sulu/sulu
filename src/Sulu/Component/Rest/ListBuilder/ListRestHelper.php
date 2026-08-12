<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\ListBuilder;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Webmozart\Assert\Assert;

/**
 * This is an service helper for ListResources accessed
 * by an REST-API. It contains a few getters, which
 * deliver some values needed by the inheriting controller.
 * These values are calculated from the request paramaters.
 */
class ListRestHelper implements ListRestHelperInterface
{
    /**
     * The current request object.
     *
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * The constructor takes the request stack as an argument, which
     * is injected by the service container.
     */
    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * Returns the current Request.
     *
     * @return Request
     */
    protected function getRequest()
    {
        $request = $this->requestStack->getCurrentRequest();
        Assert::notNull($request, 'There is currently no HTTP going on. You can\'t use this class in console mode.');

        return $request;
    }

    public function getIds()
    {
        $idsString = $this->getRequest()->query->get('ids');

        return (null !== $idsString) ? \array_filter(\explode(',', $idsString)) : null;
    }

    public function getExcludedIds()
    {
        $excludedIdsString = $this->getRequest()->query->get('excludedIds');

        return (null !== $excludedIdsString) ? \array_filter(\explode(',', $excludedIdsString)) : [];
    }

    public function getSortColumn()
    {
        $sortColumn = $this->getRequest()->query->get('sortBy', null);
        if (null === $sortColumn || !\is_string($sortColumn)) {
            return null;
        }

        // Only allow sort columns like id, otherEntity.id and no function calls (empty strings are also invalid)
        if (!\preg_match('#^\w+(\.\w+)*$#', $sortColumn)) {
            return null;
        }

        return $sortColumn;
    }

    /**
     * Returns desired sort order.
     *
     * @return string
     */
    public function getSortOrder()
    {
        return $this->getRequest()->query->get('sortOrder', 'asc');
    }

    /**
     * Returns the maximum number of elements in a single response.
     *
     * @return int|null
     */
    public function getLimit()
    {
        $default = 10;
        if ('csv' === $this->getRequest()->getRequestFormat()) {
            $default = null;
        }

        // set default limit to count of ids if result is restricted to specific ids
        $ids = $this->getIds();
        if (null != $ids) {
            $default = \count($ids);
        }

        return $this->getRequest()->query->get('limit', $default);
    }

    /**
     * Returns the calculated value for the starting position based
     * on the page and limit values.
     *
     * @return int|null
     */
    public function getOffset()
    {
        $page = $this->getRequest()->query->get('page', 1);
        $limit = $this->getLimit();

        return (null != $limit) ? $limit * ($page - 1) : null;
    }

    /**
     * returns the current page.
     */
    public function getPage()
    {
        return $this->getRequest()->query->get('page', 1);
    }

    /**
     * Returns an array with all the fields, which should be contained in the response.
     * If null is returned every field should be contained.
     *
     * @return array|null
     */
    public function getFields()
    {
        $fields = $this->getRequest()->query->get('fields');

        return (null != $fields) ? \explode(',', $fields) : null;
    }

    /**
     * Returns the pattern of the search.
     */
    public function getSearchPattern()
    {
        return $this->getRequest()->query->get('search');
    }

    /**
     * Returns an array with all the fields the search pattern should be executed on.
     *
     * @return array|null
     */
    public function getSearchFields()
    {
        $searchFields = $this->getRequest()->query->get('searchFields');

        return (null != $searchFields) ? \explode(',', $searchFields) : [];
    }

    /**
     * @return array
     */
    public function getFilter()
    {
        return $this->getRequest()->query->all('filter');
    }
}
