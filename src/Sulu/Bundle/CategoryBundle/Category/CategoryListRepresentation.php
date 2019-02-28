<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CategoryBundle\Category;

use JMS\Serializer\Annotation\ExclusionPolicy;
use Sulu\Component\Rest\ListBuilder\ListRepresentation;

/**
 * This class represents a list for the categories.
 *
 * @ExclusionPolicy("all")
 */
class CategoryListRepresentation extends ListRepresentation
{
    /**
     * {@inheritdoc}
     */
    public function __construct($data, $rel, $route, $parameters, $page, $limit, $total)
    {
        parent::__construct($data, $rel, $route, $parameters, $page, $limit, $total);
    }
}
