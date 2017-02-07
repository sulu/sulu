<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Api;

use Hateoas\Configuration\Annotation\Embedded;
use Hateoas\Configuration\Annotation\Relation;
use Hateoas\Configuration\Annotation\Route;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;

/**
 * The Collection Root RestObject is the api entity for the CollectionController.
 *
 * @ExclusionPolicy("all")
 * @Relation(
 *      "children",
 *      href = @Route(
 *          "get_collections",
 *          parameters = { "include-root" = "true" }
 *      )
 * )
 * @Relation(
 *     name = "collections",
 *     embedded = @Embedded(
 *         "expr(object.getCollections())",
 *         xmlElementName = "collections"
 *     )
 * )
 */
class RootCollection
{
    /**
     * @var string
     *
     * @Expose
     */
    private $id = 'root';

    /**
     * @var string
     *
     * @Expose
     */
    private $title = 'smart-content.media.all-collections';

    /**
     * @var bool
     *
     * @Expose
     */
    private $hasSub = true;

    /**
     * @var Collection[]
     */
    private $collections;

    public function __construct(array $collections)
    {
        $this->collections = $collections;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return bool
     */
    public function hasSub()
    {
        return $this->hasSub;
    }

    /**
     * @return Collection[]
     */
    public function getCollections()
    {
        return $this->collections;
    }
}
