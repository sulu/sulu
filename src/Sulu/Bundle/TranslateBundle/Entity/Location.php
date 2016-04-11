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

/**
 * Location.
 */
class Location
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var \Sulu\Bundle\TranslateBundle\Entity\Package
     */
    private $package;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $codes;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->codes = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set name.
     *
     * @param string $name
     *
     * @return Location
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set package.
     *
     * @param \Sulu\Bundle\TranslateBundle\Entity\Package $package
     *
     * @return Location
     */
    public function setPackage(\Sulu\Bundle\TranslateBundle\Entity\Package $package)
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
     * Add codes.
     *
     * @param \Sulu\Bundle\TranslateBundle\Entity\Code $codes
     *
     * @return Location
     */
    public function addCode(\Sulu\Bundle\TranslateBundle\Entity\Code $codes)
    {
        $this->codes[] = $codes;

        return $this;
    }

    /**
     * Remove codes.
     *
     * @param \Sulu\Bundle\TranslateBundle\Entity\Code $codes
     */
    public function removeCode(\Sulu\Bundle\TranslateBundle\Entity\Code $codes)
    {
        $this->codes->removeElement($codes);
    }

    /**
     * Get codes.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCodes()
    {
        return $this->codes;
    }
}
