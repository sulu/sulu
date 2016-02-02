<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TranslateBundle\Entity;

use JMS\Serializer\Annotation\Exclude;
use Sulu\Bundle\CoreBundle\Entity\ApiEntity;

/**
 * Code.
 */
class Code extends ApiEntity
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $code;

    /**
     * @var bool
     */
    private $backend;

    /**
     * @var bool
     */
    private $frontend;

    /**
     * @var int
     */
    private $length;

    /**
     * @var \Sulu\Bundle\TranslateBundle\Entity\Package
     * @Exclude
     */
    private $package;

    /**
     * @var \Sulu\Bundle\TranslateBundle\Entity\Location
     */
    private $location;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $translations;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->translations = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set code.
     *
     * @param string $code
     *
     * @return Code
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code.
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set backend.
     *
     * @param bool $backend
     *
     * @return Code
     */
    public function setBackend($backend)
    {
        $this->backend = $backend;

        return $this;
    }

    /**
     * Get backend.
     *
     * @return bool
     */
    public function getBackend()
    {
        return $this->backend;
    }

    /**
     * Set frontend.
     *
     * @param bool $frontend
     *
     * @return Code
     */
    public function setFrontend($frontend)
    {
        $this->frontend = $frontend;

        return $this;
    }

    /**
     * Get frontend.
     *
     * @return bool
     */
    public function getFrontend()
    {
        return $this->frontend;
    }

    /**
     * Set length.
     *
     * @param int $length
     *
     * @return Code
     */
    public function setLength($length)
    {
        $this->length = $length;

        return $this;
    }

    /**
     * Get length.
     *
     * @return int
     */
    public function getLength()
    {
        return $this->length;
    }

    /**
     * Set package.
     *
     * @param \Sulu\Bundle\TranslateBundle\Entity\Package $package
     *
     * @return Code
     */
    public function setPackage(\Sulu\Bundle\TranslateBundle\Entity\Package $package = null)
    {
        $this->package = $package;

        return $this;
    }

    /**
     * Get package.
     *
     * @return \Sulu\Bundle\TranslateBundle\Entity\Package
     */
    public function getPackage()
    {
        return $this->package;
    }

    /**
     * Set location.
     *
     * @param \Sulu\Bundle\TranslateBundle\Entity\Location $location
     *
     * @return Code
     */
    public function setLocation(\Sulu\Bundle\TranslateBundle\Entity\Location $location = null)
    {
        $this->location = $location;

        return $this;
    }

    /**
     * Get location.
     *
     * @return \Sulu\Bundle\TranslateBundle\Entity\Location
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * Add translations.
     *
     * @param \Sulu\Bundle\TranslateBundle\Entity\Translation $translations
     *
     * @return Code
     */
    public function addTranslation(\Sulu\Bundle\TranslateBundle\Entity\Translation $translations)
    {
        $this->translations[] = $translations;

        return $this;
    }

    /**
     * Remove translations.
     *
     * @param \Sulu\Bundle\TranslateBundle\Entity\Translation $translations
     */
    public function removeTranslation(\Sulu\Bundle\TranslateBundle\Entity\Translation $translations)
    {
        $this->translations->removeElement($translations);
    }

    /**
     * Get translations.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTranslations()
    {
        return $this->translations;
    }
}
