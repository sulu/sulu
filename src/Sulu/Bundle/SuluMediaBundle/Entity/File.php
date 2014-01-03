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
use Sulu\Bundle\CoreBundle\Entity\ApiEntity;

/**
 * Contact
 */
class File extends ApiEntity
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $path;

    /**
     * @var integer
     */
    private $size;

    /**
     * @var integer
     */
    private $version;

    /**
     * @var \Sulu\Component\Security\UserInterface
     */
    private $changer;

    /**
     * @var \Sulu\Component\Security\UserInterface
     */
    private $creator;

    /**
     * @var \DateTime
     */
    private $created;

    /**
     * @var \DateTime
     */
    private $changed;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $contentLanguages;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $publishLanguages;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->contentLanguages = new \Doctrine\Common\Collections\ArrayCollection();
        $this->publishLanguages = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * @param \DateTime $changed
     * @return File
     */
    public function setChanged($changed)
    {
        $this->changed = $changed;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getChanged()
    {
        return $this->changed;
    }

    /**
     * @param \Sulu\Component\Security\UserInterface $changer
     * @return File
     */
    public function setChanger($changer)
    {
        $this->changer = $changer;
        return $this;
    }

    /**
     * @return \Sulu\Component\Security\UserInterface
     */
    public function getChanger()
    {
        return $this->changer;
    }

    /**
     * @param \DateTime $created
     * @return File
     */
    public function setCreated($created)
    {
        $this->created = $created;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @param \Sulu\Component\Security\UserInterface $creator
     * @return File
     */
    public function setCreator($creator)
    {
        $this->creator = $creator;
        return $this;
    }

    /**
     * @return \Sulu\Component\Security\UserInterface
     */
    public function getCreator()
    {
        return $this->creator;
    }

    /**
     * @param int $id
     * @return File;
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $name
     * @return File
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param \Doctrine\Common\Collections\Collection $contentLanguages
     * @return File
     */
    public function setContentLanguages($contentLanguages)
    {
        $this->contentLanguages = $contentLanguages;
        return $this;
    }

    /**
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getContentLanguages()
    {
        return $this->contentLanguages;
    }

    /**
     * @param string $path
     * @return File
     */
    public function setPath($path)
    {
        $this->path = $path;
        return $this;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param \Doctrine\Common\Collections\Collection $publishLanguages
     * @return File
     */
    public function setPublishLanguages($publishLanguages)
    {
        $this->publishLanguages = $publishLanguages;
        return $this;
    }

    /**
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPublishLanguages()
    {
        return $this->publishLanguages;
    }

    /**
     * @param int $size
     * @return File
     */
    public function setSize($size)
    {
        $this->size = $size;
        return $this;
    }

    /**
     * @return int
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * @param int $version
     * @return File
     */
    public function setVersion($version)
    {
        $this->version = $version;
        return $this;
    }

    /**
     * @return int
     */
    public function getVersion()
    {
        return $this->version;
    }
}