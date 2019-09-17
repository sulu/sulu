<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SearchBundle\Rest;

use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\XmlAttribute;

class SearchResultRepresentation extends PaginatedRepresentation
{
    /**
     * @Expose
     * @XmlAttribute
     *
     * @var float
     */
    protected $time;

    public function __construct(
        $inline,
        $route,
        array $parameters,
        $page,
        $limit,
        $pages,
        $pageParameterName,
        $limitParameterName,
        $absolute,
        $total,
        $time
    ) {
        parent::__construct(
            $inline,
            $route,
            $parameters,
            $page,
            $limit,
            $pages,
            $pageParameterName,
            $limitParameterName,
            $absolute,
            $total
        );

        $this->time = $time;
    }
}
