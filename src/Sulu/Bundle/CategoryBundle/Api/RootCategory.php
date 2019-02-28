<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CategoryBundle\Api;

use Hateoas\Configuration\Annotation\Embedded;
use Hateoas\Configuration\Annotation\Relation;

/**
 * @Relation(
 *     name = "collections",
 *     embedded = @Embedded(
 *         "expr(object.getCategories())",
 *         xmlElementName = "categories"
 *     )
 * )
 */
class RootCategory
{
    /**
     * @var int
     */
    private $id = 'root';

    /**
     * @var string
     */
    private $title;

    /**
     * @var Category[]
     */
    private $categories;

    public function __construct(string $title, array $categories = [])
    {
        $this->title = $title;
        $this->categories = $categories;
    }

    public function getCategories()
    {
        return $this->categories;
    }
}
