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

/**
 * Catalogue
 */
class Catalogue
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $code;

    /**
     * @var \Sulu\Bundle\TranslateBundle\Entity\Package
     */
    private $package;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $translations;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->translations = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set code
     *
     * @param string $code
     * @return Catalogue
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set package
     *
     * @param \Sulu\Bundle\TranslateBundle\Entity\Package $package
     * @return Catalogue
     */
    public function setPackage(\Sulu\Bundle\TranslateBundle\Entity\Package $package)
    {
        $this->package = $package;

        return $this;
    }

    /**
     * Get package
     *
     * @return \Sulu\Bundle\TranslateBundle\Entity\Package
     */
    public function getPackage()
    {
        return $this->package;
    }

    /**
     * Add translations
     *
     * @param \Sulu\Bundle\TranslateBundle\Entity\Translation $translations
     * @return Catalogue
     */
    public function addTranslation(\Sulu\Bundle\TranslateBundle\Entity\Translation $translation)
    {
        $this->translations[] = $translation;

        return $this;
    }

    /**
     * Remove translations
     *
     * @param \Sulu\Bundle\TranslateBundle\Entity\Translation $translations
     */
    public function removeTranslation(\Sulu\Bundle\TranslateBundle\Entity\Translation $translation)
    {
        $this->translations->removeElement($translation);
    }

    /**
     * Get translations
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTranslations()
    {
        return $this->translations;
    }
}
