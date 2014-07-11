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

use Hateoas\Representation\CollectionRepresentation;
use Hateoas\Representation\PaginatedRepresentation;
use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\XmlAttribute;

/**
 * This class represents a list for our common rest services
 * @package Sulu\Component\Rest\ListBuilder
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
     * @param mixed $data The data which will be presented
     * @param string $rel The name of the relation inside of the _embedded field
     * @param string $route The name of the route, for generating the links
     * @param array $parameters The parameters to append to the route
     * @param integer $page The number of the current page
     * @param integer $limit The size of one page
     * @param null $total The total number of elements
     */
    public function __construct($data, $rel, $route, $parameters, $page, $limit, $total)
    {
        parent::__construct(
            new CollectionRepresentation($data, $rel),
            $route,
            $parameters,
            $page,
            $limit,
            ceil($total / $limit)
        );

        $this->total = $total;
    }
} 
