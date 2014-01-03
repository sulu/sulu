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
class MediaMeta
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
     * @var integer
     */
    private $idLocalization;

    /**
     * @var \Sulu\Bundle\MediaBundle\Entity\Media
     */
    private $media;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set collection
     *
     * @param \Sulu\Bundle\MediaBundle\Entity\Media $media
     * @return MediaMeta
     */
    public function setMedia(\Sulu\Bundle\MediaBundle\Entity\Media $media)
    {
        $this->media = $media;

        return $this;
    }

    /**
     * @return \Sulu\Bundle\MediaBundle\Entity\Media
     */
    public function getMedia()
    {
        return $this->media;
    }

    /**
     * @param string $description
     * @return MediaMeta
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
     * @return MediaMeta
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

    /**
     * @param int $idLocalization
     * @return MediaMeta
     */
    public function setIdLocalization($idLocalization)
    {
        $this->idLocalization = $idLocalization;
        return $this;
    }

    /**
     * @return int
     */
    public function getIdLocalization()
    {
        return $this->idLocalization;
    }
}
