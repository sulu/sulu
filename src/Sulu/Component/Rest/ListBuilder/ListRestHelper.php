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

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * This is an service helper for ListResources accessed
 * by an REST-API. It contains a few getters, which
 * deliver some values needed by the inheriting controller.
 * These values are calculated from the request paramaters.
 */
class ListRestHelper implements ListRestHelperInterface
{
    const PARAMETER_FIELDS = 'fields';
    const PARAMETER_PAGE = 'page';
    const PARAMETER_SEARCH = 'search';
    const PARAMETER_SEARCH_FIELDS = 'searchFields';
    const PARAMETER_SORT_BY = 'sortBy';
    const PARAMETER_SORT_ORDER = 'sortOrder';

    /**
     * The current request object.
     *
     * @var Request
     */
    protected $requestStack;

    /**
     * The constructor takes the request stack as an argument, which
     * is injected by the service container.
     *
     * @param RequestStack $requestStack
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
        return $this->requestStack->getCurrentRequest();
    }

    /**
     * Returns the desired sort column.
     *
     * @return string
     */
    public function getSortColumn()
    {
        return $this->getRequest()->get(self::PARAMETER_SORT_BY, null);
    }

    /**
     * Returns desired sort order.
     *
     * @return string
     */
    public function getSortOrder()
    {
        return $this->getRequest()->get(self::PARAMETER_SORT_ORDER, 'asc');
    }

    /**
     * Returns the maximum number of elements in a single response.
     *
     * @return int
     */
    public function getLimit()
    {
        $default = 10;
        if ($this->getRequest()->getRequestFormat() === 'csv') {
            $default = null;
        }

        return $this->getRequest()->get('limit', $default);
    }

    /**
     * Returns the calculated value for the starting position based
     * on the page and limit values.
     *
     * @return int|null
     */
    public function getOffset()
    {
        $page = $this->getRequest()->get(self::PARAMETER_PAGE, 1);
        $limit = $this->getLimit();

        return ($limit != null) ? $limit * ($page - 1) : null;
    }

    /**
     * returns the current page.
     *
     * @return mixed
     */
    public function getPage()
    {
        return $this->getRequest()->get(self::PARAMETER_PAGE, 1);
    }

    /**
     * Returns an array with all the fields, which should be contained in the response.
     * If null is returned every field should be contained.
     *
     * @return array|null
     */
    public function getFields()
    {
        $fields = $this->getRequest()->get(self::PARAMETER_FIELDS);

        return ($fields != null) ? explode(',', $fields) : null;
    }

    /**
     * Returns the pattern of the search.
     *
     * @return mixed
     */
    public function getSearchPattern()
    {
        return $this->getRequest()->get(self::PARAMETER_SEARCH);
    }

    /**
     * Returns an array with all the fields the search pattern should be executed on.
     *
     * @return array|null
     */
    public function getSearchFields()
    {
        $searchFields = $this->getRequest()->get(self::PARAMETER_SEARCH_FIELDS);

        return ($searchFields != null) ? explode(',', $searchFields) : [];
    }

    /**
     * Returns an array of available filters.
     *
     * @return string[]
     */
    public function getFilters()
    {
        $filters = [];

        $parameters = $this->getRequest()->query->all();

        foreach ($parameters as $name => $value) {
            // ignore empty values and none filter parameters
            if ($value && !in_array($name, $this->getNoneFilterParameters())) {
                $filters[$name] = $value;
            }
        }

        return $filters;
    }

    /**
     * Returns an array of none available filter parameters.
     *
     * @return array
     */
    protected function getNoneFilterParameters()
    {
        return [
            self::PARAMETER_FIELDS,
            self::PARAMETER_PAGE,
            self::PARAMETER_SEARCH,
            self::PARAMETER_SEARCH_FIELDS,
            self::PARAMETER_SORT_BY,
            self::PARAMETER_SORT_ORDER,
        ];
    }
}
