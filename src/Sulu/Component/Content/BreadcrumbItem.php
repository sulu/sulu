<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
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
     * @param int $depth
     * @param string $title
     * @param string $uuid
     */
    public function __construct(
        private $depth,
        private $uuid,
        private $title
    ) {
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
