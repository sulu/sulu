<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Repository;

class Content
{
    /**
     * @var string
     */
    private $uuid;

    /**
     * @var string
     */
    private $path;

    /**
     * @var array
     */
    private $data;

    public function __construct($uuid, $path, array $data)
    {
        $this->uuid = $uuid;
        $this->path = $path;
        $this->data = $data;
    }

    /**
     * @return string
     */
    public function getUuid()
    {
        return $this->uuid;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }
}
