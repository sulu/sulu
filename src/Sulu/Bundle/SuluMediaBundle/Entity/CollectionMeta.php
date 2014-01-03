<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CollectionMeta
 */
class CollectionMeta
{
    /**
     * @var string
     */
    private $title;

    /**
     * @var string
     */
    private $description;

    /**
     * @var integer
     */
    private $id;

    /**
     * @var \Sulu\Bundle\MediaBundle\Entity\Collection
     */
    private $collection;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param \Sulu\Bundle\MediaBundle\Entity\Collection $collection
     * @return CollectionMeta
     */
    public function setCollection(\Sulu\Bundle\MediaBundle\Entity\Collection $collection)
    {
        $this->collection = $collection;

        return $this;
    }

    /**
     * @return \Sulu\Bundle\MediaBundle\Entity\Collection
     */
    public function getCollection()
    {
        return $this->collection;
    }

    /**
     * @param string $description
     * @return CollectionMeta
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $title
     * @return CollectionMeta
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }
}
