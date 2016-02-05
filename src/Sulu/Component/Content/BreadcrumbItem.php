<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content;

/**
 * Item for breadcrumb.
 *
 * @deprecated This class was only used for an array generation, which is now done in a serializer subscriber
 */
class BreadcrumbItem implements BreadcrumbItemInterface
{
    /**
     * depth of node.
     *
     * @var int
     */
    private $depth;

    /**
     * title of node.
     *
     * @var string
     */
    private $title;

    /**
     * uuid of node.
     *
     * @var string
     */
    private $uuid;

    public function __construct($depth, $uuid, $title)
    {
        $this->depth = $depth;
        $this->title = $title;
        $this->uuid = $uuid;
    }

    /**
     * returns depth of node.
     *
     * @return int
     */
    public function getDepth()
    {
        return $this->depth;
    }

    /**
     * returns title of node.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * returns uuid of node.
     *
     * @return string
     */
    public function getUuid()
    {
        return $this->uuid;
    }

    /**
     * returns array representation.
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'uuid' => $this->getUuid(),
            'depth' => $this->getDepth(),
            'title' => $this->getTitle(),
        ];
    }
}
