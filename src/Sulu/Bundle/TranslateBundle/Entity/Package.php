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

use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\Type;
use JMS\Serializer\Annotation\VirtualProperty;
use Sulu\Bundle\CoreBundle\Entity\ApiEntity;

/**
 * Class Package.
 *
 * @ExclusionPolicy("all")
 */
class Package extends ApiEntity
{
    /**
     * @var int
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
     * @Expose
     */
    private $locations;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $catalogues;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->codes = new \Doctrine\Common\Collections\ArrayCollection();
        $this->locations = new \Doctrine\Common\Collections\ArrayCollection();
        $this->catalogues = new \Doctrine\Common\Collections\ArrayCollection();
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
     * @return Package
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
     * Add codes.
     *
     * @param \Sulu\Bundle\TranslateBundle\Entity\Code $codes
     *
     * @return Package
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
     * Returns the code with the given key, or null, if there is no
     * code with the given key.
     *
     * @param $key The key to search a code for
     *
     * @return null|Code
     */
    public function findCode($key)
    {
        if ($codes = $this->getCodes()) {
            foreach ($codes as $code) {
                /** @var $code Code */
                if ($code->getCode() == $key) {
                    return $code;
                }
            }
        }

        return;
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

    /**
     * Add locations.
     *
     * @param \Sulu\Bundle\TranslateBundle\Entity\Location $locations
     *
     * @return Package
     */
    public function addLocation(\Sulu\Bundle\TranslateBundle\Entity\Location $locations)
    {
        $this->locations[] = $locations;

        return $this;
    }

    /**
     * Remove locations.
     *
     * @param \Sulu\Bundle\TranslateBundle\Entity\Location $locations
     */
    public function removeLocation(\Sulu\Bundle\TranslateBundle\Entity\Location $locations)
    {
        $this->locations->removeElement($locations);
    }

    /**
     * Get locations.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getLocations()
    {
        return $this->locations;
    }

    /**
     * Add catalogues.
     *
     * @param \Sulu\Bundle\TranslateBundle\Entity\Catalogue $catalogues
     *
     * @return Package
     */
    public function addCatalogue(\Sulu\Bundle\TranslateBundle\Entity\Catalogue $catalogues)
    {
        $this->catalogues[] = $catalogues;

        return $this;
    }

    /**
     * Remove catalogues.
     *
     * @param \Sulu\Bundle\TranslateBundle\Entity\Catalogue $catalogues
     */
    public function removeCatalogue(\Sulu\Bundle\TranslateBundle\Entity\Catalogue $catalogues)
    {
        $this->catalogues->removeElement($catalogues);
    }

    /**
     * Get catalogues.
     *
     * @return \Doctrine\Common\Collections\Collection
     * @VirtualProperty
     * @SerializedName("catalogues")
     * @Type("array")
     */
    public function getCatalogues()
    {
        return $this->catalogues;
    }
}
