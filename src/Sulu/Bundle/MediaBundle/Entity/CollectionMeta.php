<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Entity;

/**
 * CollectionMeta.
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
     * @var string
     */
    private $locale;

    /**
     * @var int
     */
    private $id;

    /**
     * @var \Sulu\Bundle\MediaBundle\Entity\CollectionInterface
     */
    private $collection;

    /**
     * Set title.
     *
     * @param string $title
     *
     * @return CollectionMeta
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set description.
     *
     * @param string $description
     *
     * @return CollectionMeta
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set locale.
     *
     * @param string $locale
     *
     * @return CollectionMeta
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * Get locale.
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set collection.
     *
     * @param \Sulu\Bundle\MediaBundle\Entity\CollectionInterface $collection
     *
     * @return CollectionMeta
     */
    public function setCollection(\Sulu\Bundle\MediaBundle\Entity\CollectionInterface $collection)
    {
        $this->collection = $collection;

        return $this;
    }

    /**
     * Get collection.
     *
     * @return \Sulu\Bundle\MediaBundle\Entity\CollectionInterface
     */
    public function getCollection()
    {
        return $this->collection;
    }
}
