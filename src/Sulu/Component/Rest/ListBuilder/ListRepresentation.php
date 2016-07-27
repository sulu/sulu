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

use Hateoas\Configuration\Annotation\Relation;
use Hateoas\Configuration\Annotation\Route;
use Hateoas\Representation\CollectionRepresentation;
use Hateoas\Representation\PaginatedRepresentation;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\XmlAttribute;

/**
 * This class represents a list for our common rest services.
 *
 * @ExclusionPolicy("all")
 * @Relation(
 *      "filter",
 *      href = @Route(
 *          "expr(object.getRoute())",
 *          parameters = "expr({ fields: '{fieldsList}' } + object.getParameters())",
 *          absolute = "expr(object.isAbsolute())",
 *      )
 * )
 * @Relation(
 *      "find",
 *      href = @Route(
 *          "expr(object.getRoute())",
 *          parameters = "expr({ search: '{searchString}', searchFields: '{searchFields}', page: 1 } + object.getParameters())",
 *          absolute = "expr(object.isAbsolute())",
 *      )
 * )
 * @Relation(
 *      "pagination",
 *      href = @Route(
 *          "expr(object.getRoute())",
 *          parameters = "expr({ page: '{page}', limit: '{limit}'} + object.getParameters())",
 *          absolute = "expr(object.isAbsolute())",
 *      )
 * )
 * @Relation(
 *      "sortable",
 *      href = @Route(
 *          "expr(object.getRoute())",
 *          parameters = "expr({ sortBy: '{sortBy}', sortOrder: '{sortOrder}' } + object.getParameters())",
 *          absolute = "expr(object.isAbsolute())",
 *      )
 * )
 */
class ListRepresentation extends PaginatedRepresentation
{
    /**
     * @Expose
     * @XmlAttribute
     *
     * @var int
     */
    protected $total;

    /**
     * @var mixed[]
     */
    protected $data;

    /**
     * @var string
     */
    protected $rel;

    /**
     * @param mixed $data The data which will be presented
     * @param string $rel The name of the relation inside of the _embedded field
     * @param string $route The name of the route, for generating the links
     * @param array $parameters The parameters to append to the route
     * @param int $page The number of the current page
     * @param int $limit The size of one page
     * @param int $total The total number of elements
     */
    public function __construct($data, $rel, $route, $parameters, $page, $limit, $total)
    {
        parent::__construct(
            new CollectionRepresentation($data, $rel),
            $route,
            $parameters,
            $page,
            $limit,
            ($limit ? ceil($total / $limit) : 1)
        );

        $this->total = $total;
        $this->data = $data;
        $this->rel = $rel;
    }

    /**
     * Returns total number of elements.
     *
     * @return int
     */
    public function getTotal()
    {
        return $this->total;
    }

    /**
     * Returns data.
     *
     * @return \mixed[]
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Returns relation name.
     *
     * @return string
     */
    public function getRel()
    {
        return $this->rel;
    }
}
