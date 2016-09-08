<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CategoryBundle\Category;

use Hateoas\Configuration\Annotation\Relation;
use Hateoas\Configuration\Annotation\Route;
use JMS\Serializer\Annotation\ExclusionPolicy;
use Sulu\Component\Rest\ListBuilder\ListRepresentation;

/**
 * This class represents a list for the categories.
 *
 * @ExclusionPolicy("all")
 * @Relation(
 *     "children",
 *     href = @Route(
 *         "get_category_children",
 *         parameters = "expr({ parentId: '{parentId}' } + object.getParameters())",
 *         absolute = "expr(object.isAbsolute())",
 *     )
 * )
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
