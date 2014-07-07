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

    public function __construct($data, $route, $parameters, $page, $limit, $total)
    {
        parent::__construct(
            new CollectionRepresentation($data),
            $route,
            $parameters,
            $page,
            $limit,
            ceil($total / $limit)
        );

        $this->total = $total;
    }
} 
