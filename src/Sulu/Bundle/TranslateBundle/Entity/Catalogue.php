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
use Sulu\Bundle\CoreBundle\Entity\ApiEntity;

/**
 * Catalogue.
 *
 * @ExclusionPolicy("all")
 */
class Catalogue extends ApiEntity
{
    /**
     * @var int
     * @Expose
     */
    private $id;

    /**
     * @var \Sulu\Bundle\TranslateBundle\Entity\Package
     */
    private $package;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $translations;

    /**
     * @var string
     * @Expose
     */
    private $locale;

    /**
     * @var bool
     * @Expose
     */
    private $isDefault;

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
     * Set package.
     *
     * @param \Sulu\Bundle\TranslateBundle\Entity\Package $package
     *
     * @return Catalogue
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
     * Add translations.
     *
     * @param \Sulu\Bundle\TranslateBundle\Entity\Translation $translation
     *
     * @return Catalogue
     */
    public function addTranslation(\Sulu\Bundle\TranslateBundle\Entity\Translation $translation)
    {
        $this->translations[] = $translation;

        return $this;
    }

    /**
     * Remove translations.
     *
     * @param \Sulu\Bundle\TranslateBundle\Entity\Translation $translation
     */
    public function removeTranslation(\Sulu\Bundle\TranslateBundle\Entity\Translation $translation)
    {
        $this->translations->removeElement($translation);
    }

    /**
     * Returns the translation with the given key, or null, if there is no
     * translation with the given key.
     *
     * @param $key The key to search a translation for
     *
     * @return null|Translation
     */
    public function findTranslation($key)
    {
        if ($translations = $this->getTranslations()) {
            foreach ($translations as $translation) {
                /** @var $translation Translation */
                if ($translation->getCode()->getCode() == $key) {
                    return $translation;
                }
            }
        }

        return;
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

    /**
     * Set locale.
     *
     * @param string $locale
     *
     * @return Catalogue
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
     * Set isDefault.
     *
     * @param bool $isDefault
     *
     * @return Catalogue
     */
    public function setIsDefault($isDefault)
    {
        $this->isDefault = $isDefault;

        return $this;
    }

    /**
     * Get isDefault.
     *
     * @return bool
     */
    public function getIsDefault()
    {
        return $this->isDefault;
    }
}
