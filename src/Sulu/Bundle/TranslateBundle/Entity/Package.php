<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART Webservices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TranslateBundle\Entity;

use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;

/**
 * Class Package
 * @package Sulu\Bundle\TranslateBundle\Entity
 *
 * @ExclusionPolicy("all")
 */
class Package
{
    /**
     * @var integer
     * @Expose
     */
    private $id;

    /**
     * @var string
     * @Expose
     */
    protected $name;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $codes;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $locations;

    /**
     * @var \Doctrine\Common\Collections\Collection
     * @Expose
     */
    private $catalogues;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->codes = new \Doctrine\Common\Collections\ArrayCollection();
    }

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
     * Set name
     *
     * @param string $name
     * @return Package
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Add codes
     *
     * @param \Sulu\Bundle\TranslateBundle\Entity\Code $codes
     * @return Package
     */
    public function addCode(\Sulu\Bundle\TranslateBundle\Entity\Code $codes)
    {
        $this->codes[] = $codes;

        return $this;
    }

    /**
     * Remove codes
     *
     * @param \Sulu\Bundle\TranslateBundle\Entity\Code $codes
     */
    public function removeCode(\Sulu\Bundle\TranslateBundle\Entity\Code $codes)
    {
        $this->codes->removeElement($codes);
    }

    /**
     * Get codes
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCodes()
    {
        return $this->codes;
    }

    /**
     * Add locations
     *
     * @param \Sulu\Bundle\TranslateBundle\Entity\Location $locations
     * @return Package
     */
    public function addLocation(\Sulu\Bundle\TranslateBundle\Entity\Location $locations)
    {
        $this->locations[] = $locations;

        return $this;
    }

    /**
     * Remove locations
     *
     * @param \Sulu\Bundle\TranslateBundle\Entity\Location $locations
     */
    public function removeLocation(\Sulu\Bundle\TranslateBundle\Entity\Location $locations)
    {
        $this->locations->removeElement($locations);
    }

    /**
     * Get locations
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getLocations()
    {
        return $this->locations;
    }

    /**
     * Add catalogues
     *
     * @param \Sulu\Bundle\TranslateBundle\Entity\Catalogue $catalogues
     * @return Package
     */
    public function addCatalogue(\Sulu\Bundle\TranslateBundle\Entity\Catalogue $catalogues)
    {
        $this->catalogues[] = $catalogues;

        return $this;
    }

    /**
     * Remove catalogues
     *
     * @param \Sulu\Bundle\TranslateBundle\Entity\Catalogue $catalogues
     */
    public function removeCatalogue(\Sulu\Bundle\TranslateBundle\Entity\Catalogue $catalogues)
    {
        $this->catalogues->removeElement($catalogues);
    }

    /**
     * Get catalogues
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCatalogues()
    {
        return $this->catalogues;
    }
}